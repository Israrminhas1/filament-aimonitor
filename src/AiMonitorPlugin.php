<?php

namespace Filament\AiMonitor;

use Filament\AiMonitor\Filament\Pages\AiMonitorDashboard;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class AiMonitorPlugin implements Plugin
{
    protected bool $hasRequestsResource = true;
    protected bool $hasPricingResource = true;
    protected bool $hasApiKeysResource = true;
    protected bool $hasDashboard = true;
    protected ?string $navigationGroup = 'AI Monitor';

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'ai-monitor';
    }

    public function requestsResource(bool $condition = true): static
    {
        $this->hasRequestsResource = $condition;
        return $this;
    }

    public function pricingResource(bool $condition = true): static
    {
        $this->hasPricingResource = $condition;
        return $this;
    }

    public function apiKeysResource(bool $condition = true): static
    {
        $this->hasApiKeysResource = $condition;
        return $this;
    }

    public function dashboard(bool $condition = true): static
    {
        $this->hasDashboard = $condition;
        return $this;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function hasRequestsResource(): bool
    {
        return $this->hasRequestsResource;
    }

    public function hasPricingResource(): bool
    {
        return $this->hasPricingResource;
    }

    public function hasApiKeysResource(): bool
    {
        return $this->hasApiKeysResource;
    }

    public function hasDashboard(): bool
    {
        return $this->hasDashboard;
    }

    public function register(Panel $panel): void
    {
        $resources = [];
        $pages = [];

        if ($this->hasRequestsResource) {
            $resources[] = AiRequestResource::class;
        }

        if ($this->hasPricingResource) {
            $resources[] = AiModelPricingResource::class;
        }

        if ($this->hasApiKeysResource) {
            $resources[] = AiProviderApiKeyResource::class;
        }

        if ($this->hasDashboard) {
            $pages[] = AiMonitorDashboard::class;
        }

        $panel
            ->resources($resources)
            ->pages($pages);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
