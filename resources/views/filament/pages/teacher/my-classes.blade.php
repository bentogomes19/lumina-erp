<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $assignments = $data['assignments'];
    @endphp

    <style>
        .my-classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(22rem, 1fr));
            gap: 1.25rem;
        }

        .my-classes-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem;
            box-shadow: var(--lumina-shadow);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .my-classes-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--ms-card-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .my-classes-card-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--ms-text-primary);
            line-height: 1.3;
        }

        .my-classes-card-header small {
            color: var(--ms-text-secondary);
            font-size: 0.75rem;
        }

        .my-classes-status {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .my-classes-status--open {
            background: #dcfce7;
            color: #166534;
        }

        .my-classes-status--closed {
            background: #fee2e2;
            color: #991b1b;
        }

        .my-classes-status--archived {
            background: #f3f4f6;
            color: #6b7280;
        }

        .my-classes-card-body {
            padding: 1rem 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .my-classes-info-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: var(--ms-text-secondary);
        }

        .my-classes-info-row x-icon,
        .my-classes-info-row svg {
            width: 1rem;
            height: 1rem;
            flex-shrink: 0;
            opacity: 0.6;
        }

        .my-classes-info-row strong {
            color: var(--ms-text-primary);
            font-weight: 600;
        }

        .my-classes-card-actions {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--ms-card-border);
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .my-classes-action {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--ms-card-border);
            background: var(--ms-cell-bg);
            color: var(--ms-text-primary);
            transition: background 0.15s, border-color 0.15s;
        }

        .my-classes-action:hover {
            background: var(--lumina-primary-soft, #f0f9ff);
            border-color: var(--lumina-primary, #0f766e);
            color: var(--lumina-primary, #0f766e);
        }

        .my-classes-action svg {
            width: 0.875rem;
            height: 0.875rem;
        }

        .my-classes-empty {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem;
            box-shadow: var(--lumina-shadow);
            padding: 3rem 1.5rem;
            text-align: center;
        }

        .my-classes-empty svg {
            width: 3rem;
            height: 3rem;
            color: var(--ms-text-secondary);
            opacity: 0.4;
            margin: 0 auto 1rem;
        }

        .my-classes-empty h3 {
            margin: 0 0 0.5rem;
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--ms-text-primary);
        }

        .my-classes-empty p {
            margin: 0;
            font-size: 0.875rem;
            color: var(--ms-text-secondary);
        }
    </style>

    @if($assignments->isEmpty())
        <div class="my-classes-empty">
            <x-filament::icon icon="fas-chalkboard-user" class="w-12 h-12" />
            <h3>Nenhuma turma encontrada</h3>
            <p>Você ainda não possui turmas ou disciplinas atribuídas.</p>
        </div>
    @else
        <div class="my-classes-grid">
            @foreach($assignments as $assignment)
                @php
                    $class = $assignment->schoolClass;
                    $subject = $assignment->subject;
                    $gradeLevel = $class?->gradeLevel;
                    $schoolYear = $class?->schoolYear;
                    $statusValue = $class?->status?->value ?? 'open';
                    $statusLabel = $class?->status?->label() ?? '—';
                    $shiftLabel = $class?->shift?->label() ?? '—';
                @endphp
                <div class="my-classes-card">
                    <div class="my-classes-card-header">
                        <div>
                            <h3>{{ $class?->name ?? '—' }}</h3>
                            <small>{{ $subject?->name ?? '—' }}</small>
                        </div>
                        <span class="my-classes-status my-classes-status--{{ $statusValue }}">
                            {{ $statusLabel }}
                        </span>
                    </div>

                    <div class="my-classes-card-body">
                        <div class="my-classes-info-row">
                            <x-filament::icon icon="fas-layer-group" class="w-4 h-4" />
                            <span>Série/Ano: <strong>{{ $gradeLevel?->name ?? '—' }}</strong></span>
                        </div>
                        <div class="my-classes-info-row">
                            <x-filament::icon icon="fas-clock" class="w-4 h-4" />
                            <span>Turno: <strong>{{ $shiftLabel }}</strong></span>
                        </div>
                        <div class="my-classes-info-row">
                            <x-filament::icon icon="fas-calendar" class="w-4 h-4" />
                            <span>Período Letivo: <strong>{{ $schoolYear?->name ?? '—' }}</strong></span>
                        </div>
                        <div class="my-classes-info-row">
                            <x-filament::icon icon="fas-users" class="w-4 h-4" />
                            <span>Alunos: <strong>{{ $assignment->students_count }}</strong></span>
                        </div>
                        <div class="my-classes-info-row">
                            <x-filament::icon icon="fas-hourglass-half" class="w-4 h-4" />
                            <span>Carga Horária: <strong>{{ $assignment->hours_weekly ? $assignment->hours_weekly . 'h/semana' : '—' }}</strong></span>
                        </div>
                        @if($class?->room ?? false)
                            <div class="my-classes-info-row">
                                <x-filament::icon icon="fas-door-open" class="w-4 h-4" />
                                <span>Sala: <strong>{{ $class->room }}</strong></span>
                            </div>
                        @endif
                    </div>

                    <div class="my-classes-card-actions">
                        <a href="#" class="my-classes-action" title="Ver alunos">
                            <x-filament::icon icon="fas-users" class="w-4 h-4" />
                            Ver alunos
                        </a>

                        @if(\App\Support\PermissionAccess::can('teacher.attendance.create'))
                            <a href="#" class="my-classes-action" title="Lançar frequência">
                                <x-filament::icon icon="fas-clipboard-check" class="w-4 h-4" />
                                Lançar frequência
                            </a>
                        @endif

                        @if(\App\Support\PermissionAccess::can('teacher.grades.create'))
                            <a href="#" class="my-classes-action" title="Lançar notas">
                                <x-filament::icon icon="fas-pen-to-square" class="w-4 h-4" />
                                Lançar notas
                            </a>
                        @endif

                        @if(\App\Support\PermissionAccess::can('teacher.assessments.create'))
                            <a href="#" class="my-classes-action" title="Criar avaliação">
                                <x-filament::icon icon="fas-clipboard-list" class="w-4 h-4" />
                                Criar avaliação
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
