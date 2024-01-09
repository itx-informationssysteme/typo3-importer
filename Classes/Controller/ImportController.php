<?php

namespace Itx\Importer\Controller;

use Itx\Importer\Command\Producer\AbstractJobProducer;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Job;
use Itx\Importer\Domain\Repository\ImportRepository;
use Itx\Importer\Domain\Repository\JobRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Beuser\Domain\Model\ModuleData;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class ImportController extends ActionController
{
    protected const ITEMS_PER_PAGE_JOBS_DETAIL = 5;
    protected const ITEMS_PER_PAGE_LIST_ALL = 10;
    protected const ITEMS_PER_PAGE_LIST_GROUP = 3;

    protected ?ModuleData $moduleData = null;
    protected ?ModuleTemplate $moduleTemplate = null;

    /** @var array<AbstractJobProducer> */
    protected array $importProducer = [];

    /** @var array<string,ExecuteSchedulableCommandTask> */
    protected array $schedulerTasks;

    public function __construct(iterable                        $producers,
                                protected ModuleTemplateFactory $moduleTemplateFactory,
                                protected ImportRepository      $importRepository,
                                protected JobRepository         $jobRepository,
                                protected Scheduler             $scheduler)
    {
        foreach ($producers as $producer) {
            $this->importProducer[$producer::getImportType()] = $producer;
        }

        // Find the producer scheduler task to get the last execution time and the next execution time
        $tasks = $this->scheduler->fetchTasksWithCondition('', true);

        /** @var AbstractTask $task */
        foreach ($tasks as $task) {
            /** @var ExecuteSchedulableCommandTask $task */
            if ($task instanceof ExecuteSchedulableCommandTask &&
                str_starts_with($task->getCommandIdentifier(), 'importer:producer')) {
                $importIdentifier = str_replace('importer:producer:', '', $task->getCommandIdentifier());
                $this->schedulerTasks[$importIdentifier] = $task;
            }
        }
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle('');

    }

    /**
     * @return ResponseInterface
     */
    public function listAction(): ResponseInterface
    {
        $importTypes = [];

        foreach ($this->importProducer as $producer) {
            $importType = $producer::getImportType();
            $imports = $this->importRepository->findNByImportType($importType, self::ITEMS_PER_PAGE_LIST_GROUP);

            if (count($imports) === 0) {
                continue;
            }

            $currentlyRunning = false;
            foreach ($imports as $import) {
                if ($import->getStatus() === Import::IMPORT_STATUS_RUNNING) {
                    $currentlyRunning = true;
                    break;
                }
            }

            // Array shape: ['nextExecution' => int, 'lastExecution' => int]
            /** @var array<string,int>|null $taskData */
            $taskData = null;

            $schedulerTask = $this->schedulerTasks[$importType] ?? null;
            if ($schedulerTask !== null) {
                $taskData = [];
                $taskData['nextExecution'] = $schedulerTask->getExecution()->getNextExecution();
            }

            $importTypes[$importType] = [
                'label' => $producer::getImportLabel(),
                'type' => $importType,
                'imports' => $imports,
                'taskData' => $taskData,
                'currentlyRunning' => $currentlyRunning,
            ];
        }

        $this->view->assign('importGroups', $importTypes);
        $this->view->assign('itemsPerGroup', self::ITEMS_PER_PAGE_LIST_GROUP);

        return $this->htmlResponse();

    }

    /**
     * @throws NoSuchArgumentException
     */
    public function listAllAction(string $importType): ResponseInterface
    {
        $imports = $this->importRepository->findNByImportType($importType);

        $page = $this->request->hasArgument('page') ? (int)$this->request->getArgument('page') : 1;

        $paginator = new QueryResultPaginator($imports, $page, self::ITEMS_PER_PAGE_LIST_ALL);
        $pagination = new SimplePagination($paginator);

        $this->view->assignMultiple([
                                        'imports' => $paginator->getPaginatedItems(),
                                        'pagination' => $pagination,
                                        'paginator' => $paginator,
                                        'importType' => $importType,
                                        'importName' => $this->importProducer[$importType]::getImportLabel(),
                                    ]);

        return $this->htmlResponse();
    }

    /**
     * @throws StopActionException
     */
    public function startImportAction(string $importType): ResponseInterface
    {
        // Exec producer console command to start import
        $output = [];
        $returnCode = 0;

        $projectRoot = Environment::getProjectPath();

        $result = exec(sprintf("$projectRoot/vendor/bin/typo3cms importer:producer:%s", $importType), $output, $returnCode);
        if ($result === false) {
            throw new \RuntimeException('Error while starting import', 1599638230);
        }

        if ($returnCode !== 0) {
            $output = implode("\n", $output);
            throw new \RuntimeException("Error while starting import, return code was $returnCode: $output", 1599638330);
        }

        $this->addFlashMessage('Import started', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

        return $this->redirectToUri($this->uriBuilder->reset()->uriFor('list'));
    }

    /**
     * @param Import|null $import
     *
     * @return ResponseInterface
     * @throws ImmediateResponseException
     * @throws PageNotFoundException|NoSuchArgumentException
     */
    public function showAction(?Import $import = null): ResponseInterface
    {
        if ($import === null) {
            /** @var ErrorController $errorController */
            $errorController = GeneralUtility::makeInstance(ErrorController::class);
            $response = $errorController->pageNotFoundAction($GLOBALS['TYPO3_REQUEST'], 'Import not available/does not exist');
            throw new ImmediateResponseException($response, 1599638331);
        }

        $page = $this->request->hasArgument('page') ? (int)$this->request->getArgument('page') : 1;

        // Calculate progress
        $totalJobs = $this->jobRepository->countTotalJobsByImport($import);
        $jobsToProcess = $this->jobRepository->countIncompleteJobsByImport($import);

        $progress = 0;
        if ($totalJobs > 0) {
            $progress = round(($totalJobs - $jobsToProcess) / $totalJobs * 100);
        }

        $jobPaginator = new QueryResultPaginator($this->jobRepository->findByImportAndStatus($import, Job::STATUS_FAILED),
                                                 $page,
                                                 self::ITEMS_PER_PAGE_JOBS_DETAIL);
        $jobPagination = new SimplePagination($jobPaginator);

        $this->view->assign('import', $import);
        $this->view->assign('importName', $this->importProducer[$import->getImportType()]::getImportLabel());

        $this->view->assign('progress', $progress);
        $this->view->assign('totalJobs', $totalJobs);
        $this->view->assign('jobsToProcess', $jobsToProcess);

        $this->view->assign('jobPaginator', $jobPaginator);
        $this->view->assign('jobPagination', $jobPagination);
        $this->view->assign('jobs', $jobPaginator->getPaginatedItems());

        return $this->htmlResponse();
    }
}
