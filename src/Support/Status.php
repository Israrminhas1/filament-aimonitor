<?php

namespace Filament\AiMonitor\Support;

class Status
{
    public const SUCCESS = 'success';

    public const FAILED = 'failed';

    public const RATE_LIMITED = 'rate_limited';

    /**
     * Statuses that count as failures. `error` is accepted for backwards compatibility.
     */
    public const FAILURES = ['failed', 'error'];

    public static function color(?string $status): string
    {
        return match ($status) {
            self::SUCCESS => 'success',
            self::RATE_LIMITED => 'warning',
            'failed', 'error' => 'danger',
            default => 'gray',
        };
    }

    public static function label(?string $status): string
    {
        return match ($status) {
            self::RATE_LIMITED => 'Rate Limited',
            null, '' => '—',
            default => ucfirst($status),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::SUCCESS => 'Success',
            self::FAILED => 'Failed',
            'error' => 'Error',
            self::RATE_LIMITED => 'Rate Limited',
        ];
    }
}
