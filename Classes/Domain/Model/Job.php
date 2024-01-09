<?php

namespace Itx\Importer\Domain\Model;

use DateTime;

class Job extends \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMPLETED = 'completed';

    protected int $sorting = 0;
    protected DateTime $startTime;
    protected DateTime $endTime;
    protected bool $isFinisher = false;

    /** @var string
     * Can have the following values = ['queued', 'running', 'completed', 'failed']
     */
    protected string $status = 'queued';
    protected string $payloadType = "";
    protected string $payload = "";
    protected string $failureReason = "";
    protected Import $import;

    public function __construct() {
        $this->startTime = new DateTime();
        $this->endTime = new DateTime();
    }

    /**
     * @return DateTime
     */
    public function getStartTime(): DateTime
    {
        return $this->startTime;
    }

    /**
     * @param DateTime $startTime
     */
    public function setStartTime(DateTime $startTime): void
    {
        $this->startTime = $startTime;
    }

    /**
     * @return DateTime
     */
    public function getEndTime(): DateTime
    {
        return $this->endTime;
    }

    /**
     * @param DateTime $endTime
     */
    public function setEndTime(DateTime $endTime): void
    {
        $this->endTime = $endTime;
    }

    /**
     * @return bool
     */
    public function isFinisher(): bool
    {
        return $this->isFinisher;
    }

    /**
     * @param bool $isFinisher
     */
    public function setIsFinisher(bool $isFinisher): void
    {
        $this->isFinisher = $isFinisher;
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
     * @return string
     */
    public function getPayloadType(): string
    {
        return $this->payloadType;
    }

    /**
     * @param string $payloadType
     */
    public function setPayloadType(string $payloadType): void
    {
        $this->payloadType = $payloadType;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * @param string $payload
     */
    public function setPayload(string $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * @return Import
     */
    public function getImport(): Import
    {
        return $this->import;
    }

    /**
     * @param Import $import
     */
    public function setImport(Import $import): void
    {
        $this->import = $import;
    }

    /**
     * @return int
     */
    public function getSorting(): int
    {
        return $this->sorting;
    }

    /**
     * @param int $sorting
     */
    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }

    /**
     * @return string
     */
    public function getFailureReason(): string
    {
        return $this->failureReason;
    }

    /**
     * @param string $failureReason
     */
    public function setFailureReason(string $failureReason): void
    {
        $this->failureReason = $failureReason;
    }
}
