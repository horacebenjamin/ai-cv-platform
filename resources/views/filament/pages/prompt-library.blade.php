<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        @foreach ($this->getPrompts() as $prompt)
            <x-filament::section>
                <x-slot name="heading">{{ $prompt['name'] }}</x-slot>
                <x-slot name="description">{{ $prompt['description'] }}</x-slot>

                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Provider</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $prompt['provider'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Model</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ $prompt['model'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500 dark:text-gray-400">Status</dt>
                        <dd>
                            <x-filament::badge color="gray">{{ $prompt['status'] }}</x-filament::badge>
                        </dd>
                    </div>
                </dl>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
