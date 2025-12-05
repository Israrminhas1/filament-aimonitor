<?php

namespace Filament\AiMonitor\Filament\Pages;

use Filament\Pages\Page;
use Filament\AiMonitor\Filament\Widgets\AiCostTrendsChartWidget;
use Filament\AiMonitor\Filament\Widgets\AiProviderBreakdownWidget;
use Filament\AiMonitor\Filament\Widgets\AiRecentRequestsWidget;
use Filament\AiMonitor\Filament\Widgets\AiSetupAlertWidget;
use Filament\AiMonitor\Filament\Widgets\AiStatsOverviewWidget;
use Filament\AiMonitor\Filament\Widgets\AiTopModelsWidget;
use Filament\AiMonitor\Filament\Widgets\AiUserUsageWidget;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Models\AiRequest;

class AiMonitorDashboard extends Page
{
    protected string $view = 'ai-monitor::filament.pages.ai-monitor-dashboard';

    public bool $hasApiKeys = false;
    public bool $hasData = false;
    public bool $showDashboard = false;

    public function mount(): void
    {
        $this->hasApiKeys = AiProviderApiKey::where('active', true)->exists();
        $this->hasData = AiRequest::exists();
        $this->showDashboard = $this->hasApiKeys || $this->hasData;
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'AI Monitor';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        return 'aimonitor';
    }

    public function getTitle(): string
    {
        return 'AI Monitor';
    }

    public function getHeading(): string
    {
        return 'AI Usage & Cost Monitor';
    }

    public function getSubheading(): ?string
    {
        return 'Track your AI API usage, costs, and performance';
    }

    public function getWidgets(): array
    {
        return [
            AiSetupAlertWidget::class,
            AiStatsOverviewWidget::class,
            AiCostTrendsChartWidget::class,
            AiProviderBreakdownWidget::class,
            AiTopModelsWidget::class,
            AiUserUsageWidget::class,
            AiRecentRequestsWidget::class,
        ];
    }

    public function getVisibleWidgets(): array
    {
        return $this->filterVisibleWidgets($this->getWidgets());
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }
}
