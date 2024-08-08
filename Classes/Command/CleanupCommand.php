<?php

namespace Itx\Importer\Command;

use Itx\Importer\Command\Producer\AbstractJobProducer;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Repository\ImportRepository;
use Itx\Importer\Domain\Repository\JobRepository;
use Itx\Importer\Domain\Repository\StatisticRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class CleanupCommand extends Command
{
    public function __construct(
        protected iterable $producers,
        protected ImportRepository $importRepository,
        protected JobRepository $jobRepository,
        protected PersistenceManager $persistenceManager,
        protected StatisticRepository $statisticRepository
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('importer:cleanup')
            ->setDescription('Cleanup the database')->addArgument('numberToKeep', InputArgument::OPTIONAL, 'Number to keep', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $numberToKeep = (int)$input->getArgument('numberToKeep');
        if ($numberToKeep < 1) {
            $output->writeln('Number to keep must be greater than 0');
            return Command::FAILURE;
        }

        /** @var AbstractJobProducer $producer */
        foreach ($this->producers as $producer) {
            $output->writeln('Cleaning up ' . $producer->getImportType());

            /** @var Import $import */
            foreach ($this->importRepository->findOutdatedImports($producer->getImportType(), $numberToKeep) as $import) {
                $jobsDeleted = $this->jobRepository->deleteByImport($import);
                $output->writeln('Deleted ' . $jobsDeleted . ' jobs for import ' . $import->getUid());

                $statsDeleted = $this->statisticRepository->deleteByImport($import);
                $output->writeln('Deleted ' . $statsDeleted . ' statistics for import ' . $import->getUid());

                $this->importRepository->remove($import);
                $this->persistenceManager->persistAll();

                $output->writeln('Deleted import ' . $import->getUid());
            }
        }

        $output->writeln('Cleanup done');
        return Command::SUCCESS;
    }
}
