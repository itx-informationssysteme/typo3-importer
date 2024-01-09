<?php

namespace Itx\Importer\Domain\Repository;

use Itx\Importer\Domain\Model\BackendUser;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class BackendUserRepository extends \TYPO3\CMS\Beuser\Domain\Repository\BackendUserRepository
{
    /**
     * @return BackendUser[]|QueryResultInterface
     */
    public function findByimporterFailedNotification(): QueryResultInterface|array
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching($query->equals('importer_failed_notification', 1))->execute();
    }
}
