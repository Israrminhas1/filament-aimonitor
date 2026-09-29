<?php

namespace Filament\AiMonitor\Support;

class PricingUnit
{
    public const TOKENS = 'tokens';

    public const IMAGES = 'images';

    public const SECONDS = 'seconds';

    public const REQUESTS = 'requests';

    /**
     * A missing unit, for example before the migration has run, means per token.
     */
    public static function isPerToken(?string $unit): bool
    {
        return blank($unit) || $unit === self::TOKENS;
    }

    /**
     * What one price applies to: "1K tokens", "image", "second", "request".
     */
    public static function per(?string $unit): string
    {
        return match ($unit) {
            self::IMAGES => 'image',
            self::SECONDS => 'second',
            self::REQUESTS => 'request',
            null, '', self::TOKENS => '1K tokens',
            default => $unit,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::TOKENS => 'Tokens (price per 1K)',
            self::IMAGES => 'Images (price per image)',
            self::SECONDS => 'Seconds (price per second)',
            self::REQUESTS => 'Requests (price per request)',
        ];
    }
}
