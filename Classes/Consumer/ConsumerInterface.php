<?php

namespace Itx\Importer\Consumer;

use Itx\Importer\Domain\Model\Import;
use Itx\Importer\Payload\PayloadInterface;

interface ConsumerInterface
{
    /**
     * Implements the execution logic of the consumer.
     *
     * Do not modify the import object, it is read-only.
     *
     * @param mixed  $payload
     * @param Import $import
     *
     * @return array<PayloadInterface> Can return an array of payloads, which in turn generates new jobs
     */
    public function runJob(mixed $payload, Import $import): array;

    /**
     * Returns the payload type this consumer can handle
     */
    public function getPayloadType(): string;
}
