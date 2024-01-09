<?php

namespace Itx\Importer\Domain\Model;



class BackendUser extends \TYPO3\CMS\Beuser\Domain\Model\BackendUser
{
    protected bool $importerFailedNotification;

    /**
     * @return bool
     */
    public function isImporterFailedNotification(): bool
    {
        return $this->importerFailedNotification;
    }

    /**
     * @param bool $importerFailedNotification
     */
    public function setImporterFailedNotification(bool $importerFailedNotification): void
    {
        $this->importerFailedNotification = $importerFailedNotification;
    }
}
