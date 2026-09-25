<?php

namespace Filament\AiMonitor\Filament\Pages;

use Filament\AiMonitor\AiMonitorPlugin;
use Filament\AiMonitor\Filament\Concerns\HasAiMonitorNavigation;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;
use Filament\AiMonitor\Filament\Widgets\AiCostTrendsChartWidget;
use Filament\AiMonitor\Filament\Widgets\AiProviderBreakdownWidget;
use Filament\AiMonitor\Filament\Widgets\AiRecentRequestsWidget;
use Filament\AiMonitor\Filament\Widgets\AiSetupAlertWidget;
use Filament\AiMonitor\Filament\Widgets\AiStatsOverviewWidget;
use Filament\AiMonitor\Filament\Widgets\AiTopModelsWidget;
use Filament\AiMonitor\Filament\Widgets\AiUserUsageWidget;
use Filament\AiMonitor\Filament\Widgets\Concerns\InteractsWithAiMonitorFilters;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Provider;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Panel;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class AiMonitorDashboard extends Dashboard
{
    use HasAiMonitorNavigation;
    use HasFiltersForm;

    protected static string $routePath = '/aimonitor';

    protected static int $aiMonitorNavigationSort = 1;

    public bool $showDashboard = false;

    public function mount(): void
    {
        $this->showDashboard = AiRequest::query()->exists()
            || AiProviderApiKey::query()->where('active', true)->exists();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationLabel(): string
    {
        return 'Dashboard';
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return 'aimonitor';
    }

    public function getTitle(): string | Htmlable
    {
        return 'AI Monitor';
    }

    public function getHeading(): string | Htmlable
    {
        return 'AI Usage & Cost Monitor';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Track your AI API usage, costs, and performance';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('period')
                    ->label('Period')
                    ->options(InteractsWithAiMonitorFilters::getPeriodOptions())
                    ->default(30)
                    ->selectablePlaceholder(false),

                Select::make('provider')
                    ->label('Provider')
                    ->placeholder('All providers')
                    ->options(fn (): array => Provider::options(
                        AiRequest::query()->distinct()->pluck('provider')->all()
                    )),
            ])
            ->columns(['md' => 2, 'xl' => 4]);
    }

    public function content(Schema $schema): Schema
    {
        if (! $this->showDashboard) {
            return $schema->components([
                View::make('ai-monitor::filament.pages.ai-monitor-welcome')
                    ->viewData([
                        'createApiKeyUrl' => AiMonitorPlugin::resourceUrl(AiProviderApiKeyResource::class, 'create'),
                        'createPricingUrl' => AiMonitorPlugin::resourceUrl(AiModelPricingResource::class, 'create'),
                    ]),
            ]);
        }

        return parent::content($schema);
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

    public function getColumns(): int | array
    {
        return ['md' => 2, 'xl' => 3];
    }
}
