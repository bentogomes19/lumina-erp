<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $currentYear = $this->getCurrentSchoolYear();
            $events = $this->getCalendarEvents();
        @endphp

        @if($currentYear)
            {{-- Informações do Ano Letivo --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Ano Letivo {{ $currentYear->year }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Início</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $currentYear->starts_at?->format('d/m/Y') ?? '-' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Término</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $currentYear->ends_at?->format('d/m/Y') ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Calendário de Eventos --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                    Eventos do Calendário Acadêmico
                </h3>

                <div class="space-y-4">
                    @foreach($events as $event)
                        @php
                            $isPast = $event['date'] && $event['date']->isPast();
                            $typeColors = [
                                'start' => 'border-green-500 bg-green-50 dark:bg-green-900/20',
                                'end' => 'border-red-500 bg-red-50 dark:bg-red-900/20',
                                'term-end' => 'border-blue-500 bg-blue-50 dark:bg-blue-900/20',
                                'holiday' => 'border-purple-500 bg-purple-50 dark:bg-purple-900/20',
                            ];
                            $borderColor = $typeColors[$event['type']] ?? 'border-gray-300 bg-gray-50 dark:bg-gray-700';
                        @endphp

                        <div class="border-l-4 {{ $borderColor }} p-4 rounded-r-lg {{ $isPast ? 'opacity-60' : '' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $event['title'] }}
                                        </h4>
                                        @if($isPast)
                                            <span class="text-xs bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 px-2 py-1 rounded">
                                                Concluído
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                        {{ $event['description'] }}
                                    </p>
                                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                        <x-heroicon-o-calendar class="w-4 h-4" />
                                        <span>{{ $event['date']?->format('d/m/Y') ?? '-' }}</span>
                                        
                                        @if(!$isPast && $event['date'])
                                            @php
                                                $daysUntil = now()->diffInDays($event['date'], false);
                                            @endphp
                                            @if($daysUntil >= 0)
                                                <span class="ml-2 text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded">
                                                    @if($daysUntil == 0)
                                                        Hoje
                                                    @elseif($daysUntil == 1)
                                                        Amanhã
                                                    @else
                                                        Em {{ $daysUntil }} dias
                                                    @endif
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Informação sobre Bimestres --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach(['1º Bimestre', '2º Bimestre', '3º Bimestre', '4º Bimestre'] as $index => $bimester)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border-t-4 border-blue-500">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $bimester }}</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            @if($currentYear->starts_at)
                                {{ $currentYear->starts_at->addMonths($index * 2)->format('M/Y') }} - 
                                {{ $currentYear->starts_at->addMonths(($index * 2) + 2)->format('M/Y') }}
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 text-yellow-500 mx-auto mb-3" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    Nenhum Ano Letivo Ativo
                </h3>
                <p class="text-gray-600 dark:text-gray-400">
                    Não há informações de calendário acadêmico disponíveis no momento.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
