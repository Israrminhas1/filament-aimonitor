<?php

namespace Filament\AiMonitor\Services;

use Carbon\Carbon;
use Filament\AiMonitor\Models\AiRequest;

class AiUsageLogger
{
    public function __construct(
        protected AiPricingService $pricingService
    ) {}

    public function log(array $data): AiRequest
    {
        if (!isset($data['total_tokens'])) {
            $promptTokens = $data['prompt_tokens'] ?? 0;
            $completionTokens = $data['completion_tokens'] ?? 0;
            $data['total_tokens'] = $promptTokens + $completionTokens;
        }

        if (!isset($data['meta']) || !is_array($data['meta'])) {
            $data['meta'] = [];
        }

        if (!isset($data['cost_usd']) || $data['cost_usd'] === null) {
            $provider = $data['provider'] ?? 'unknown';
            $model = $data['model'] ?? null;
            $promptTokens = $data['prompt_tokens'] ?? 0;
            $completionTokens = $data['completion_tokens'] ?? 0;

            $calculatedCost = $this->pricingService->calculateCost(
                $provider,
                $model,
                $promptTokens,
                $completionTokens
            );

            if ($calculatedCost === null) {
                $data['cost_usd'] = null;
                $data['meta']['cost_source'] = 'missing';
                $data['meta']['pricing_missing'] = true;
            } else {
                $data['cost_usd'] = $calculatedCost;
                $data['meta']['cost_source'] = 'auto';
            }
        } else {
            $data['meta']['cost_source'] = 'manual';
        }

        if (!isset($data['occurred_at'])) {
            $data['occurred_at'] = Carbon::now();
        }

        if (!isset($data['status'])) {
            $data['status'] = 'success';
        }

        if (function_exists('tenant') && tenant() && empty($data['tenant_id'])) {
            $data['tenant_id'] = tenant()->id;
        }

        return AiRequest::create($data);
    }

    public function logOpenAi(array $data): AiRequest
    {
        $data['provider'] = 'openai';
        return $this->log($data);
    }

    public function logAnthropic(array $data): AiRequest
    {
        $data['provider'] = 'anthropic';
        return $this->log($data);
    }

    public function logGemini(array $data): AiRequest
    {
        $data['provider'] = 'gemini';
        return $this->log($data);
    }

    public function logPerplexity(array $data): AiRequest
    {
        $data['provider'] = 'perplexity';
        return $this->log($data);
    }
}
