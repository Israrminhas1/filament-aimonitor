# Filament AI Monitor

A Filament 4 plugin for monitoring AI API usage, costs, and managing API keys across multiple providers (OpenAI, Anthropic, Gemini, Perplexity).

## Features

- Track AI API requests with token counts and costs
- Manage API keys for multiple providers
- Configure model-specific pricing
- Dashboard with usage analytics and cost trends
- Per-user usage tracking
- Multi-tenancy support

## Requirements

- PHP 8.2+
- Laravel 11+
- Filament 4.0+

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

## Register the Plugin

Add the plugin to your Filament panel in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Filament\AiMonitor\AiMonitorPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            AiMonitorPlugin::make(),
        ]);
}
```

## Usage

### Recording AI Requests

```php
use Filament\AiMonitor\Facades\AiMonitor;

// Record an AI API request
AiMonitor::record([
    'provider' => 'openai',
    'model' => 'gpt-4o',
    'request_type' => 'chat',
    'prompt_tokens' => 150,
    'completion_tokens' => 50,
    'total_tokens' => 200,
    'status' => 'success',
    'user_id' => auth()->id(),
]);
```

### Getting API Keys

```php
use Filament\AiMonitor\Facades\AiMonitor;

// Get the highest priority active API key for a provider
$apiKey = AiMonitor::getApiKey('openai');
```

### Calculating Costs

Costs are automatically calculated based on your configured pricing when you record a request.

## Configuration

The config file (`config/ai-monitor.php`) allows you to:

- Set the user model for relationships
- Configure tenant column for multi-tenancy
- Customize table names

## License

MIT License. See [LICENSE](LICENSE) for details.
