<x-filament-panels::page>
    @if($this->showDashboard)
        @livewire(\Filament\AiMonitor\Filament\Widgets\AiSetupAlertWidget::class)

        <x-filament-widgets::widgets
            :widgets="$this->getVisibleWidgets()"
            :columns="$this->getColumns()"
        />
    @else
        <x-filament::section>
            <div class="text-center py-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    Welcome to AI Monitor
                </h2>

                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Get started by adding your API keys to begin tracking your AI usage and costs.
                </p>

                <div class="flex justify-center gap-3">
                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.ai-provider-api-keys.create') }}"
                        icon="heroicon-o-key"
                    >
                        Add API Key
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        href="{{ route('filament.admin.resources.ai-model-pricings.create') }}"
                        icon="heroicon-o-currency-dollar"
                        color="gray"
                    >
                        Configure Pricing
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
