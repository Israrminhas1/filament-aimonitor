<?php

namespace Filament\AiMonitor\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Models\AiRequest;

class AiSetupAlertWidget extends Widget
{
    protected string $view = 'ai-monitor::filament.widgets.ai-setup-alert-widget';

    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $hasPricing = AiModelPricing::where('active', true)->exists();
        $hasApiKeys = AiProviderApiKey::where('active', true)->exists();
        $requestsMissingPricing = AiRequest::whereNull('cost_usd')->count();

        return [
            'hasPricing' => $hasPricing,
            'hasApiKeys' => $hasApiKeys,
            'requestsMissingPricing' => $requestsMissingPricing,
            'showWidget' => !$hasPricing || !$hasApiKeys || $requestsMissingPricing > 0,
        ];
    }
}
