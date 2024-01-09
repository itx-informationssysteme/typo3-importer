<?php

namespace Itx\Importer\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Import extends AbstractDomainObject
{
    public const IMPORT_STATUS_RUNNING = 'RUNNING';
    public const IMPORT_STATUS_COMPLETED = 'COMPLETED';
    public const IMPORT_STATUS_FAILED = 'FAILED';

    protected \DateTime $startTime;
    protected \DateTime $endTime;

    /** @var string $status Can be RUNNING, COMPLETED or FAILED */
    protected string $status = self::IMPORT_STATUS_RUNNING;

    protected string $importType;
    protected int $failedJobs = 0;
    protected int $completedJobs = 0;
    protected int $totalJobs = 0;

    /**
     * @var ObjectStorage<Statistic>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     * @Cascade("remove")
     */
    protected ObjectStorage $statistics;

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject()
    {
        $this->startTime = new \DateTime();
        $this->endTime = new \DateTime();
        $this->statistics = new ObjectStorage();
    }

    /**
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return $this->startTime;
    }

    /**
     * @param \DateTime $startTime
     */
    public function setStartTime(\DateTime $startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return $this->endTime;
    }

    /**
     * @param \DateTime $endTime
     */
    public function setEndTime(\DateTime $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @return int
     */
    public function getFailedJobs(): int
    {
        return $this->failedJobs;
    }

    /**
     * @param int $failedJobs
     */
    public function setFailedJobs(int $failedJobs): void
    {
        $this->failedJobs = $failedJobs;
    }

    /**
     * @return int
     */
    public function getCompletedJobs(): int
    {
        return $this->completedJobs;
    }

    /**
     * @param int $completedJobs
     */
    public function setCompletedJobs(int $completedJobs): void
    {
        $this->completedJobs = $completedJobs;
    }

    /**
     * @return string
     */
    public function getImportType(): string
    {
        return $this->importType;
    }

    /**
     * @param string $importType
     */
    public function setImportType(string $importType): void
    {
        $this->importType = $importType;
    }

    /**
     * @return int
     */
    public function getTotalJobs(): int
    {
        return $this->totalJobs;
    }

    /**
     * @param int $totalJobs
     */
    public function setTotalJobs(int $totalJobs): void
    {
        $this->totalJobs = $totalJobs;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return ObjectStorage
     */
    public function getStatistics(): ObjectStorage
    {
        return $this->statistics;
    }

    /**
     * @param ObjectStorage $statistics
     */
    public function setStatistics(ObjectStorage $statistics): void
    {
        $this->statistics = $statistics;
    }

    public function getStatisticsByTableName(string $tableName): ?Statistic
    {
        foreach ($this->statistics as $statistic) {
            if ($statistic->getRecordTable() === $tableName) {
                return $statistic;
            }
        }

        return null;
    }
}
