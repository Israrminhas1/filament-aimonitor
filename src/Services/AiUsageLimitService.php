<?php

namespace Filament\AiMonitor\Services;

use Carbon\Carbon;
use Filament\AiMonitor\Models\AiRequest;

class AiUsageLimitService
{
    public function getUserMonthlySpend(int $userId, ?Carbon $month = null): float
    {
        $month = $month ?? now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        return AiRequest::query()
            ->where('user_id', $userId)
            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
            ->sum('cost_usd') ?? 0.0;
    }

    public function getUserLimitStatus($user): array
    {
        $limit = $user->ai_monthly_limit_usd ?? null;
        $spent = $this->getUserMonthlySpend($user->id);

        if ($limit === null || $limit <= 0) {
            return [
                'limit' => null,
                'spent' => $spent,
                'remaining' => null,
                'percent_used' => null,
                'state' => 'no-limit',
            ];
        }

        $remaining = $limit - $spent;
        $percentUsed = ($spent / $limit) * 100;

        $alertThreshold = $user->ai_alert_threshold_percent ?? 80;

        if ($percentUsed >= 100) {
            $state = 'over';
        } elseif ($percentUsed >= $alertThreshold) {
            $state = 'warning';
        } else {
            $state = 'ok';
        }

        return [
            'limit' => (float) $limit,
            'spent' => $spent,
            'remaining' => $remaining,
            'percent_used' => round($percentUsed, 2),
            'state' => $state,
        ];
    }
}
