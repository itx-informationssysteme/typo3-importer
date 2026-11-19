<?php

declare(strict_types=1);

namespace Itx\Importer\Dashboard;

use Itx\Importer\Command\Producer\AbstractJobProducer;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Job;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Read-only data source for the dashboard widgets.
 *
 * Uses plain DBAL queries instead of the Extbase repositories, so it works inside the dashboard
 * (no Extbase request / storage page handling) and does not depend on the domain model.
 */
class ImportDashboardDataProvider
{
    private const TABLE_IMPORT = 'tx_importer_domain_model_import';
    private const TABLE_JOB = 'tx_importer_domain_model_job';
    private const TABLE_STATISTIC = 'tx_importer_domain_model_statistic';

    /** @var array<string,string>|null import type => label */
    private ?array $labels = null;

    /**
     * @param iterable<AbstractJobProducer> $producers
     */
    public function __construct(
        private readonly iterable $producers,
        private readonly ConnectionPool $connectionPool
    ) {}

    /**
     * One entry per import type (registered producers plus types that only exist in the database),
     * each with its most recent import and live job counts.
     *
     * @return list<array{type: string, label: string, import: array<string,mixed>|null, jobs: array<string,int>, duration: string|null}>
     */
    public function getLatestImportsByType(): array
    {
        $entries = [];
        foreach ($this->getImportTypes() as $type) {
            $import = $this->findLatestImport($type);

            $entries[] = [
                'type' => $type,
                'label' => $this->getLabel($type),
                'import' => $import,
                'jobs' => $import !== null ? $this->getJobCounts((int)$import['uid']) : $this->emptyJobCounts(),
                'duration' => $import !== null ? $this->getDuration($import) : null,
            ];
        }

        return $entries;
    }

    /**
     * Statistics rows of the most recent import per type; types without statistics are omitted.
     *
     * @return list<array{type: string, label: string, import: array<string,mixed>, statistics: list<array<string,mixed>>}>
     */
    public function getLatestStatisticsByType(): array
    {
        $result = [];
        foreach ($this->getLatestImportsByType() as $entry) {
            if ($entry['import'] === null) {
                continue;
            }
            $statistics = $this->findStatistics((int)$entry['import']['uid']);
            if ($statistics === []) {
                continue;
            }
            $result[] = [
                'type' => $entry['type'],
                'label' => $entry['label'],
                'import' => $entry['import'],
                'statistics' => $statistics,
            ];
        }

        return $result;
    }

    /**
     * Most recent failed jobs across all imports.
     *
     * @return list<array<string,mixed>>
     */
    public function getRecentFailedJobs(int $limit = 10): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_JOB);
        $rows = $queryBuilder
            ->select('j.uid', 'j.import', 'j.end_time', 'j.failure_reason', 'i.import_type')
            ->from(self::TABLE_JOB, 'j')
            ->join('j', self::TABLE_IMPORT, 'i', $queryBuilder->expr()->eq('i.uid', $queryBuilder->quoteIdentifier('j.import')))
            ->where($queryBuilder->expr()->eq('j.status', $queryBuilder->createNamedParameter(Job::STATUS_FAILED)))
            ->orderBy('j.end_time', 'DESC')
            ->addOrderBy('j.uid', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as &$row) {
            $row['label'] = $this->getLabel((string)$row['import_type']);
        }
        unset($row);

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function getImportTypes(): array
    {
        $types = array_keys($this->getLabels());

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_IMPORT);
        $dbTypes = $queryBuilder
            ->select('import_type')
            ->from(self::TABLE_IMPORT)
            ->groupBy('import_type')
            ->executeQuery()
            ->fetchFirstColumn();

        $types = array_values(array_unique(array_merge($types, array_map('strval', $dbTypes))));
        sort($types);

        return $types;
    }

    /**
     * @return array<string,string>
     */
    private function getLabels(): array
    {
        if ($this->labels === null) {
            $this->labels = [];
            foreach ($this->producers as $producer) {
                $this->labels[$producer::getImportType()] = $producer::getImportLabel();
            }
        }

        return $this->labels;
    }

    private function getLabel(string $type): string
    {
        return $this->getLabels()[$type] ?? $type;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function findLatestImport(string $type): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_IMPORT);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_IMPORT)
            ->where($queryBuilder->expr()->eq('import_type', $queryBuilder->createNamedParameter($type)))
            ->orderBy('start_time', 'DESC')
            ->addOrderBy('uid', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        return $row === false ? null : $row;
    }

    /**
     * Live counts from the job table (the counters on the import record are only written when the import finishes).
     *
     * @return array<string,int>
     */
    private function getJobCounts(int $importUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_JOB);
        $rows = $queryBuilder
            ->select('status')
            ->addSelectLiteral($queryBuilder->expr()->count('*', 'amount'))
            ->from(self::TABLE_JOB)
            ->where($queryBuilder->expr()->eq('import', $queryBuilder->createNamedParameter($importUid, Connection::PARAM_INT)))
            ->groupBy('status')
            ->executeQuery()
            ->fetchAllAssociative();

        $counts = $this->emptyJobCounts();
        foreach ($rows as $row) {
            $amount = (int)$row['amount'];
            $counts['total'] += $amount;
            switch ($row['status']) {
                case Job::STATUS_QUEUED:
                    $counts['queued'] += $amount;
                    break;
                case Job::STATUS_RUNNING:
                    $counts['running'] += $amount;
                    break;
                case Job::STATUS_COMPLETED:
                    $counts['completed'] += $amount;
                    break;
                case Job::STATUS_FAILED:
                    $counts['failed'] += $amount;
                    break;
            }
        }

        $counts['done'] = $counts['completed'] + $counts['failed'];
        $counts['progress'] = $counts['total'] > 0 ? (int)round($counts['done'] / $counts['total'] * 100) : 0;

        return $counts;
    }

    /**
     * @return array<string,int>
     */
    private function emptyJobCounts(): array
    {
        return ['queued' => 0, 'running' => 0, 'completed' => 0, 'failed' => 0, 'total' => 0, 'done' => 0, 'progress' => 0];
    }

    /**
     * @param array<string,mixed> $import
     */
    private function getDuration(array $import): ?string
    {
        if ($import['status'] === Import::IMPORT_STATUS_RUNNING) {
            return null;
        }

        $seconds = max(0, (int)$import['end_time'] - (int)$import['start_time']);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $rest = $seconds % 60;

        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }
        if ($rest > 0 || $parts === []) {
            $parts[] = $rest . 's';
        }

        return implode(' ', $parts);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function findStatistics(int $importUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_STATISTIC);
        $rows = $queryBuilder
            ->select('*')
            ->from(self::TABLE_STATISTIC)
            ->where($queryBuilder->expr()->eq('import', $queryBuilder->createNamedParameter($importUid, Connection::PARAM_INT)))
            ->orderBy('record_name', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as &$row) {
            $row['total_after_import'] = (int)$row['number_added'] + (int)$row['number_updated'] + (int)$row['number_unchanged'];
        }
        unset($row);

        return $rows;
    }
}
