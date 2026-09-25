<?php

namespace Filament\AiMonitor\Filament\Concerns;

use Filament\AiMonitor\AiMonitorPlugin;

/**
 * Shared navigation settings for AI Monitor pages and resources.
 * Classes using this trait define `$aiMonitorNavigationSort`.
 */
trait HasAiMonitorNavigation
{
    public static function getNavigationGroup(): ?string
    {
        if ($plugin = AiMonitorPlugin::current()) {
            return $plugin->getNavigationGroup();
        }

        return config('ai-monitor.navigation_group', 'AI Monitor');
    }

    public static function getNavigationSort(): ?int
    {
        return (AiMonitorPlugin::current()?->getNavigationSort() ?? 0) + static::$aiMonitorNavigationSort;
    }
}
