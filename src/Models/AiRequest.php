<?php

namespace Filament\AiMonitor\Models;

use Carbon\CarbonInterface;
use Filament\AiMonitor\Models\Traits\IsTenantScoped;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

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
        return $this->belongsTo(config('ai-monitor.user_model', 'App\\Models\\User'));
    }

    public function scopeOccurredBetween(Builder $query, CarbonInterface $from, ?CarbonInterface $until = null): Builder
    {
        $query->where($this->qualifyColumn('occurred_at'), '>=', $from);

        if ($until) {
            $query->where($this->qualifyColumn('occurred_at'), '<', $until);
        }

        return $query;
    }

    public function scopeMissingCost(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('cost_usd'));
    }

    /**
     * SQL expression that truncates `occurred_at` to a date on the current driver.
     */
    public static function dateExpression(?string $connection = null): string
    {
        $driver = DB::connection($connection ?? (new static)->getConnectionName())->getDriverName();

        return $driver === 'sqlsrv' ? 'CAST(occurred_at AS date)' : 'DATE(occurred_at)';
    }
}
