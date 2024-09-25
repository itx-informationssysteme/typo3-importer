<?php

namespace Itx\Importer\Command\Producer;

use Generator;
use Itx\Importer\Domain\Model\ExamplePayload;
use Itx\Importer\Domain\Model\Import;

class ExampleProducer extends AbstractJobProducer
{

    public static function getImportType(): string
    {
        return 'example';
    }

    protected function isSourceAvailable(): bool
    {
        throw new \Exception('Not implemented');
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function generateJobs(): Generator
    {
        for ($i = 0; $i < 10; $i++) {
            $payload = new ExamplePayload();
            $payload->property1 = 'value1';
            $payload->property2 = 'value2';

            yield $payload;
        }
    }

    public function finishImport(Import $import): void
    {
    }

    public static function getImportLabel(): string
    {
        return "Example";
    }
}
