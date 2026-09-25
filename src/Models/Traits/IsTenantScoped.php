<?php

namespace Filament\AiMonitor\Models\Traits;

use Filament\AiMonitor\Models\Scopes\TenantScope;
use Filament\AiMonitor\Support\Tenancy;

trait IsTenantScoped
{
    public static function bootIsTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (isset($model->tenant_id)) {
                return;
            }

            if (($tenantId = Tenancy::currentId()) !== null) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function initializeIsTenantScoped(): void
    {
        if (! in_array('tenant_id', $this->fillable, true)) {
            $this->fillable[] = 'tenant_id';
        }
    }
}
