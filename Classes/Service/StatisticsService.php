<?php

namespace Itx\Importer\Service;

use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Repository\ImportRepository;
use Itx\Importer\Domain\Repository\StatisticRepository;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireException;
use TYPO3\CMS\Core\Locking\Exception\LockAcquireWouldBlockException;
use TYPO3\CMS\Core\Locking\Exception\LockCreateException;
use TYPO3\CMS\Core\Locking\LockingStrategyInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class StatisticsService
{
    /**
     * @var array<string, Lock>
     */
    protected array $locks = [];

    public function __construct(protected LockingService      $lockingService,
                                protected StatisticRepository $statisticRepository,
                                protected ImportRepository    $importRepository,
                                protected PersistenceManager  $persistenceManager)
    {
    }

    private function getLockOrCreate(string $name): LockingStrategyInterface
    {
        if (!isset($this->locks[$name])) {
            $this->locks[$name] = $this->lockingService->createLock($name);
        }

        return $this->locks[$name];
    }

    /**
     * @param string $recordName
     * @param string $tableName
     * @param Import $import
     * @param int    $added
     * @param int    $updated
     * @param int    $deleted
     * @param int    $unchanged
     *
     * @throws IllegalObjectTypeException
     * @throws UnknownObjectException
     * @throws LockAcquireException
     * @throws LockAcquireWouldBlockException
     * @throws LockCreateException
     */
    public function addStatistic(string $recordName,
                                 string $tableName,
                                 Import $import,
                                 int    $added,
                                 int    $updated,
                                 int    $deleted,
                                 int    $unchanged): void
    {
        $importUid = $import->getUid();

        $lock = $this->lockingService->createLock("statistic.$importUid.$tableName");
        if (!$lock->acquire()) {
            throw new \RuntimeException("Could not acquire lock for $importUid.$tableName");
        }

        $statistic = $this->statisticRepository->findByRecordTableAndImport($tableName, $import);
        if (!$statistic) {
            $statistic = new \Itx\Importer\Domain\Model\Statistic();
            $statistic->setRecordName($recordName);
            $statistic->setRecordTable($tableName);
            $statistic->setPid(0);
            $statistic->setNumberAdded(0);
            $statistic->setNumberUpdated(0);
            $statistic->setNumberDeleted(0);
            $statistic->setNumberUnchanged(0);

            $import->getStatistics()->attach($statistic);
            $this->importRepository->update($import);
        }

        $statistic->setNumberAdded($statistic->getNumberAdded() + $added);
        $statistic->setNumberUpdated($statistic->getNumberUpdated() + $updated);
        $statistic->setNumberDeleted($statistic->getNumberDeleted() + $deleted);
        $statistic->setNumberUnchanged($statistic->getNumberUnchanged() + $unchanged);

        if (!$statistic->_isNew()) {
            $this->statisticRepository->update($statistic);
        }

        $this->persistenceManager->persistAll();

        $lock->release();
    }
}
