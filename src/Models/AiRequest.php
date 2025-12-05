<?php

namespace Filament\AiMonitor\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Filament\AiMonitor\Models\Traits\IsTenantScoped;

class AiRequest extends Model
{
    use IsTenantScoped;

    protected $fillable = [
        'provider',
        'model',
        'request_type',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'status',
        'http_code',
        'user_id',
        'tenant_id',
        'meta',
        'occurred_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'occurred_at' => 'datetime',
        'cost_usd' => 'decimal:8',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'http_code' => 'integer',
    ];

    public function user(): BelongsTo
    {
        $userModel = config('ai-monitor.user_model', 'App\\Models\\User');
        return $this->belongsTo($userModel);
    }
}
