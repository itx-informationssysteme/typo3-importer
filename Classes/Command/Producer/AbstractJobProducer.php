<?php

namespace Itx\Importer\Command\Producer;

use Exception;
use Generator;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Repository\ImportRepository;
use Itx\Importer\Domain\Repository\JobRepository;
use Itx\Importer\Payload\PayloadInterface;
use Itx\Importer\Service\JobQueueService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

#[Channel('import')]
abstract class AbstractJobProducer extends Command
{
    protected ImportRepository $importRepository;
    protected JobRepository $jobRepository;
    protected PersistenceManager $persistenceManager;
    protected LoggerInterface $logger;

    protected InputInterface $input;

    public function __construct(ImportRepository          $importRepository,
                                JobRepository             $jobRepository,
                                PersistenceManager        $persistenceManager,
                                LoggerInterface           $logger,
                                protected JobQueueService $jobQueueService)
    {
        $this->importRepository = $importRepository;
        $this->jobRepository = $jobRepository;
        $this->persistenceManager = $persistenceManager;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure(): void
    {
        $importType = static::getImportType();
        $this->setName('importer:producer:' . $importType);
        $this->setDescription("This command is the job producer for the $importType import");
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // save input so it can be used by concrete producers
        $this->setInput($input);
        try {
            if (!$this->isSourceAvailable()) {
                throw new \RuntimeException('Source is not available');
            }
        }
        catch (Exception $e) {
            $this->logger->error("[PRODUCER][{type}] {message}, Trace: {trace}, Code: {code}",
                                 [
                                     'message' => $e->getMessage(),
                                     'trace' => $e->getTraceAsString(),
                                     'code' => $e->getCode(),
                                     'type' => static::getImportType()
                                 ]);

            throw $e;
        }

        // Check if input with same type is already running

        if ($this->importRepository->countImportsWithStatus(static::getImportType(), Import::IMPORT_STATUS_RUNNING) > 0) {
            $this->logger->notice("[PRODUCER][{type}] Import is already running -> skipping.",
                                  ['type' => static::getImportType()]);
            $output->writeln('Import is already running. Skipping import.');

            return Command::FAILURE;
        }

        $this->logger->info("[PRODUCER][{type}] Starting new import", ['type' => static::getImportType()]);

        // check if there are jobs -> if no jobs are found, do not create import record
        $jobs = $this->generateJobs();
        if (!$jobs->current()) {
            $this->logger->notice("[PRODUCER][{type}] No jobs found -> skipping.", ['type' => static::getImportType()]);

            return Command::FAILURE;
        }
        $jobs->rewind();

        // Create an Import Record
        $import = new Import();
        $import->setImportType(static::getImportType());
        $import->setStartTime(new \DateTime());
        $this->importRepository->add($import);
        $this->persistenceManager->persistAll();

        $jobCounter = 0;

        // Create Jobs
        foreach ($jobs as $payload) {
            $this->jobQueueService->addJob($import, $payload);
            $jobCounter++;

            if ($jobCounter % 10 === 0) {
                $this->persistenceManager->persistAll();
            }
        }

        // Add one final finish marker job
        $this->jobQueueService->addFinisherJob($import);

        $import->setTotalJobs($jobCounter);

        $this->persistenceManager->persistAll();

        $this->logger->info("[PRODUCER][{type}] Created {count} initial jobs for import",
                            ['type' => static::getImportType(), 'count' => $jobCounter]);

        return Command::SUCCESS;
    }

    /**
     * @return InputInterface
     */
    public function getInput(): InputInterface
    {
        return $this->input;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }

    /**
     * @return string The import type as string (e.g. "product")
     */
    abstract public static function getImportType(): string;

    /**
     * @return string The import name label as string (e.g. "Go Ahead Career")
     */
    abstract public static function getImportLabel(): string;

    /**
     * Executes last actions, after all jobs were processed
     */
    abstract public function finishImport(Import $import): void;

    /**
     * @return bool True if the source is available, false otherwise, can also throw an exception to indicate what went wrong
     * @throws Exception
     *
     */
    abstract protected function isSourceAvailable(): bool;

    /**
     * Function that return a generator that yields PayloadInterface objects. These Payloads will be used to create jobs.
     *
     * @return Generator<PayloadInterface>
     */
    abstract protected function generateJobs(): Generator;
}
