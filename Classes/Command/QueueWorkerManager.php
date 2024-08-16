<?php

namespace Itx\Importer\Command;

use Exception;
use Itx\Importer\Domain\Repository\JobRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Core\Environment;

class QueueWorkerManager extends Command
{
    protected function configure(): void
    {
        $this->addArgument('workerCount', InputArgument::OPTIONAL, 'Number of workers to start', 4);
        $this->addArgument('maxJobs', InputArgument::OPTIONAL, 'Maximum jobs per worker, before it stops', 100);
    }

    public function __construct(protected JobRepository $jobRepository, string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workerCount = (int)$input->getArgument('workerCount');
        $maxJobs = (int)$input->getArgument('maxJobs');
        $consolePath = Environment::getProjectPath() . '/vendor/bin/typo3';

        $availableJobs = $this->jobRepository->countIncompleteJobsWithoutFinisherJob();
        if ($availableJobs === 0) {
            return Command::SUCCESS;
        }

        // Start workers
        for ($i = 0; $i < $workerCount; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                throw new \RuntimeException('Could not fork QueueWorkerManager process');
            }

            if ($pid === 0) {
                // Child process
                $result = pcntl_exec($consolePath, ['importer:queue-worker', $maxJobs]);
                if ($result === false) {
                    throw new \RuntimeException('Could not execute QueueWorkerManager process');
                }

                return Command::SUCCESS;
            }
        }

        $status = 0;
        pcntl_wait($status);

        return Command::SUCCESS;
    }
}
