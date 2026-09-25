# Filament AI Monitor

A Filament plugin (v4 and v5) for monitoring AI API usage, costs, and managing API keys across multiple providers (OpenAI, Anthropic, Gemini, Perplexity).

## Features

- Track AI API requests with token counts and automatic cost calculation
- Manage encrypted API keys for multiple providers with priority-based rotation
- Configure model-specific pricing with provider defaults, a global fallback, and automatic matching of dated model snapshots (`gpt-4o-2024-08-06` → `gpt-4o`)
- Dashboard with period (7 / 30 / 90 / 365 days) and provider filters, usage analytics, and cost trends
- Recalculate costs for past requests after adding or changing pricing
- Per-user spending tracking and limits
- Multi-tenancy support (`tenant()` helper, Filament tenancy, or a custom resolver)
- `AiRequestLogged` event for alerts, budgets, and integrations

## Requirements

- PHP 8.2+
- Laravel 11.28+ (tested up to Laravel 13)
- Filament 4.x (Livewire 3) or Filament 5.x (Livewire 4)

## Installation

```bash
composer require israrminhas/filament-aimonitor
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag="ai-monitor-migrations"
php artisan migrate
```

Publish config (optional):

```bash
php artisan vendor:publish --tag="ai-monitor-config"
```

Import current list prices for popular OpenAI, Anthropic, Gemini and Perplexity models (optional):

```bash
php artisan ai-monitor:setup-pricing          # skips models you already priced
php artisan ai-monitor:setup-pricing --force  # overwrite with the bundled prices
```

> Provider prices change often. Check the imported values against each provider's pricing page.

### Upgrading from the Filament 4 version

No database changes are needed. Update the package, then run `php artisan filament:upgrade`. If you had published the package's views, delete `resources/views/vendor/ai-monitor` and publish them again, since `ai-monitor-dashboard.blade.php`, `ai-top-models-widget.blade.php` and `ai-user-usage-widget.blade.php` were removed.

## Register the Plugin

Add the plugin to your Filament panel in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Filament\AiMonitor\AiMonitorPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            AiMonitorPlugin::make(),
        ]);
}
```

The plugin works on any panel ID and path; links are generated for the panel it is registered on.

### Plugin Options

```php
AiMonitorPlugin::make()
    ->navigationGroup('Observability') // null to ungroup; defaults to config('ai-monitor.navigation_group')
    ->navigationSort(10)               // offset added to the sort of every AI Monitor nav item
    ->dashboard()                      // pass false to hide any of these
    ->requestsResource()
    ->pricingResource()
    ->apiKeysResource();
```

### Styling

The plugin uses only Filament's built-in components and classes, so no custom theme or Tailwind `@source` entry is required.

---

## Helper Functions

The package provides three global helper functions:

### `ai_log()` - Log AI Requests

```php
// Log any AI request
ai_log([
    'provider' => 'openai',
    'model' => 'gpt-4o',
    'request_type' => 'chat',
    'prompt_tokens' => 150,
    'completion_tokens' => 50,
    'status' => 'success',
    'user_id' => auth()->id(),
]);
```

Cost is **automatically calculated** from your pricing configuration. The `total_tokens` and `occurred_at` are auto-filled if not provided.

### `ai_key()` - Get API Key

```php
// Get the highest priority active API key for a provider
$apiKey = ai_key('openai');
$apiKey = ai_key('anthropic');
$apiKey = ai_key('gemini');
```

### `ai_cost()` - Calculate Cost

```php
// Calculate cost for tokens without logging
$cost = ai_cost('openai', 'gpt-4o', 1000, 500);
// Returns cost in USD based on your pricing config
```

---

## Using the Services

### AiUsageLogger

```php
use Filament\AiMonitor\Services\AiUsageLogger;

$logger = app(AiUsageLogger::class);

// Generic log
$logger->log([
    'provider' => 'openai',
    'model' => 'gpt-4o',
    'prompt_tokens' => 100,
    'completion_tokens' => 50,
    'status' => 'success',
    'user_id' => auth()->id(),
    'meta' => ['conversation_id' => 123],
]);

// Provider-specific shortcuts
$logger->logOpenAi([...]);
$logger->logAnthropic([...]);
$logger->logGemini([...]);
$logger->logPerplexity([...]);
```

### AiKeyManager

```php
use Filament\AiMonitor\Services\AiKeyManager;

$keyManager = app(AiKeyManager::class);

// Get single key (highest priority)
$key = $keyManager->getKey('openai');

// Get all active keys for a provider
$keys = $keyManager->getAllKeys('openai');

// Check if provider has any active keys
if ($keyManager->hasProvider('anthropic')) {
    // ...
}
```

### AiPricingService

```php
use Filament\AiMonitor\Services\AiPricingService;

$pricing = app(AiPricingService::class);

// Get pricing for a model
$rates = $pricing->getPricing('openai', 'gpt-4o');
// Returns: ['input_per_1k' => 0.005, 'output_per_1k' => 0.015]

// Check if pricing exists
if ($pricing->hasPricing('anthropic', 'claude-sonnet-5')) {
    // ...
}

// Calculate cost
$cost = $pricing->calculateCost('openai', 'gpt-4o', 1000, 500);

// Get all configured providers
$providers = $pricing->getProviders();

// Get models for a provider
$models = $pricing->getModelsForProvider('openai');
```

### AiUsageLimitService

```php
use Filament\AiMonitor\Services\AiUsageLimitService;

$limitService = app(AiUsageLimitService::class);

// Get user's monthly spend
$spent = $limitService->getUserMonthlySpend($userId);

// Get full limit status
$status = $limitService->getUserLimitStatus($user);
// Returns:
// [
//     'limit' => 100.00,
//     'spent' => 45.50,
//     'remaining' => 54.50,
//     'percent_used' => 45.5,
//     'state' => 'ok', // 'ok', 'warning', 'over', 'no-limit'
// ]
```

---

## Usage Examples

### OpenAI Integration

```php
use OpenAI\Laravel\Facades\OpenAI;

$response = OpenAI::chat()->create([
    'model' => 'gpt-4o',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello!'],
    ],
]);

// Log the request
ai_log([
    'provider' => 'openai',
    'model' => $response->model,
    'request_type' => 'chat',
    'prompt_tokens' => $response->usage->promptTokens,
    'completion_tokens' => $response->usage->completionTokens,
    'status' => 'success',
    'user_id' => auth()->id(),
]);
```

### Anthropic Integration

```php
$response = Http::withHeaders([
    'x-api-key' => ai_key('anthropic'),
    'anthropic-version' => '2023-06-01',
])->post('https://api.anthropic.com/v1/messages', [
    'model' => 'claude-3-5-sonnet-20241022',
    'max_tokens' => 1024,
    'messages' => [['role' => 'user', 'content' => 'Hello!']],
]);

$data = $response->json();

ai_log([
    'provider' => 'anthropic',
    'model' => $data['model'],
    'request_type' => 'chat',
    'prompt_tokens' => $data['usage']['input_tokens'],
    'completion_tokens' => $data['usage']['output_tokens'],
    'status' => $response->successful() ? 'success' : 'failed',
    'user_id' => auth()->id(),
]);
```

### With Error Handling

```php
try {
    $response = OpenAI::chat()->create([...]);

    ai_log([
        'provider' => 'openai',
        'model' => 'gpt-4o',
        'prompt_tokens' => $response->usage->promptTokens,
        'completion_tokens' => $response->usage->completionTokens,
        'status' => 'success',
        'user_id' => auth()->id(),
    ]);
} catch (\Exception $e) {
    ai_log([
        'provider' => 'openai',
        'model' => 'gpt-4o',
        'prompt_tokens' => 0,
        'completion_tokens' => 0,
        'status' => 'failed',
        'user_id' => auth()->id(),
        'meta' => ['error' => $e->getMessage()],
    ]);
}
```

---

## User Spending Limits

### Add Columns to Users Table

```bash
php artisan make:migration add_ai_limits_to_users_table
```

```php
Schema::table('users', function (Blueprint $table) {
    $table->decimal('ai_monthly_limit_usd', 10, 4)->nullable();
    $table->integer('ai_alert_threshold_percent')->default(80);
});
```

### Check Limits Before AI Calls

```php
use Filament\AiMonitor\Services\AiUsageLimitService;

$limitService = app(AiUsageLimitService::class);
$status = $limitService->getUserLimitStatus(auth()->user());

if ($status['state'] === 'over') {
    throw new \Exception('Monthly AI spending limit reached.');
}

if ($status['state'] === 'warning') {
    // Notify user they're approaching limit
}
```

---

## Pricing Resolution

When a request is logged, its cost is calculated from the first match below:

1. **Exact model**: a row whose model equals the request's model (case-insensitive).
2. **Snapshot of a priced model**: `gpt-4o-2024-08-06`, `claude-sonnet-4-6-20260101`, `claude-3-5-sonnet-latest`, `gemini-2.5-pro-preview-05-06` and `model@20241022` use the row for the base model. A different model that just shares a prefix (`gpt-4o-mini` vs `gpt-4o`) does **not** match.
3. **Provider default**: the provider's row with *Provider default* enabled.
4. **Global fallback**: the row with *Global fallback* enabled.

If nothing matches, the request is stored with `cost_usd = null` and flagged on the dashboard.

### Recalculating Costs

Added or changed pricing after requests were logged? Recalculate them:

- **From the panel**: *AI Requests → Recalculate missing costs*, or select requests and use the *Recalculate cost* bulk action.
- **From the CLI**:

```bash
php artisan ai-monitor:recalculate-costs                       # only requests missing a cost
php artisan ai-monitor:recalculate-costs --all --since=2026-01-01
php artisan ai-monitor:recalculate-costs --all --include-manual  # also overwrite manually logged costs
```

---

## Events

Every call to `ai_log()` / `AiUsageLogger::log()` dispatches `Filament\AiMonitor\Events\AiRequestLogged`, which you can use for budget alerts, Slack notifications, and so on:

```php
use Filament\AiMonitor\Events\AiRequestLogged;
use Filament\AiMonitor\Services\AiUsageLimitService;
use Illuminate\Support\Facades\Event;

Event::listen(function (AiRequestLogged $event) {
    if ($user = $event->request->user) {
        $status = app(AiUsageLimitService::class)->getUserLimitStatus($user);

        if ($status['state'] === 'over') {
            // notify...
        }
    }
});
```

---

## Multi-Tenancy Support

When `tenant_support` is enabled, every AI Monitor model is scoped to the current tenant, and `tenant_id` is filled automatically on create. The current tenant is resolved from, in order:

1. A custom resolver, if you register one.
2. The global `tenant()` helper (e.g. [stancl/tenancy](https://tenancyforlaravel.com)).
3. The current Filament panel tenant (`Filament::getTenant()`).

```php
// config/ai-monitor.php
'tenant_support' => true,
```

```php
// AppServiceProvider::boot()
use Filament\AiMonitor\Support\Tenancy;

Tenancy::resolveUsing(fn () => auth()->user()?->team_id);
```

The plugin's resources set `$isScopedToTenant = false`, so they work on Filament panels with tenancy without needing an ownership relationship on the plugin's models.

---

## Dashboard

The dashboard (`/{panel}/aimonitor`) has a **period** filter (7 days, 30 days, 90 days, 12 months) and a **provider** filter that apply to every widget. The filters are kept in the URL and session.

| Widget | Description |
|--------|-------------|
| Setup Alert | Shown when pricing or API keys are missing, or requests have no cost |
| Stats Overview | Requests, cost, tokens and success rate, with change vs the previous period |
| Cost & Request Trends | Daily cost and request line chart |
| Cost by Provider | Doughnut chart breakdown |
| Top Models by Cost | Five most expensive models |
| Usage by User | Five highest-spending users |
| Recent Requests | Latest calls, linking to the request detail page |

The widgets also work on your own dashboards; without page filters they show the last 30 days.

---

## Testing

```bash
composer install
composer test
```

The test suite runs against whichever Filament version is installed. CI runs it on Filament 4 and 5.

---

## License

MIT License. See [LICENSE](LICENSE) for details.
