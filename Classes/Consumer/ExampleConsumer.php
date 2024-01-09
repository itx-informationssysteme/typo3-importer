<?php

namespace Itx\Importer\Consumer;

use Itx\Importer\Domain\Model\ExamplePayload;
use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Service\LockingService;

class ExampleConsumer implements ConsumerInterface
{
    public function __construct(protected LockingService $lockingService) {

    }

    /**
     * @param ExamplePayload $payload
     * @param Import         $import
     *
     * @inheritDoc
     */
    public function runJob(mixed $payload, Import $import): array
    {
        $lock = $this->lockingService->createLock('example');
        $lock->acquire(true);
        echo 'Running job with payload: ' . $payload->property1 . ' ' . $payload->property2 . PHP_EOL;
        sleep(6);

        $lock->release();

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getPayloadType(): string
    {
        return ExamplePayload::class;
    }
}
