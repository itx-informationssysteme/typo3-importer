<?php

declare(strict_types=1);

namespace Itx\Importer\Widgets;

use Itx\Importer\Dashboard\ImportDashboardDataProvider;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;

abstract class AbstractImportWidget implements WidgetInterface, RequestAwareWidgetInterface
{
    protected ServerRequestInterface $request;

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        protected readonly WidgetConfigurationInterface $configuration,
        protected readonly ImportDashboardDataProvider $dataProvider,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly array $options = []
    ) {}

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-dashboard', 'itx/importer']);
        $view->assignMultiple([
            'configuration' => $this->configuration,
            'options' => $this->options,
        ] + $this->getTemplateVariables());

        return $view->render($this->getTemplateName());
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    abstract protected function getTemplateName(): string;

    /**
     * @return array<string,mixed>
     */
    abstract protected function getTemplateVariables(): array;
}
