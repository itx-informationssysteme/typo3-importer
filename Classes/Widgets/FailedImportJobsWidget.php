<?php

declare(strict_types=1);

namespace Itx\Importer\Widgets;

/**
 * Most recent failed queue jobs with their failure reason. Number of rows: option "limit" (default 10).
 */
class FailedImportJobsWidget extends AbstractImportWidget
{
    protected function getTemplateName(): string
    {
        return 'Widget/FailedImportJobs';
    }

    protected function getTemplateVariables(): array
    {
        return ['jobs' => $this->dataProvider->getRecentFailedJobs((int)($this->options['limit'] ?? 10))];
    }
}
