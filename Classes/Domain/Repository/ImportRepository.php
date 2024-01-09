<?php

namespace Itx\Importer\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class ImportRepository extends Repository
{
    public function findLastImportWithTypeAndJobs(string $type): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd([
                                                $query->equals('import_type', $type),
                                                $query->greaterThan('total_jobs', 0),
                                            ]))->setOrderings([
                                                                  'crdate' => QueryInterface::ORDER_DESCENDING,
                                                              ])->setLimit(1);

        return $query->execute();
    }

    public function findNByImportType(string $importType, int|null $limit = null): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        $query->setOrderings(['start_time' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING])
              ->matching($query->equals('import_type', $importType));

        if ($limit) {
            $query->setLimit($limit);
        }

        return $query->execute();
    }

    /**
     * @param string $importType
     * @param string $status
     *
     * @return int
     */
    public function countImportsWithStatus(string $importType, string $status): int
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->setOrderings(['start_time' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING])
              ->matching($query->logicalAnd([
                                                $query->equals('import_type', $importType),
                                                $query->equals('status', $status),
                                            ]));

        return $query->execute()->count();
    }

    public function findAll()
    {

        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->setOrderings(['uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);

        return $query->execute();
    }
}
