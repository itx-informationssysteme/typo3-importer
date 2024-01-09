<?php

namespace Itx\Importer\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

class Statistic extends AbstractDomainObject
{
    protected string $recordName;
    protected string $recordTable;
    protected int $numberAdded;
    protected int $numberUpdated;
    protected int $numberDeleted;
    protected int $numberUnchanged;

    /**
     * @return string
     */
    public function getRecordName(): string
    {
        return $this->recordName;
    }

    /**
     * @param string $recordName
     */
    public function setRecordName(string $recordName): void
    {
        $this->recordName = $recordName;
    }

    /**
     * @return string
     */
    public function getRecordTable(): string
    {
        return $this->recordTable;
    }

    /**
     * @param string $recordTable
     */
    public function setRecordTable(string $recordTable): void
    {
        $this->recordTable = $recordTable;
    }

    /**
     * @return int
     */
    public function getNumberAdded(): int
    {
        return $this->numberAdded;
    }

    /**
     * @param int $numberAdded
     */
    public function setNumberAdded(int $numberAdded): void
    {
        $this->numberAdded = $numberAdded;
    }

    /**
     * @return int
     */
    public function getNumberUpdated(): int
    {
        return $this->numberUpdated;
    }

    /**
     * @param int $numberUpdated
     */
    public function setNumberUpdated(int $numberUpdated): void
    {
        $this->numberUpdated = $numberUpdated;
    }

    /**
     * @return int
     */
    public function getNumberDeleted(): int
    {
        return $this->numberDeleted;
    }

    /**
     * @param int $numberDeleted
     */
    public function setNumberDeleted(int $numberDeleted): void
    {
        $this->numberDeleted = $numberDeleted;
    }

    /**
     * @return int
     */
    public function getNumberUnchanged(): int
    {
        return $this->numberUnchanged;
    }

    /**
     * @param int $numberUnchanged
     */
    public function setNumberUnchanged(int $numberUnchanged): void
    {
        $this->numberUnchanged = $numberUnchanged;
    }
}
