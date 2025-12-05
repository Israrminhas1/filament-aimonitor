<?php

namespace Filament\AiMonitor;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AiMonitorServiceProvider extends PackageServiceProvider
{
    public static string $name = 'ai-monitor';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_ai_requests_table',
                'create_ai_model_pricings_table',
                'create_ai_provider_api_keys_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(Services\AiPricingService::class);
        $this->app->singleton(Services\AiKeyManager::class);
        $this->app->singleton(Services\AiUsageLogger::class, function ($app) {
            return new Services\AiUsageLogger($app->make(Services\AiPricingService::class));
        });
        $this->app->singleton(Services\AiUsageLimitService::class);
    }

    public function packageBooted(): void
    {
        // Register Livewire components
        Livewire::component('ai-stats-overview-widget', Filament\Widgets\AiStatsOverviewWidget::class);
        Livewire::component('ai-cost-trends-chart-widget', Filament\Widgets\AiCostTrendsChartWidget::class);
        Livewire::component('ai-provider-breakdown-widget', Filament\Widgets\AiProviderBreakdownWidget::class);
        Livewire::component('ai-top-models-widget', Filament\Widgets\AiTopModelsWidget::class);
        Livewire::component('ai-user-usage-widget', Filament\Widgets\AiUserUsageWidget::class);
        Livewire::component('ai-recent-requests-widget', Filament\Widgets\AiRecentRequestsWidget::class);
        Livewire::component('ai-setup-alert-widget', Filament\Widgets\AiSetupAlertWidget::class);
    }
}
