<x-filament-widgets::widget>
    @if (! $hasPricing || ! $hasApiKeys)
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <x-slot name="heading">Setup required</x-slot>

            <x-slot name="description">
                @if (! $hasPricing && ! $hasApiKeys)
                    Add model pricing and an API key to start tracking costs.
                @elseif (! $hasPricing)
                    Add model pricing so request costs can be calculated.
                    Tip: run <code>php artisan ai-monitor:setup-pricing</code> to import current prices.
                @else
                    Add an API key so <code>ai_key()</code> can hand keys to your app.
                @endif
            </x-slot>

            <div style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                @if (! $hasPricing && $createPricingUrl)
                    <x-filament::button tag="a" :href="$createPricingUrl" icon="heroicon-o-currency-dollar">
                        Add model pricing
                    </x-filament::button>
                @endif

                @if (! $hasApiKeys && $createApiKeyUrl)
                    <x-filament::button tag="a" :href="$createApiKeyUrl" icon="heroicon-o-key" color="gray">
                        Add API key
                    </x-filament::button>
                @endif
            </div>
        </x-filament::section>
    @elseif ($requestsMissingPricing > 0)
        <x-filament::section icon="heroicon-o-information-circle" icon-color="warning" compact>
            <x-slot name="heading">
                {{ number_format($requestsMissingPricing) }} {{ \Illuminate\Support\Str::plural('request', $requestsMissingPricing) }} missing pricing
            </x-slot>

            <x-slot name="description">
                These requests used a model with no matching price. Add pricing, then recalculate their cost.
            </x-slot>

            @if ($missingPricingUrl)
                <x-slot name="afterHeader">
                    <x-filament::button tag="a" :href="$missingPricingUrl" size="sm" color="gray">
                        Review requests
                    </x-filament::button>
                </x-slot>
            @endif
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
