<?php

namespace Itx\Importer\Domain\Model;

use Itx\Importer\Payload\PayloadInterface;

class ExamplePayload implements PayloadInterface
{
    public string $property1;
    public string $property2;
}
