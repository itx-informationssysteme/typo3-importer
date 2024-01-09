<?php

namespace Itx\Importer\Service;

use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Domain\Model\Job;
use Itx\Importer\Domain\Repository\JobRepository;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;

class JobQueueService
{
    protected Serializer $serializer;

    public function __construct(protected JobRepository $jobRepository)
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * Adds a job to the queue. The caller is responsible for
     * ensuring that some time the persistence manager ist called, in case of a lot of jobs.
     *
     * @param Import $import
     * @param mixed  $payload
     *
     * @return void
     * @throws IllegalObjectTypeException
     */
    public function addJob(Import $import, mixed $payload): void
    {
        $job = new Job();

        // Serialize the payload to JSON
        $jsonPayload = $this->serializer->serialize($payload, 'json');
        $job->setPayload($jsonPayload);
        $job->setStatus(Job::STATUS_QUEUED);

        $job->setPayloadType(get_class($payload));
        $job->setSorting(0);
        $job->setPid(0);

        $job->setImport($import);

        $this->jobRepository->add($job);
    }

    /**
     * This method adds a job to the queue that will be executed after all other jobs are finished.
     *
     * @throws IllegalObjectTypeException
     */
    public function addFinisherJob(Import $import): void
    {
        $job = new Job();
        $job->setPayloadType('');
        $job->setPayload('');
        $job->setPid(0);
        $job->setStatus(Job::STATUS_QUEUED);
        $job->setSorting(time());
        $job->setImport($import);
        $job->setIsFinisher(true);
        $this->jobRepository->add($job);
    }
}
