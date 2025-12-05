<x-filament-widgets::widget>
    <x-filament::section heading="Top Models by Cost" icon="heroicon-o-cpu-chip">
        @php
            $models = $this->getModels();
            $maxCost = collect($models)->max('total_cost') ?: 1;
        @endphp

        <div class="space-y-4">
            @foreach($models as $model)
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <x-filament::badge
                                :color="match($model['provider']) {
                                    'openai' => 'success',
                                    'anthropic' => 'info',
                                    'gemini' => 'warning',
                                    'perplexity' => 'danger',
                                    default => 'gray',
                                }"
                                size="sm"
                            >
                                {{ ucfirst($model['provider']) }}
                            </x-filament::badge>
                            <span class="font-medium text-gray-900 dark:text-white text-sm">
                                {{ $model['model'] }}
                            </span>
                        </div>
                        <span class="font-semibold text-warning-600 dark:text-warning-400">
                            ${{ number_format($model['total_cost'] ?? 0, 4) }}
                        </span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div
                            class="bg-gradient-to-r from-primary-500 to-primary-600 h-2 rounded-full transition-all duration-500"
                            style="width: {{ ($model['total_cost'] / $maxCost) * 100 }}%"
                        ></div>
                    </div>

                    <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ number_format($model['requests_count']) }} requests</span>
                        <span>{{ number_format($model['tokens_sum'] ?? 0) }} tokens</span>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
