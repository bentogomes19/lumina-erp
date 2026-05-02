<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $lessons = $data['lessons'];
        $filters = $data['filters'];
        $isOnLeave = $data['isOnLeave'];

        $weekDays = ['Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira', 'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira', 'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado', 'Sunday' => 'Domingo'];

        $grouped = $lessons->groupBy(fn ($l) => $l->date->format('l'));
    @endphp

    <style>
        .ts-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: end;
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem;
            box-shadow: var(--lumina-shadow);
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }

        .ts-filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 10rem;
            flex: 1;
        }

        .ts-filter-group label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--ms-text-secondary);
        }

        .ts-filter-group select {
            padding: 0.45rem 0.65rem;
            border: 1px solid var(--ms-card-border);
            border-radius: 0.375rem;
            background: var(--ms-cell-bg);
            color: var(--ms-text-primary);
            font-size: 0.8125rem;
        }

        .ts-filter-reset {
            padding: 0.45rem 0.85rem;
            border: 1px solid var(--ms-card-border);
            border-radius: 0.375rem;
            background: var(--ms-cell-bg);
            color: var(--ms-text-secondary);
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }

        .ts-filter-reset:hover {
            background: var(--lumina-primary-soft, #f0f9ff);
            color: var(--lumina-primary, #0f766e);
        }

        .ts-alert {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 0.625rem;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .ts-alert--warning {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
        }

        .ts-alert svg {
            width: 1.25rem;
            height: 1.25rem;
            flex-shrink: 0;
        }

        .ts-day-section {
            margin-bottom: 1.25rem;
        }

        .ts-day-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1rem;
            background: var(--lumina-primary-soft, #f0f9ff);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem 0.625rem 0 0;
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--lumina-primary, #0f766e);
        }

        .ts-day-header svg {
            width: 1rem;
            height: 1rem;
        }

        .ts-lessons-list {
            border: 1px solid var(--ms-card-border);
            border-top: none;
            border-radius: 0 0 0.625rem 0.625rem;
            overflow: hidden;
        }

        .ts-lesson-row {
            display: grid;
            grid-template-columns: 7rem 1fr 1fr auto auto auto;
            gap: 1rem;
            align-items: center;
            padding: 0.85rem 1.25rem;
            background: var(--ms-card-bg);
            border-bottom: 1px solid var(--ms-card-border);
            font-size: 0.8125rem;
            color: var(--ms-text-secondary);
        }

        .ts-lesson-row:last-child {
            border-bottom: none;
        }

        .ts-lesson-time {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 700;
            color: var(--ms-text-primary);
            white-space: nowrap;
        }

        .ts-lesson-time svg {
            width: 0.875rem;
            height: 0.875rem;
            opacity: 0.5;
        }

        .ts-lesson-class {
            font-weight: 600;
            color: var(--ms-text-primary);
        }

        .ts-lesson-subject {
            color: var(--ms-text-secondary);
        }

        .ts-lesson-room,
        .ts-lesson-shift,
        .ts-lesson-year {
            font-size: 0.75rem;
            color: var(--ms-text-secondary);
        }

        .ts-empty {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem;
            box-shadow: var(--lumina-shadow);
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .ts-empty svg {
            width: 3rem;
            height: 3rem;
            color: var(--ms-text-secondary);
            opacity: 0.4;
            margin: 0 auto 1rem;
        }

        .ts-empty h3 {
            margin: 0 0 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--ms-text-primary);
        }

        .ts-empty p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--ms-text-secondary);
        }

        @media (max-width: 768px) {
            .ts-lesson-row {
                grid-template-columns: 1fr;
                gap: 0.35rem;
            }
        }
    </style>

    {{-- Alerta de afastamento --}}
    @if($isOnLeave)
        <div class="ts-alert ts-alert--warning">
            <x-filament::icon icon="fas-triangle-exclamation" class="w-5 h-5" />
            <span>Você está atualmente afastado(a). Sua agenda pode não refletir aulas ativas.</span>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="ts-filters">
        <div class="ts-filter-group">
            <label>Período Letivo</label>
            <select wire:model.live="filterSchoolYear">
                <option value="">Todos</option>
                @foreach($filters['schoolYears'] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ts-filter-group">
            <label>Turma</label>
            <select wire:model.live="filterClass">
                <option value="">Todas</option>
                @foreach($filters['classes'] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ts-filter-group">
            <label>Disciplina</label>
            <select wire:model.live="filterSubject">
                <option value="">Todas</option>
                @foreach($filters['subjects'] as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="ts-filter-group">
            <label>Turno</label>
            <select wire:model.live="filterShift">
                <option value="">Todos</option>
                @foreach($filters['shifts'] as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button wire:click="resetFilters" class="ts-filter-reset" type="button">
            Limpar filtros
        </button>
    </div>

    {{-- Agenda --}}
    @if($lessons->isEmpty())
        <div class="ts-empty">
            <x-filament::icon icon="fas-calendar-days" class="w-12 h-12" />
            <h3>Nenhuma aula encontrada</h3>
            <p>Não há aulas registradas na sua agenda para os filtros selecionados.</p>
        </div>
    @else
        @foreach($weekDays as $dayEn => $dayPt)
            @if(isset($grouped[$dayEn]) && $grouped[$dayEn]->isNotEmpty())
                <div class="ts-day-section">
                    <div class="ts-day-header">
                        <x-filament::icon icon="fas-calendar-day" class="w-4 h-4" />
                        {{ $dayPt }}
                    </div>
                    <div class="ts-lessons-list">
                        @foreach($grouped[$dayEn] as $lesson)
                            @php
                                $class = $lesson->schoolClass;
                                $subject = $lesson->subject;
                                $schoolYear = $lesson->schoolYear ?? $class?->schoolYear;
                                $shiftLabel = $class?->shift?->label() ?? '—';
                            @endphp
                            <div class="ts-lesson-row">
                                <div class="ts-lesson-time">
                                    <x-filament::icon icon="fas-clock" class="w-4 h-4" />
                                    {{ $lesson->start_time?->format('H:i') ?? '—' }} – {{ $lesson->end_time?->format('H:i') ?? '—' }}
                                </div>
                                <div class="ts-lesson-class">
                                    {{ $class?->name ?? '—' }}
                                </div>
                                <div class="ts-lesson-subject">
                                    {{ $subject?->name ?? '—' }}
                                </div>
                                <div class="ts-lesson-room">
                                    {{ $class?->room ?? '' }}
                                </div>
                                <div class="ts-lesson-shift">
                                    {{ $shiftLabel }}
                                </div>
                                <div class="ts-lesson-year">
                                    {{ $schoolYear?->name ?? '—' }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</x-filament-panels::page>
