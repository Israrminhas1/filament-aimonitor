<?php

namespace Filament\AiMonitor\Services;

use Illuminate\Support\Collection;
use Filament\AiMonitor\Models\AiProviderApiKey;

class AiKeyManager
{
    public function getKey(string $provider): ?string
    {
        $provider = strtolower($provider);

        $key = AiProviderApiKey::provider($provider)
            ->active()
            ->orderBy('priority')
            ->first();

        return $key?->api_key;
    }

    public function getAllKeys(string $provider): Collection
    {
        $provider = strtolower($provider);

        return AiProviderApiKey::provider($provider)
            ->active()
            ->orderBy('priority')
            ->get();
    }

    public function hasProvider(string $provider): bool
    {
        $provider = strtolower($provider);

        return AiProviderApiKey::provider($provider)
            ->active()
            ->exists();
    }
}
