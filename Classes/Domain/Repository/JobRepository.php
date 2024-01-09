<?php

namespace Itx\Importer\Domain\Repository;

use Doctrine\DBAL\DBALException;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Job;
use Itx\Importer\Exception\JobAlreadyGoneException;
use Traversable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Repository;

class JobRepository extends Repository
{
    /**
     * @return Job|null
     * @throws DBALException
     * @throws JobAlreadyGoneException
     */
    public function findAndAcquireNextJob(): ?object
    {
        // Initialize QueryBuilder
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                                      ->getQueryBuilderForTable('tx_importer_domain_model_job');

        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->equals('status', Job::STATUS_QUEUED));
        $query->setLimit(1);
        $query->setOrderings([
                                 'sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
                                 'uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING
                             ]);

        $job = $query->execute()->getFirst();
        if ($job === null) {
            return null;
        }

        // Set the status to "in progress"
        $result = $queryBuilder->update('tx_importer_domain_model_job')
                               ->where($queryBuilder->expr()->eq('uid',
                                                                 $queryBuilder->createNamedParameter($job->getUid(),
                                                                                                     \PDO::PARAM_INT)),
                                       $queryBuilder->expr()->eq('status',
                                                                 $queryBuilder->createNamedParameter(Job::STATUS_QUEUED)))
                               ->set('status', Job::STATUS_RUNNING)
                               ->set('start_time', time(), true, \PDO::PARAM_INT)
                               ->execute();

        // If the update was not successful, the job was already acquired by another process
        if ($result === 0) {
            throw new JobAlreadyGoneException('Job ' . $job->getUid() . ' was already acquired by another process', 1622020000);
        }

        $job->setStatus(Job::STATUS_RUNNING);

        return $job;
    }

    /**
     * @throws DBALException
     */
    public function reinsertJobIntoQueue(Job $job): void
    {
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
                                      ->getQueryBuilderForTable('tx_importer_domain_model_job');

        $result = $queryBuilder->update('tx_importer_domain_model_job')
                               ->where($queryBuilder->expr()->eq('uid',
                                                                 $queryBuilder->createNamedParameter($job->getUid(),
                                                                                                     \PDO::PARAM_INT)))
                               ->set('status', Job::STATUS_QUEUED)
                               ->set('sorting', $job->getSorting() + 30, true, \PDO::PARAM_INT)
                               ->execute();

        if ($result === 0) {
            throw new \RuntimeException('Could not reinsert job ' . $job->getUid() . ' into queue', 1622020001);
        }
    }

    public function countTotalJobsByImport(Import $import): int
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->logicalAnd($query->equals('import', $import->getUid())));

        return $query->execute()->count();
    }

    public function countIncompleteJobsByImport(Import $import): int
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->logicalAnd($query->equals('import', $import->getUid()),
                                            $query->logicalOr($query->equals('status', Job::STATUS_QUEUED),
                                                              $query->equals('status', Job::STATUS_RUNNING))));

        return $query->execute()->count();
    }

    public function countIncompleteJobsWithoutFinisherJob(?Import $import = null): int
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $logicalAnd = [
            $query->logicalOr($query->equals('status', Job::STATUS_QUEUED),
                              $query->equals('status', Job::STATUS_RUNNING)),
            $query->equals('isFinisher', false)
        ];

        if ($import !== null) {
            $logicalAnd[] = $query->equals('import', $import->getUid());
        }

        $query->matching($query->logicalAnd(...$logicalAnd));

        return $query->execute()->count();
    }

    /**
     * Find jobs that exceeded the given timeout
     *
     * @param int    $timeout Timeout in seconds
     * @param Import $import
     *
     * @return Traversable<Job>
     * @throws InvalidQueryException
     */
    public function findJobsThatExceededTimeout(int $timeout, Import $import): Traversable
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->logicalAnd($query->equals('status', Job::STATUS_RUNNING),
                                            $query->equals('isFinisher', false),
                                            $query->equals('import', $import->getUid()),
                                            $query->lessThan('startTime', time() - $timeout)));

        return $query->execute();
    }

    public function countFailedJobsByImport(int $importUid): int
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->logicalAnd($query->equals('status', Job::STATUS_FAILED), $query->equals('import', $importUid)));

        return $query->execute()->count();
    }

    public function countCompletedJobsByImport(int $importUid): int
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);

        $query->matching($query->logicalAnd($query->equals('status', Job::STATUS_COMPLETED),
                                            $query->equals('import', $importUid)));

        return $query->execute()->count();
    }

    public function findByImportAndStatus(Import $import, string $status): null|object
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching($query->logicalAnd($query->equals('import', $import->getUid()),
                                                   $query->equals('status', $status)))->execute();
    }

    public function findFinisherByImportId(int $id): null|object
    {
        $query = $this->createQuery();

        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->matching($query->logicalAnd($query->equals('import', $id),
                                                   $query->equals('is_finisher', 1)))->execute()->getFirst();
    }

    public function findAll()
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);

        return $query->execute();
    }
}
