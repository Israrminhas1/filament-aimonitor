<?php

namespace Filament\AiMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\AiMonitor\Models\Traits\IsTenantScoped;

class AiProviderApiKey extends Model
{
    use IsTenantScoped;

    protected $fillable = [
        'provider',
        'key_name',
        'api_key',
        'priority',
        'active',
        'tenant_id',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'priority' => 'integer',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', strtolower($provider));
    }
}
