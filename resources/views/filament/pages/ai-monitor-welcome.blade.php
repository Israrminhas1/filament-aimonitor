<x-filament::empty-state icon="heroicon-o-cpu-chip" heading="Welcome to AI Monitor">
    <x-slot name="description">
        Add an API key or log your first request with <code>ai_log([...])</code> to start tracking AI usage and costs.
    </x-slot>

    @if ($createApiKeyUrl || $createPricingUrl)
        <x-slot name="footer">
            <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 0.75rem;">
                @if ($createApiKeyUrl)
                    <x-filament::button tag="a" :href="$createApiKeyUrl" icon="heroicon-o-key">
                        Add API key
                    </x-filament::button>
                @endif

                @if ($createPricingUrl)
                    <x-filament::button tag="a" :href="$createPricingUrl" icon="heroicon-o-currency-dollar" color="gray">
                        Configure pricing
                    </x-filament::button>
                @endif
            </div>
        </x-slot>
    @endif
</x-filament::empty-state>
