<?php

use Filament\AiMonitor\Events\AiRequestLogged;
use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Services\AiKeyManager;
use Filament\AiMonitor\Services\AiPricingService;
use Filament\AiMonitor\Services\AiUsageLimitService;
use Illuminate\Support\Facades\Event;

function price(string $provider, ?string $model, float $in, float $out, array $extra = []): AiModelPricing
{
    return AiModelPricing::create($extra + [
        'provider' => $provider,
        'model' => $model,
        'input_per_1k' => $in,
        'output_per_1k' => $out,
        'active' => true,
    ]);
}

describe('pricing', function () {
    it('prefers an exact model match', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01);
        price('openai', 'gpt-4o-mini', 0.00015, 0.0006);

        expect(app(AiPricingService::class)->getPricing('openai', 'gpt-4o-mini'))
            ->toBe(['input_per_1k' => 0.00015, 'output_per_1k' => 0.0006]);
    });

    it('matches dated and tagged snapshots of a priced model', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01);
        price('openai', 'gpt-4o-mini', 0.00015, 0.0006);
        price('anthropic', 'claude-sonnet-4-6', 0.003, 0.015);
        price('anthropic', 'claude-opus-4', 0.015, 0.075);

        $pricing = app(AiPricingService::class);

        expect($pricing->getPricing('openai', 'gpt-4o-mini-2024-07-18')['input_per_1k'])->toBe(0.00015)
            ->and($pricing->getPricing('OpenAI', 'GPT-4o-2024-08-06')['input_per_1k'])->toBe(0.0025)
            ->and($pricing->getPricing('anthropic', 'claude-sonnet-4-6-20260101')['input_per_1k'])->toBe(0.003)
            ->and($pricing->getPricing('anthropic', 'claude-sonnet-4-6@20260101')['input_per_1k'])->toBe(0.003);
    });

    it('does not price a different model that only shares a prefix', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01);
        price('anthropic', 'claude-opus-4', 0.015, 0.075);

        $pricing = app(AiPricingService::class);

        expect($pricing->getPricing('openai', 'gpt-4o-mini'))->toBeNull()
            ->and($pricing->getPricing('anthropic', 'claude-opus-4-5'))->toBeNull();
    });

    it('falls back to the provider default, then the global fallback', function () {
        price('anthropic', null, 0.003, 0.015, ['is_default' => true]);
        price('any', null, 0.001, 0.002, ['is_fallback' => true]);

        $pricing = app(AiPricingService::class);

        expect($pricing->getPricing('anthropic', 'claude-unknown')['input_per_1k'])->toBe(0.003)
            ->and($pricing->getPricing('mistral', 'mistral-large')['input_per_1k'])->toBe(0.001);
    });

    it('returns null without pricing and ignores inactive rows', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01, ['active' => false]);

        expect(app(AiPricingService::class)->calculateCost('openai', 'gpt-4o', 1000, 1000))->toBeNull();
    });

    it('calculates cost', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01);

        expect(ai_cost('openai', 'gpt-4o', 1000, 500))->toBe(0.0075);
    });

    it('sees pricing changes made after the first lookup', function () {
        $pricing = app(AiPricingService::class);
        expect($pricing->hasPricing('openai', 'gpt-4o'))->toBeFalse();

        price('openai', 'gpt-4o', 0.0025, 0.01);

        expect($pricing->hasPricing('openai', 'gpt-4o'))->toBeTrue();
    });
});

describe('logging', function () {
    it('logs a request with an automatic cost and fires an event', function () {
        Event::fake([AiRequestLogged::class]);
        price('openai', 'gpt-4o', 0.0025, 0.01);

        $request = ai_log([
            'provider' => 'OpenAI',
            'model' => 'gpt-4o-2024-08-06',
            'prompt_tokens' => 1000,
            'completion_tokens' => 500,
        ]);

        expect($request->provider)->toBe('openai')
            ->and($request->total_tokens)->toBe(1500)
            ->and((float) $request->cost_usd)->toBe(0.0075)
            ->and($request->status)->toBe('success')
            ->and($request->meta['cost_source'])->toBe('auto')
            ->and($request->occurred_at)->not->toBeNull();

        Event::assertDispatched(AiRequestLogged::class, fn ($event) => $event->request->is($request));
    });

    it('marks requests without pricing and keeps manual costs', function () {
        $missing = ai_log(['provider' => 'openai', 'model' => 'gpt-x', 'prompt_tokens' => 10]);
        $manual = ai_log(['provider' => 'openai', 'model' => 'gpt-x', 'cost_usd' => 1.5]);

        expect($missing->cost_usd)->toBeNull()
            ->and($missing->meta)->toMatchArray(['cost_source' => 'missing', 'pricing_missing' => true])
            ->and((float) $manual->cost_usd)->toBe(1.5)
            ->and($manual->meta['cost_source'])->toBe('manual');
    });

    it('recalculates missing costs once pricing exists, leaving manual costs alone', function () {
        $missing = ai_log(['provider' => 'openai', 'model' => 'gpt-4o', 'prompt_tokens' => 1000, 'completion_tokens' => 0]);
        $manual = ai_log(['provider' => 'openai', 'model' => 'gpt-4o', 'prompt_tokens' => 1000, 'cost_usd' => 9]);

        price('openai', 'gpt-4o', 0.0025, 0.01);
        $pricing = app(AiPricingService::class);

        expect($pricing->recalculate($missing))->toBeTrue()
            ->and((float) $missing->fresh()->cost_usd)->toBe(0.0025)
            ->and($missing->fresh()->meta)->not->toHaveKey('pricing_missing')
            ->and($pricing->recalculate($manual))->toBeFalse()
            ->and((float) $manual->fresh()->cost_usd)->toBe(9.0);
    });
});

describe('prompt caching', function () {
    it('prices cache writes on top of the uncached input', function () {
        price('anthropic', 'claude-sonnet-5', 0.002, 0.01);

        // 2,013 uncached input, 22,568 cache-write and 134 output tokens at $2 / $10 per 1M:
        // 2,013 x $2 + 22,568 x $2 x 1.25 + 134 x $10 = $61,786 per 1M = $0.061786.
        expect(ai_cost('anthropic', 'claude-sonnet-5', 2013, 134, cacheWriteTokens: 22568))->toBe(0.061786)
            ->and(ai_cost('anthropic', 'claude-sonnet-5', 2013, 134))->toBe(0.005366);
    });

    it('prices cache reads and writes at the provider multipliers', function (string $provider) {
        price($provider, 'claude-sonnet-5', 0.002, 0.01);
        $pricing = app(AiPricingService::class);

        expect($pricing->calculateCost($provider, 'claude-sonnet-5', 0, 0, cacheReadTokens: 1_000_000))->toBe(0.2)
            ->and($pricing->calculateCost($provider, 'claude-sonnet-5', 0, 0, cacheWriteTokens: 1_000_000))->toBe(2.5);
    })->with(['anthropic', 'bedrock']);

    it('ignores cache tokens for providers without a multiplier', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01);

        expect(ai_cost('openai', 'gpt-4o', 1000, 500, 5000, 5000))->toBe(0.0075);
    });

    it('uses multipliers added in the config', function () {
        price('openai', 'gpt-4o', 0.0025, 0.01);
        config(['ai-monitor.cache_multipliers.openai' => ['read' => 0.5]]);

        expect(ai_cost('openai', 'gpt-4o', 0, 0, cacheReadTokens: 1000, cacheWriteTokens: 1000))->toBe(0.00125);
    });

    it('reads the cache tokens from the logged meta', function () {
        price('anthropic', 'claude-sonnet-5', 0.002, 0.01);

        $request = ai_log([
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-5',
            'prompt_tokens' => 2013,
            'completion_tokens' => 134,
            'meta' => ['cache_read_tokens' => 0, 'cache_write_tokens' => 22568],
        ]);

        expect((float) $request->cost_usd)->toBe(0.061786)
            ->and($request->meta)->toMatchArray(['cache_write_tokens' => 22568, 'cost_source' => 'auto']);
    });

    it('includes the cache tokens when recalculating', function () {
        $request = ai_log([
            'provider' => 'anthropic',
            'model' => 'claude-sonnet-5',
            'prompt_tokens' => 2013,
            'completion_tokens' => 134,
            'meta' => ['cache_write_tokens' => 22568],
        ]);

        price('anthropic', 'claude-sonnet-5', 0.002, 0.01);
        $this->artisan('ai-monitor:recalculate-costs')->assertSuccessful();

        expect((float) $request->fresh()->cost_usd)->toBe(0.061786);
    });
});

describe('keys', function () {
    it('returns the highest priority active key, encrypted at rest', function () {
        AiProviderApiKey::create(['provider' => 'openai', 'api_key' => 'sk-low', 'priority' => 5, 'active' => true]);
        AiProviderApiKey::create(['provider' => 'openai', 'api_key' => 'sk-high', 'priority' => 1, 'active' => true]);
        AiProviderApiKey::create(['provider' => 'openai', 'api_key' => 'sk-off', 'priority' => 0, 'active' => false]);

        expect(ai_key('OPENAI'))->toBe('sk-high')
            ->and(app(AiKeyManager::class)->getAllKeys('openai'))->toHaveCount(2)
            ->and(app(AiKeyManager::class)->hasProvider('gemini'))->toBeFalse()
            ->and(AiProviderApiKey::query()->toBase()->value('api_key'))->not->toContain('sk-');
    });
});

describe('limits', function () {
    it('reports monthly spend against the user limit', function () {
        $user = $this->createUser(['ai_monthly_limit_usd' => 10]);

        ai_log(['provider' => 'openai', 'model' => 'x', 'cost_usd' => 8.5, 'user_id' => $user->id]);
        ai_log(['provider' => 'openai', 'model' => 'x', 'cost_usd' => 5, 'user_id' => $user->id, 'occurred_at' => now()->subMonths(2)]);

        $status = app(AiUsageLimitService::class)->getUserLimitStatus($user);

        expect($status['spent'])->toBe(8.5)
            ->and($status['remaining'])->toBe(1.5)
            ->and($status['state'])->toBe('warning');
    });
});

it('scopes records to the resolved tenant', function () {
    \Filament\AiMonitor\Support\Tenancy::resolveUsing(fn () => 'team-a');
    ai_log(['provider' => 'openai', 'model' => 'x', 'cost_usd' => 1]);

    \Filament\AiMonitor\Support\Tenancy::resolveUsing(fn () => 'team-b');
    ai_log(['provider' => 'openai', 'model' => 'x', 'cost_usd' => 2]);

    expect(AiRequest::count())->toBe(1)
        ->and(AiRequest::first()->tenant_id)->toBe('team-b');

    config(['ai-monitor.tenant_support' => false]);

    expect(AiRequest::count())->toBe(2);
})->after(fn () => \Filament\AiMonitor\Support\Tenancy::resolveUsing(null));
