<?php

namespace Filament\AiMonitor\Support;

use Closure;
use Filament\Facades\Filament;
use Throwable;

class Tenancy
{
    protected static ?Closure $resolver = null;

    /**
     * Override how the current tenant ID is resolved, e.g. in a service provider:
     *
     *     Tenancy::resolveUsing(fn () => auth()->user()?->team_id);
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        static::$resolver = $resolver;
    }

    public static function enabled(): bool
    {
        return (bool) config('ai-monitor.tenant_support', true);
    }

    public static function currentId(): int | string | null
    {
        if (! static::enabled()) {
            return null;
        }

        if (static::$resolver) {
            return (static::$resolver)();
        }

        // stancl/tenancy and similar packages expose a global tenant() helper.
        if (function_exists('tenant') && ($tenant = tenant())) {
            return method_exists($tenant, 'getTenantKey') ? $tenant->getTenantKey() : $tenant->id;
        }

        try {
            return Filament::getTenant()?->getKey();
        } catch (Throwable) {
            return null;
        }
    }
}
