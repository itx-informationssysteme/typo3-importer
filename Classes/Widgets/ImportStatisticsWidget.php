<?php

declare(strict_types=1);

namespace Itx\Importer\Widgets;

/**
 * Records added / updated / deleted / unchanged by the latest run of every import type.
 */
class ImportStatisticsWidget extends AbstractImportWidget
{
    protected function getTemplateName(): string
    {
        return 'Widget/ImportStatistics';
    }

    protected function getTemplateVariables(): array
    {
        return ['groups' => $this->dataProvider->getLatestStatisticsByType()];
    }
}
