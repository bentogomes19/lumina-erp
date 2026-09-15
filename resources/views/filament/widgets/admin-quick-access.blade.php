<x-filament-widgets::widget>
    <x-filament::section
        heading="Acesso rápido"
        description="Atalhos para as tarefas mais frequentes da secretaria."
        icon="fas-bolt"
        :collapsible="true"
        :persist-collapsed="true"
    >
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 2xl:grid-cols-4">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-sm dark:border-white/10 dark:bg-white/5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-{{ $action['color'] }}-50 text-{{ $action['color'] }}-600 dark:bg-{{ $action['color'] }}-500/10 dark:text-{{ $action['color'] }}-400">
                        <x-filament::icon :icon="$action['icon']" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $action['label'] }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $action['description'] }}</span>
                    </span>
                    <x-filament::icon icon="fas-arrow-right" class="ml-auto h-4 w-4 text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-primary-600" />
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
