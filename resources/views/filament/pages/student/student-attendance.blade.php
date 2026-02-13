<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Estatísticas de Frequência --}}
        @php
            $stats = $this->getAttendanceStats();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Taxa de Presença</p>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['rate'] }}%</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-heroicon-o-check-circle class="w-8 h-8 text-green-600 dark:text-green-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Presenças</p>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['present'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-heroicon-o-calendar-days class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Faltas</p>
                        <p class="text-3xl font-bold {{ $stats['absent'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $stats['absent'] }}
                        </p>
                    </div>
                    <div class="p-3 {{ $stats['absent'] > 10 ? 'bg-red-100 dark:bg-red-900' : 'bg-gray-100 dark:bg-gray-700' }} rounded-full">
                        <x-heroicon-o-x-circle class="w-8 h-8 {{ $stats['absent'] > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}" />
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Atrasos</p>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['late'] }}</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <x-heroicon-o-clock class="w-8 h-8 text-yellow-600 dark:text-yellow-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Alerta se a taxa de presença estiver baixa --}}
        @if($stats['rate'] < 75)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-yellow-400" />
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 dark:text-yellow-200">
                            <strong>Atenção:</strong> Sua taxa de presença está abaixo de 75%. É importante manter uma frequência adequada para seu aproveitamento acadêmico.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabela de frequência --}}
        <div>
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
