<?php

namespace Filament\AiMonitor\Services;

use Carbon\Carbon;
use Filament\AiMonitor\Events\AiRequestLogged;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Tenancy;

class AiUsageLogger
{
    public function __construct(
        protected AiPricingService $pricingService
    ) {}

    public function log(array $data): AiRequest
    {
        if (isset($data['provider'])) {
            $data['provider'] = strtolower($data['provider']);
        }

        if (! isset($data['total_tokens'])) {
            $data['total_tokens'] = ($data['prompt_tokens'] ?? 0) + ($data['completion_tokens'] ?? 0);
        }

        if (! isset($data['meta']) || ! is_array($data['meta'])) {
            $data['meta'] = [];
        }

        if (! isset($data['cost_usd'])) {
            $calculatedCost = $this->pricingService->calculateCost(
                $data['provider'] ?? 'unknown',
                $data['model'] ?? null,
                $data['prompt_tokens'] ?? 0,
                $data['completion_tokens'] ?? 0,
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

        $data['occurred_at'] ??= Carbon::now();
        $data['status'] ??= 'success';

        if (empty($data['tenant_id']) && ($tenantId = Tenancy::currentId()) !== null) {
            $data['tenant_id'] = $tenantId;
        }

        $request = AiRequest::create($data);

        AiRequestLogged::dispatch($request);

        return $request;
    }

    public function logOpenAi(array $data): AiRequest
    {
        return $this->log(['provider' => 'openai'] + $data);
    }

    public function logAnthropic(array $data): AiRequest
    {
        return $this->log(['provider' => 'anthropic'] + $data);
    }

    public function logGemini(array $data): AiRequest
    {
        return $this->log(['provider' => 'gemini'] + $data);
    }

    public function logPerplexity(array $data): AiRequest
    {
        return $this->log(['provider' => 'perplexity'] + $data);
    }
}
