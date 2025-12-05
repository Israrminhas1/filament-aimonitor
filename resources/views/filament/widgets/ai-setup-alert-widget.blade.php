<x-filament-widgets::widget>
    @if(!$hasPricing || !$hasApiKeys)
        <x-filament::section>
            <div class="flex items-center gap-3">
                <span class="text-warning-500 text-xl">⚠</span>
                <div class="flex-1">
                    <h3 class="text-base font-semibold text-warning-600 dark:text-warning-400">Setup Required</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        @if(!$hasPricing)
                            <a href="{{ route('filament.admin.resources.ai-model-pricings.create') }}" class="underline font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500">Add model pricing</a>
                        @endif
                        @if(!$hasPricing && !$hasApiKeys) and @endif
                        @if(!$hasApiKeys)
                            <a href="{{ route('filament.admin.resources.ai-provider-api-keys.create') }}" class="underline font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500">add API keys</a>
                        @endif
                        to start tracking costs.
                    </p>
                </div>
            </div>
        </x-filament::section>
    @elseif($requestsMissingPricing > 0)
        <x-filament::section>
            <div class="flex items-center gap-3">
                <span class="text-warning-500 text-lg">ℹ</span>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <strong class="text-gray-900 dark:text-white">{{ number_format($requestsMissingPricing) }}</strong> requests are missing pricing data.
                        <a href="{{ route('filament.admin.resources.ai-model-pricings.index') }}" class="underline font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500">Configure pricing</a>
                    </p>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
