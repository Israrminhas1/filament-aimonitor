<?php

namespace Filament\AiMonitor;

use Filament\AiMonitor\Filament\Pages\AiMonitorDashboard;
use Filament\AiMonitor\Filament\Resources\AiModelPricingResource;
use Filament\AiMonitor\Filament\Resources\AiProviderApiKeyResource;
use Filament\AiMonitor\Filament\Resources\AiRequestResource;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Throwable;

class AiMonitorPlugin implements Plugin
{
    protected bool $hasRequestsResource = true;

    protected bool $hasPricingResource = true;

    protected bool $hasApiKeysResource = true;

    protected bool $hasDashboard = true;

    protected ?string $navigationGroup;

    protected ?int $navigationSort = null;

    public function __construct()
    {
        $this->navigationGroup = config('ai-monitor.navigation_group', 'AI Monitor');
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }

    /**
     * The plugin instance registered on the current panel, if any.
     */
    public static function current(): ?static
    {
        try {
            $panel = Filament::getCurrentOrDefaultPanel();
        } catch (Throwable) {
            return null;
        }

        $id = app(static::class)->getId();

        return $panel?->hasPlugin($id) ? $panel->getPlugin($id) : null;
    }

    /**
     * URL to a page of one of the plugin's resources on the current panel,
     * or null when that resource is not registered there.
     */
    public static function resourceUrl(string $resource, string $page = 'index', array $parameters = []): ?string
    {
        try {
            return $resource::getUrl($page, $parameters);
        } catch (Throwable) {
            return null;
        }
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

    /**
     * Offset added to the sort order of every AI Monitor navigation item.
     */
    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
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
