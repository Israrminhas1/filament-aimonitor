<?php

use Filament\AiMonitor\Services\AiKeyManager;
use Filament\AiMonitor\Services\AiPricingService;
use Filament\AiMonitor\Services\AiUsageLogger;

if (!function_exists('ai_key')) {
    function ai_key(string $provider): ?string
    {
        return app(AiKeyManager::class)->getKey($provider);
    }
}

if (!function_exists('ai_log')) {
    function ai_log(array $data)
    {
        return app(AiUsageLogger::class)->log($data);
    }
}

if (!function_exists('ai_cost')) {
    function ai_cost(string $provider, ?string $model, ?int $promptTokens, ?int $completionTokens): ?float
    {
        return app(AiPricingService::class)->calculateCost($provider, $model, $promptTokens, $completionTokens);
    }
}
