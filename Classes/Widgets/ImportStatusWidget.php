<?php

declare(strict_types=1);

namespace Itx\Importer\Widgets;

/**
 * Latest run of every import type: status, start, duration and job progress.
 */
class ImportStatusWidget extends AbstractImportWidget
{
    protected function getTemplateName(): string
    {
        return 'Widget/ImportStatus';
    }

    protected function getTemplateVariables(): array
    {
        return ['types' => $this->dataProvider->getLatestImportsByType()];
    }
}
