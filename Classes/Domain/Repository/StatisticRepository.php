<?php

namespace Itx\Importer\Domain\Repository;

use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Statistic;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StatisticRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function findByRecordTableAndImport(string $recordTable, Import $import): ?Statistic
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);

        $query->matching(
            $query->logicalAnd(
                $query->equals('recordTable', $recordTable),
                $query->equals('import', $import)
            )
        );

        /** @var Statistic $result */
        $result = $query->execute()->getFirst();

        return $result;
    }

    public function deleteByImport(Import $import): int
    {
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                              ->getQueryBuilderForTable('tx_importer_domain_model_statistic');

        $result = $queryBuilder->delete('tx_importer_domain_model_statistic')
                                ->where($queryBuilder->expr()->eq(
                                    'import',
                                    $queryBuilder->createNamedParameter(
                                        $import->getUid(),
                                        \PDO::PARAM_INT
                                    )
                                ))
                               ->executeStatement();

        return $result;
    }
}
