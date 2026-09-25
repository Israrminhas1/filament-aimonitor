<?php

use Filament\AiMonitor\Filament\Pages\AiMonitorDashboard;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages\CreateAiModelPricing;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource\Pages\ListAiModelPricings;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource\Pages\CreateAiProviderApiKey;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource\Pages\EditAiProviderApiKey;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages\ListAiRequests;
use Filament\AiMonitor\Filament\Resources\AiRequestResource\Pages\ViewAiRequest;
use Filament\AiMonitor\Filament\Widgets\AiCostTrendsChartWidget;
use Filament\AiMonitor\Filament\Widgets\AiProviderBreakdownWidget;
use Filament\AiMonitor\Filament\Widgets\AiRecentRequestsWidget;
use Filament\AiMonitor\Filament\Widgets\AiSetupAlertWidget;
use Filament\AiMonitor\Filament\Widgets\AiStatsOverviewWidget;
use Filament\AiMonitor\Filament\Widgets\AiTopModelsWidget;
use Filament\AiMonitor\Filament\Widgets\AiUserUsageWidget;
use Filament\AiMonitor\Models\AiModelPricing;
use Filament\AiMonitor\Models\AiProviderApiKey;
use Filament\AiMonitor\Models\AiRequest;
use Filament\AiMonitor\Support\Tenancy;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = $this->createUser(['name' => 'Ada Lovelace']);
    $this->actingAs($this->user);
});

function seedRequests($user): void
{
    AiModelPricing::create(['provider' => 'openai', 'model' => 'gpt-4o', 'input_per_1k' => 0.0025, 'output_per_1k' => 0.01, 'active' => true]);

    ai_log(['provider' => 'openai', 'model' => 'gpt-4o', 'prompt_tokens' => 1000, 'completion_tokens' => 500, 'user_id' => $user->id]);
    ai_log(['provider' => 'openai', 'model' => 'gpt-4o-mini', 'prompt_tokens' => 200, 'completion_tokens' => 100, 'status' => 'failed']);
    ai_log(['provider' => 'anthropic', 'model' => 'claude-sonnet-5', 'prompt_tokens' => 100, 'cost_usd' => 0.5, 'occurred_at' => now()->subDays(20)]);
    ai_log(['provider' => 'gemini', 'model' => 'gemini-2.5-pro', 'prompt_tokens' => 100, 'cost_usd' => 0.1, 'occurred_at' => now()->subDays(45)]);
}

describe('dashboard', function () {
    it('shows the welcome state before any data exists', function () {
        $this->get(AiMonitorDashboard::getUrl())
            ->assertOk()
            ->assertSee('Welcome to AI Monitor')
            ->assertSee(AiProviderApiKeyResource::getUrl('create'), escape: false);
    });

    it('renders all widgets once data exists', function () {
        seedRequests($this->user);

        $this->get(AiMonitorDashboard::getUrl())
            ->assertOk()
            ->assertSee('AI Usage &amp; Cost Monitor', escape: false)
            ->assertDontSee('Welcome to AI Monitor');
    });

    it('lives at the same URL as before', function () {
        expect(AiMonitorDashboard::getUrl())->toEndWith('/console/aimonitor');
    });

    it('uses the navigation group from the plugin', function () {
        expect(AiMonitorDashboard::getNavigationGroup())->toBe('AI Monitor')
            ->and(AiRequestResource::getNavigationGroup())->toBe('AI Monitor');

        \Filament\AiMonitor\AiMonitorPlugin::get()->navigationGroup('Observability')->navigationSort(10);

        expect(AiModelPricingResource::getNavigationGroup())->toBe('Observability')
            ->and(AiModelPricingResource::getNavigationSort())->toBe(13);
    });
});

describe('widgets', function () {
    beforeEach(fn () => seedRequests($this->user));

    it('renders every widget with default and custom filters', function (string $widget) {
        livewire($widget)->assertOk();
        livewire($widget, ['pageFilters' => ['period' => 7, 'provider' => 'openai']])->assertOk();
        livewire($widget, ['pageFilters' => ['period' => 365, 'provider' => null]])->assertOk();
    })->with([
        AiStatsOverviewWidget::class,
        AiCostTrendsChartWidget::class,
        AiProviderBreakdownWidget::class,
        AiTopModelsWidget::class,
        AiUserUsageWidget::class,
        AiRecentRequestsWidget::class,
        AiSetupAlertWidget::class,
    ]);

    it('applies the period filter to stats', function () {
        $totalRequests = fn (array $filters) => (fn () => $this->getStats()[0]->getValue())
            ->call(livewire(AiStatsOverviewWidget::class, ['pageFilters' => $filters])->instance());

        expect($totalRequests(['period' => 7]))->toBe('2')
            ->and($totalRequests(['period' => 30]))->toBe('3')
            ->and($totalRequests(['period' => 90]))->toBe('4')
            ->and($totalRequests(['period' => 90, 'provider' => 'anthropic']))->toBe('1');
    });

    it('aggregates top models and user usage', function () {
        livewire(AiTopModelsWidget::class)
            ->assertSee(['gpt-4o', 'claude-sonnet-5'])
            ->assertDontSee('gemini-2.5-pro');

        livewire(AiUserUsageWidget::class)
            ->assertSee(['Ada Lovelace', 'System']);
    });

    it('aggregates without SQL errors when tenant scoping is active', function () {
        Tenancy::resolveUsing(fn () => 'team-1');
        ai_log(['provider' => 'openai', 'model' => 'gpt-4o', 'prompt_tokens' => 10, 'user_id' => $this->user->id]);

        livewire(AiTopModelsWidget::class)->assertOk()->assertSee('gpt-4o');
        livewire(AiUserUsageWidget::class)->assertOk()->assertSee('Ada Lovelace');
        livewire(AiStatsOverviewWidget::class)->assertOk();
    })->after(fn () => Tenancy::resolveUsing(null));

    it('shows a missing pricing notice', function () {
        AiProviderApiKey::create(['provider' => 'openai', 'api_key' => 'sk-test', 'priority' => 1, 'active' => true]);

        livewire(AiSetupAlertWidget::class)->assertSee('1 request missing pricing');
    });
});

describe('requests resource', function () {
    beforeEach(fn () => seedRequests($this->user));

    it('lists, filters and views requests', function () {
        $failed = AiRequest::where('status', 'failed')->first();

        livewire(ListAiRequests::class)
            ->assertCanSeeTableRecords(AiRequest::all())
            ->filterTable('status', ['failed'])
            ->assertCanSeeTableRecords([$failed])
            ->assertCountTableRecords(1);

        livewire(ListAiRequests::class)
            ->filterTable('cost_missing', true)
            ->assertCountTableRecords(1);

        $this->get(AiRequestResource::getUrl('view', ['record' => $failed]))
            ->assertOk()
            ->assertSee(['gpt-4o-mini', 'Token Usage', 'Metadata']);
    });

    it('recalculates missing costs', function () {
        AiModelPricing::create(['provider' => 'openai', 'model' => 'gpt-4o-mini', 'input_per_1k' => 0.001, 'output_per_1k' => 0.002, 'active' => true]);

        livewire(ListAiRequests::class)
            ->callAction('recalculateMissingCosts')
            ->assertNotified();

        expect(AiRequest::query()->missingCost()->count())->toBe(0);
    });

    it('cannot create requests', function () {
        expect(AiRequestResource::canCreate())->toBeFalse();
    });
});

describe('pricing resource', function () {
    it('creates pricing with a lowercased provider', function () {
        livewire(CreateAiModelPricing::class)
            ->fillForm([
                'provider' => ' OpenAI ',
                'model' => 'gpt-5',
                'input_per_1k' => 0.00125,
                'output_per_1k' => 0.01,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(AiModelPricing::first())
            ->provider->toBe('openai')
            ->model->toBe('gpt-5');
    });

    it('lists pricing', function () {
        $row = AiModelPricing::create(['provider' => 'openai', 'model' => 'gpt-5', 'input_per_1k' => 0.00125, 'output_per_1k' => 0.01, 'active' => true]);

        livewire(ListAiModelPricings::class)
            ->assertCanSeeTableRecords([$row])
            ->assertSee('$1.25 / 1M');
    });
});

describe('api keys resource', function () {
    it('creates a key', function () {
        livewire(CreateAiProviderApiKey::class)
            ->fillForm(['provider' => 'Anthropic', 'api_key' => 'sk-ant-secret-1234', 'priority' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        expect(ai_key('anthropic'))->toBe('sk-ant-secret-1234');
    });

    it('never sends the stored key to the browser and keeps it when left empty', function () {
        $key = AiProviderApiKey::create(['provider' => 'openai', 'api_key' => 'sk-super-secret-9876', 'priority' => 1, 'active' => true]);

        livewire(EditAiProviderApiKey::class, ['record' => $key->getRouteKey()])
            ->assertDontSee('sk-super-secret-9876')
            ->assertSchemaStateSet(['api_key' => null])
            ->fillForm(['key_name' => 'Production'])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($key->fresh())
            ->api_key->toBe('sk-super-secret-9876')
            ->key_name->toBe('Production');
    });

    it('masks keys in the table', function () {
        AiProviderApiKey::create(['provider' => 'openai', 'api_key' => 'sk-super-secret-9876', 'priority' => 1, 'active' => true]);

        $this->get(AiProviderApiKeyResource::getUrl())
            ->assertOk()
            ->assertSee('••••••••9876')
            ->assertDontSee('sk-super-secret');
    });
});

describe('commands', function () {
    it('seeds pricing and respects --force', function () {
        $this->artisan('ai-monitor:setup-pricing')->assertSuccessful();
        $count = AiModelPricing::count();

        expect($count)->toBeGreaterThan(10)
            ->and(ai_cost('anthropic', 'claude-sonnet-5', 1_000_000, 0))->toBe(2.0);

        AiModelPricing::where('model', 'claude-sonnet-5')->update(['input_per_1k' => 1]);
        $this->artisan('ai-monitor:setup-pricing')->assertSuccessful();
        expect(AiModelPricing::where('model', 'claude-sonnet-5')->value('input_per_1k'))->toEqual(1);

        $this->artisan('ai-monitor:setup-pricing --force')->assertSuccessful();
        expect(AiModelPricing::count())->toBe($count)
            ->and(AiModelPricing::where('model', 'claude-sonnet-5')->value('input_per_1k'))->toEqual(0.002);
    });

    it('recalculates missing costs', function () {
        ai_log(['provider' => 'openai', 'model' => 'gpt-4o-2024-08-06', 'prompt_tokens' => 1000]);

        $this->artisan('ai-monitor:setup-pricing')->assertSuccessful();
        $this->artisan('ai-monitor:recalculate-costs')->assertSuccessful();

        expect((float) AiRequest::first()->cost_usd)->toBe(0.0025);
    });
});
