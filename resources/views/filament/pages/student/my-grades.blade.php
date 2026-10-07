<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $currentClass = $data['currentClass'];
    @endphp

    <div class="space-y-6">
        @if(!$data['student'] || !$currentClass)
            <x-filament::section>
                <x-slot name="heading">Nenhuma turma ativa encontrada</x-slot>
                Suas notas aparecerão aqui quando houver uma matrícula no ano letivo vigente.
            </x-filament::section>
        @else
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p><strong class="text-gray-950 dark:text-white">{{ $currentClass->name }}</strong> <span>· Ano letivo {{ $currentClass->schoolYear?->year }}</span></p>
                <p>Consulte as médias e abra os detalhes de cada disciplina para ver as avaliações.</p>
            </div>

            <div class="flex flex-wrap gap-2" role="group" aria-label="Período das notas">
                @foreach(\App\Filament\Pages\Student\MyGrades::PERIODS as $key => $label)
                    <x-filament::button
                        :color="$this->selectedPeriod === $key ? 'primary' : 'gray'"
                        :outlined="$this->selectedPeriod !== $key"
                        wire:click="setPeriod('{{ $key }}')"
                        wire:loading.attr="disabled"
                        wire:target="setPeriod"
                        :aria-pressed="$this->selectedPeriod === $key ? 'true' : 'false'"
                        size="sm"
                    >{{ $label }}</x-filament::button>
                @endforeach
            </div>

            <div wire:loading.class="opacity-50" wire:target="setPeriod">
                {{ $this->table }}
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400" role="status" aria-live="polite">
                {{ $data['period_label'] }} · {{ $data['stats']['total'] }} disciplinas.
                @if($data['stats']['ongoing'] > 0)
                    {{ $data['stats']['ongoing'] }} sem nota neste período.
                @endif
                * Média final e situação consideram as notas já lançadas; antes do fechamento dos quatro bimestres, os resultados são parciais. “Abaixo da média” indica necessidade de atenção, não uma reprovação definitiva.
            </p>
        @endif
    </div>
</x-filament-panels::page>
