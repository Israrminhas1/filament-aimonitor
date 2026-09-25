<?php

namespace Filament\AiMonitor\Models;

use Filament\AiMonitor\Models\Traits\IsTenantScoped;
use Filament\AiMonitor\Services\AiPricingService;
use Illuminate\Database\Eloquent\Model;

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

    protected static function booted(): void
    {
        $flush = fn () => app(AiPricingService::class)->flushCache();

        static::saved($flush);
        static::deleted($flush);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
