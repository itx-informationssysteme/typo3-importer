<?php

namespace Itx\Importer\Domain\Repository;

use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Statistic;

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
}
