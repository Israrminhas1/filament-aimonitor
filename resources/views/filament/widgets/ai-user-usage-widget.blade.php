<x-filament-widgets::widget>
    <x-filament::section heading="Usage by User" icon="heroicon-o-users">
        @php
            $users = $this->getUsers();
            $maxCost = collect($users)->max('total_cost') ?: 1;
        @endphp

        <div class="space-y-4">
            @foreach($users as $user)
                @php
                    $initials = strtoupper(substr($user['user_name'], 0, 2));
                    $percentage = ($user['total_cost'] / $maxCost) * 100;
                @endphp
                <div class="relative">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/50">
                                <span class="text-xs font-bold text-primary-600 dark:text-primary-400">
                                    {{ $initials }}
                                </span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-900 dark:text-white text-sm">
                                    {{ $user['user_name'] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                    {{ number_format($user['requests_count']) }} requests
                                </span>
                            </div>
                        </div>
                        <span class="font-semibold text-warning-600 dark:text-warning-400">
                            ${{ number_format($user['total_cost'] ?? 0, 4) }}
                        </span>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 ml-11">
                        <div
                            class="bg-gradient-to-r from-primary-500 to-primary-600 h-2 rounded-full transition-all duration-500"
                            style="width: {{ $percentage }}%"
                        ></div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
