<?php

namespace Filament\AiMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\AiMonitor\Models\Traits\IsTenantScoped;

class AiModelPricing extends Model
{
    use IsTenantScoped;

    protected $fillable = [
        'provider',
        'model',
        'input_per_1k',
        'output_per_1k',
        'is_default',
        'is_fallback',
        'active',
        'tenant_id',
    ];

    protected $casts = [
        'input_per_1k' => 'float',
        'output_per_1k' => 'float',
        'is_default' => 'bool',
        'is_fallback' => 'bool',
        'active' => 'bool',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
