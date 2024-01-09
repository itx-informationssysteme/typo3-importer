<?php

namespace Itx\Importer\Command;

use DateTime;
use Doctrine\DBAL\DBALException;
use Exception;
use Itx\Importer\Command\Producer\AbstractJobProducer;
use Itx\Importer\Consumer\ConsumerInterface;
use Itx\Importer\Controller\ImportController;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Job;
use Itx\Importer\Domain\Repository\BackendUserRepository;
use Itx\Importer\Domain\Repository\ImportRepository;
use Itx\Importer\Domain\Repository\JobRepository;
use Itx\Importer\Exception\JobAlreadyGoneException;
use Itx\Importer\Service\JobQueueService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

#[Channel('import')]
class QueueWorker extends \Symfony\Component\Console\Command\Command
{
    public const JOB_TIMEOUT = 180;
    protected int $processId;
    protected string $logPrefix = '';

    protected JobRepository $jobRepository;
    protected PersistenceManager $persistenceManager;
    protected LoggerInterface $logger;
    protected Serializer $serializer;
    protected ImportRepository $importRepository;
    protected BackendUserRepository $userRepository;
    protected UriBuilder $uriBuilder;

    protected Request $request;
    protected ImportController $importController;

    /** @var array<string, ConsumerInterface> */
    protected array $consumerPayloadMap = [];

    /**
     * @var array<string, AbstractJobProducer> $producerMap
     */
    protected array $producerMap = [];

    protected ?Job $currentJob = null;

    protected bool $shouldQuit = false;

    public function __construct(iterable                  $consumers,
                                iterable                  $producers,
                                JobRepository             $jobRepository,
                                PersistenceManager        $persistenceManager,
                                LoggerInterface           $logger,
                                ImportRepository          $importRepository,
                                BackendUserRepository     $userRepository,
                                UriBuilder                $uriBuilder,
                                Request                   $request,
                                protected JobQueueService $jobQueueService)
    {
        $this->jobRepository = $jobRepository;
        $this->persistenceManager = $persistenceManager;
        $this->logger = $logger;
        $this->importRepository = $importRepository;
        $this->userRepository = $userRepository;
        $this->uriBuilder = $uriBuilder;
        $this->request = $request;

        $this->processId = getmypid();
        $this->logPrefix = sprintf('[WORKER-%s] ', $this->processId);

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);

        /** @var array<ConsumerInterface> $consumers */
        foreach ($consumers as $consumer) {
            $this->consumerPayloadMap[$consumer->getPayloadType()] = $consumer;
        }

        /** @var array<AbstractJobProducer> $producers */
        foreach ($producers as $producer) {
            $this->producerMap[$producer::getImportType()] = $producer;
        }

        // Catch uncaught exceptions, so we can set the job to failed
        set_exception_handler([$this, 'handleException']);

        // Quit on SIGTERM, SIGINT, SIGQUIT
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'quit']);
            pcntl_signal(SIGINT, [$this, 'quit']);
            pcntl_signal(SIGQUIT, [$this, 'quit']);
        }

        parent::__construct();
    }

    public function quit(): bool
    {
        $this->shouldQuit = true;

        return true;
    }

    protected function configure(): void
    {
        $this->setName('importer:queue-worker');
        $this->setDescription('This command is the queue worker for the importer');
        $this->addArgument('maxJobs',
                           InputArgument::OPTIONAL,
                           'Maximum number of jobs to process before the worker quits and waits to be started again.',
                           100);
        $this->addArgument('waitingTime',
                           InputArgument::OPTIONAL,
                           'The time in seconds the command will wait to check for another job, after finding no job',
                           10);
        $this->addArgument('timeout',
                           InputArgument::OPTIONAL,
                           'The time in seconds the command will keep checking for jobs, before quitting',
                           60);
    }

    /**
     * @throws UnknownObjectException
     * @throws IllegalObjectTypeException|Throwable
     */
    public function handleException(Throwable|null $exception): void
    {
        if ($exception === null) {
            return;
        }

        if ($this->currentJob) {
            $this->currentJob->setStatus(Job::STATUS_FAILED);
            $this->currentJob->setEndTime(new DateTime());
            $this->currentJob->setFailureReason($exception);

            $this->jobRepository->update($this->currentJob);
            $this->persistenceManager->persistAll();
        }

        $this->logger->error("Job {uid} failed by uncaught exception: {exception}",
                             [
                                 'uid' => $this->currentJob?->getUid() ?? -1,
                                 'exception' => $exception
                             ]);

        // Rethrow the exception, because the queue worker is dead anyway
        throw $exception;
    }

    /**
     * @inheritDoc
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws DBALException
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $maxJobs = (int)$input->getArgument('maxJobs');
        $waitingTime = (int)$input->getArgument('waitingTime');
        $timeout = (int)$input->getArgument('timeout');

        $jobsWorkedOn = 0;
        /** @var DateTime|null $currentTimeout */
        $currentTimeout = null;

        $this->logger->info($this->logPrefix . ' Starting queue worker');

        while ($jobsWorkedOn < $maxJobs) {
            if ($this->shouldQuit) {
                $output->writeln($this->logPrefix . ' Quitting because of signal');
                $this->logger->info($this->logPrefix . ' Quitting because of signal');

                return Command::SUCCESS;
            }

            // Clear the state, so we don't have any entities in the persistence manager. This is important, because
            // when jobs are running concurrently
            $this->persistenceManager->clearState();

            try {
                $job = $this->jobRepository->findAndAcquireNextJob();
            }
            catch (JobAlreadyGoneException $e) {
                // Job was already gone, so we can just continue and try to find another one
                continue;
            }

            if ($job === null) {
                $output->writeln($this->logPrefix . ' No job found, waiting ' . $waitingTime . ' seconds');
                if ($currentTimeout !== null && time() - $currentTimeout->getTimestamp() > $timeout) {
                    $output->writeln($this->logPrefix . ' Timeout reached, quitting');
                    $this->logger->info($this->logPrefix . ' Timeout reached, stopping queue worker');

                    return Command::SUCCESS;
                }

                if ($currentTimeout === null) {
                    $this->logger->info($this->logPrefix . ' No job found for the first time, setting timeout');
                    $currentTimeout = new DateTime();
                }

                $this->logger->info($this->logPrefix . ' No job found, waiting for ' . $waitingTime . ' seconds');

                sleep($waitingTime);
                continue;
            }

            // We need to set the job globally so if there is an uncaught exception, we can set the job to failed
            $this->currentJob = $job;

            if ($job->isFinisher()) {
                $this->handleImportFinisher($job);
                continue;
            }

            // Start the job, the start time already has been set when the job was acquired
            $output->writeln($this->logPrefix . ' Working on job ' . $job->getUid());
            $this->processJob($job);

            $job->setEndTime(new DateTime());

            $this->jobRepository->update($job);
            $this->persistenceManager->persistAll();

            $jobsWorkedOn++;
            $currentTimeout = null;
        }

        $this->logger->info($this->logPrefix . ' Max jobs reached, stopping queue worker');
        $output->writeln($this->logPrefix . ' Max jobs reached, stopping queue worker');

        return Command::SUCCESS;
    }

    /**
     * @throws Exception
     */
    protected function processJob(Job $job): void
    {
        $this->logger->info($this->logPrefix . ' Processing job ' . $job->getUid() . ' of type ' . $job->getPayloadType() .
                            ' for import ' . $job->getImport()->getUid() . ' with payload ' . $job->getPayload());

        $deserializedPayload = $this->serializer->deserialize($job->getPayload(), $job->getPayloadType(), 'json');

        try {
            if (!isset($this->consumerPayloadMap[$job->getPayloadType()])) {
                throw new \RuntimeException('No consumer found for payload type ' . $job->getPayloadType());
            }

            // Run the job and process new child jobs
            $newPayloads = $this->consumerPayloadMap[$job->getPayloadType()]->runJob($deserializedPayload, $job->getImport());
            if (count($newPayloads) > 0) {
                foreach ($newPayloads as $newPayload) {
                    $this->jobQueueService->addJob($job->getImport(), $newPayload);
                }
            }
        }
        catch (Exception $e) {
            $this->logger->error($this->logPrefix . ' Error while processing job {uid}',
                                 [
                                     'uid' => $job->getUid(),
                                     'exception' => $e
                                 ]);

            $job->setStatus(Job::STATUS_FAILED);
            $job->setFailureReason($e->getMessage());

            // Re-throw the exception to stop the worker, when in Development context
            if (Environment::getContext()->isDevelopment()) {
                $this->jobRepository->update($job);
                $this->persistenceManager->persistAll();
                throw $e;
            }

            return;
        }

        $job->setStatus(Job::STATUS_COMPLETED);
    }

    /**
     * @param Job $job
     *
     * @throws DBALException
     * @throws IllegalObjectTypeException
     * @throws InvalidQueryException
     * @throws RouteNotFoundException
     * @throws SiteNotFoundException
     * @throws TransportExceptionInterface
     * @throws UnknownObjectException
     */
    protected function handleImportFinisher(Job $job): void
    {
        $importName = $job->getImport()->getImportType();

        // Process jobs that exceeded the timeout
        $timeout = self::JOB_TIMEOUT;
        /** @var Job $timeoutJob */
        foreach ($this->jobRepository->findJobsThatExceededTimeout($timeout, $job->getImport()) as $timeoutJob) {
            $this->logger->info($this->logPrefix . " Job {$timeoutJob->getUid()} exceeded timeout, setting to failed");
            $timeoutJob->setStatus(Job::STATUS_FAILED);
            $timeoutJob->setEndTime(new DateTime());
            $timeoutJob->setFailureReason("Job exceeded timeout of ${timeout}s");
            $this->jobRepository->update($timeoutJob);
        }

        $this->persistenceManager->persistAll();

        // Count all jobs that are not completed yet
        if ($this->jobRepository->countIncompleteJobsWithoutFinisherJob($job->getImport()) > 0) {
            $this->logger->info($this->logPrefix . " Import $importName ({uid}) finished, but there are still uncompleted jobs",
                                [
                                    'uid' => $job->getImport()->getUid()
                                ]);

            // Throttle
            sleep(1);

            $this->jobRepository->reinsertJobIntoQueue($job);
            $this->persistenceManager->persistAll();

            return;
        }

        $job->setStartTime(new DateTime());
        $job->setStatus(Job::STATUS_COMPLETED);
        $job->setEndTime(new DateTime());

        $this->jobRepository->update($job);
        $this->persistenceManager->persistAll();

        $import = $job->getImport();
        $import->setEndTime(new DateTime());
        $import->setTotalJobs($this->jobRepository->countTotalJobsByImport($import));
        $import->setCompletedJobs($this->jobRepository->countCompletedJobsByImport($import->getUid()));
        $import->setFailedJobs($this->jobRepository->countFailedJobsByImport($import->getUid()));

        if ($import->getFailedJobs() > 0) {
            $import->setStatus(Import::IMPORT_STATUS_FAILED);
            $this->sendEmailForFailedImport($import);
        } else {
            $import->setStatus(Import::IMPORT_STATUS_COMPLETED);
        }

        $importDuration = $import->getEndTime()->getTimestamp() - $import->getStartTime()->getTimestamp();

        $this->importRepository->update($import);
        $this->persistenceManager->persistAll();

        $producer = $this->producerMap[$import->getImportType()] ?? null;
        $producer?->finishImport($import);

        $this->logger->info($this->logPrefix .
                            " Import $importName ({uid}) completed in {duration} minutes. Jobs completed: {completedJobs}. Jobs failed: {failedJobs}.",
                            [
                                'uid' => $import->getUid(),
                                'duration' => $importDuration / 60,
                                'completedJobs' => $import->getCompletedJobs(),
                                'failedJobs' => $import->getFailedJobs(),
                            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws RouteNotFoundException
     * @throws SiteNotFoundException
     */
    protected function sendEmailForFailedImport(Import $import): void
    {
        // Set up a fake request to get the site configuration
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId(1);

        $baseUrl = $site->getBase()->__toString();

        $normalizedParams = new NormalizedParams([
                                                     'HTTP_HOST' => $site->getBase()->getHost(),
                                                     'HTTPS' => $site->getBase()->getScheme() === 'https' ? 'on' : 'off',
                                                 ], $GLOBALS['TYPO3_CONF_VARS']['SYS'], '', '');

        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                                        ->withAttribute('normalizedParams', $normalizedParams)
                                        ->withAttribute('site', $site);

        $GLOBALS['TYPO3_REQUEST'] = $request;

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_DIR', $site->getBase() . '/');

        //Get the users that should receive the email
        $users = $this->userRepository->findByimporterFailedNotification();
        if (empty($users)) {
            return;
        }

        $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? 'TYPO3 Site (no sitename found))';

        $emailSender = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '';
        if (empty($emailSender)) {
            return;
        }

        $emailSenderName = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] ?? "$siteName Importer";

        $importName = $this->producerMap[$import->getImportType()]::getImportLabel();
        $importUid = $import->getUid();

        // We need to hardcode the URL here, because url generation is not possible in the CLI context and backend routes
        $importUrl =
            "$baseUrl/typo3/module/web/ImporterImportManager?tx_importer_web_importerimportmanager%5Bimport%5D=$importUid&tx_importer_web_importerimportmanager%5Baction%5D=show&tx_importer_web_importerimportmanager%5Bcontroller%5D=Import";

        //Create and send the mail
        $email = GeneralUtility::makeInstance(FluidEmail::class);
        $email->setRequest($request);
        $email->from(new Address($emailSender, $emailSenderName))
              ->subject("$siteName - Failed Import: $importName [$importUid]")
              ->format(FluidEmail::FORMAT_BOTH) // send HTML and plaintext mail
              ->setTemplate('FailedJobEmail')
              ->assignMultiple([
                                   'import' => $import,
                                   'importName' => $importName,
                                   'importUrl' => $importUrl,
                                   'siteName' => $siteName,
                               ]);

        $receivers = [];

        foreach ($users as $user) {
            $address = $user->getEmail() ?? '';
            if ($address === '') {
                continue;
            }

            $receiver = new Address($address, $user->getRealName() ?? '');

            $receivers[] = $receiver;
        }

        $email->to(...$receivers);

        $mailer = GeneralUtility::makeInstance(Mailer::class);

        try {
            $mailer->send($email);
        }
        catch (Exception $e) {
            $this->logger->error($this->logPrefix . ' Failed to send email for failed import',
                                 [
                                     'exception' => $e
                                 ]);
        }
    }

}
