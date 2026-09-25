<?php

namespace Filament\AiMonitor\Support;

class Provider
{
    /**
     * Human-readable labels for well-known providers.
     */
    public const LABELS = [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'gemini' => 'Gemini',
        'google' => 'Google',
        'perplexity' => 'Perplexity',
        'mistral' => 'Mistral',
        'cohere' => 'Cohere',
        'deepseek' => 'DeepSeek',
        'xai' => 'xAI',
        'groq' => 'Groq',
    ];

    /**
     * Filament color names used for badges.
     */
    public const BADGE_COLORS = [
        'openai' => 'success',
        'anthropic' => 'warning',
        'gemini' => 'info',
        'google' => 'info',
        'perplexity' => 'danger',
    ];

    /**
     * Hex colors used in charts.
     */
    public const CHART_COLORS = [
        'openai' => '#10b981',
        'anthropic' => '#f59e0b',
        'gemini' => '#3b82f6',
        'google' => '#3b82f6',
        'perplexity' => '#8b5cf6',
        'cohere' => '#ec4899',
        'mistral' => '#14b8a6',
        'deepseek' => '#6366f1',
        'xai' => '#64748b',
        'groq' => '#ef4444',
    ];

    public static function label(?string $provider): string
    {
        if (blank($provider)) {
            return '—';
        }

        return static::LABELS[strtolower($provider)] ?? ucfirst($provider);
    }

    public static function color(?string $provider): string
    {
        return static::BADGE_COLORS[strtolower((string) $provider)] ?? 'gray';
    }

    public static function chartColor(?string $provider): string
    {
        return static::CHART_COLORS[strtolower((string) $provider)] ?? '#6b7280';
    }

    /**
     * @param  array<string>  $providers
     * @return array<string, string>
     */
    public static function options(array $providers = []): array
    {
        $providers = array_unique(array_merge(['openai', 'anthropic', 'gemini', 'perplexity'], $providers));
        sort($providers);

        return collect($providers)
            ->filter()
            ->mapWithKeys(fn (string $provider) => [$provider => static::label($provider)])
            ->all();
    }
}
