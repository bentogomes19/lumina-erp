<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ \App\Support\AdministrativeDashboardAccess::administrativeLabelFor(auth()->user()) }}
            </p>

            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Os indicadores e atalhos exibidos nesta página seguem as permissões do seu perfil administrativo.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
