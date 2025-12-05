<?php

namespace Filament\AiMonitor\Models\Traits;

use Filament\AiMonitor\Models\Scopes\TenantScope;

trait IsTenantScoped
{
    public static function bootIsTenantScoped(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (function_exists('tenant') && tenant()) {
                if (! isset($model->tenant_id)) {
                    $model->tenant_id = tenant()->id;
                }
            }
        });
    }

    public function initializeIsTenantScoped(): void
    {
        if (property_exists($this, 'fillable') && ! in_array('tenant_id', $this->fillable, true)) {
            $this->fillable[] = 'tenant_id';
        }
    }
}
