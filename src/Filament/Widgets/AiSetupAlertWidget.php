<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\AiMonitor\AiMonitorPlugin;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Models\AiRequest;
use Filament\Widgets\Widget;

class AiSetupAlertWidget extends Widget
{
    protected string $view = 'ai-monitor::filament.widgets.ai-setup-alert-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return ! AiModelPricing::query()->where('active', true)->exists()
            || ! AiProviderApiKey::query()->where('active', true)->exists()
            || AiRequest::query()->missingCost()->exists();
    }

    protected function getViewData(): array
    {
        return [
            'hasPricing' => AiModelPricing::query()->where('active', true)->exists(),
            'hasApiKeys' => AiProviderApiKey::query()->where('active', true)->exists(),
            'requestsMissingPricing' => AiRequest::query()->missingCost()->count(),
            'createPricingUrl' => AiMonitorPlugin::resourceUrl(AiModelPricingResource::class, 'create'),
            'createApiKeyUrl' => AiMonitorPlugin::resourceUrl(AiProviderApiKeyResource::class, 'create'),
            'missingPricingUrl' => AiMonitorPlugin::resourceUrl(AiRequestResource::class, 'index', ['filters' => ['cost_missing' => ['value' => 1]]]),
        ];
    }
}
