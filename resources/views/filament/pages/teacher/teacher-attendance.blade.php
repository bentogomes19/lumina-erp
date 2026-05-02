<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $assignments = $data['assignments'];
        $classes = $data['classes'];
        $subjects = $data['subjects'];
        $students = $data['students'];
        $summary = $data['summary'];
        $canSubmit = $data['canSubmit'];
        $canCreate = $data['canCreate'];
        $canUpdate = $data['canUpdate'];
        $isBlocked = $data['isBlocked'];
        $schoolYearClosed = $data['schoolYearClosed'];
        $contextError = $data['contextError'];
        $saveSummary = $data['saveSummary'];

        $context = $data['context'];
        $selectedClassName = $context['class']?->name ?? null;
        $selectedSubjectName = $context['subject']?->name ?? null;
        $selectedSchoolYear = $context['schoolYear']?->year ?? $context['schoolYear']?->name ?? null;
        $selectedDate = $this->selectedDate ? \Illuminate\Support\Carbon::parse($this->selectedDate)->format('d/m/Y') : '—';
    @endphp

    <style>
        .teacher-attendance-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .teacher-attendance-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.875rem;
            box-shadow: var(--lumina-shadow);
        }

        .teacher-attendance-hero {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .teacher-attendance-hero-meta {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-width: 16rem;
        }

        .teacher-attendance-title {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .teacher-attendance-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.16), rgba(249, 115, 22, 0.16));
            color: #0ea5e9;
            flex-shrink: 0;
        }

        .teacher-attendance-title h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--ms-text-primary);
        }

        .teacher-attendance-title p {
            margin: 0.2rem 0 0;
            color: var(--ms-text-secondary);
            font-size: 0.8125rem;
        }

        .teacher-attendance-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .teacher-attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            background: var(--ms-cell-bg);
            border: 1px solid var(--ms-card-border);
            color: var(--ms-text-primary);
            font-size: 0.75rem;
            font-weight: 600;
        }

        .teacher-attendance-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .teacher-attendance-stat {
            padding: 0.6rem 0.75rem;
            border-radius: 0.65rem;
            background: var(--ms-cell-bg);
            border: 1px solid var(--ms-card-border);
        }

        .teacher-attendance-stat small {
            display: block;
            color: var(--ms-text-secondary);
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .teacher-attendance-stat strong {
            display: block;
            color: var(--ms-text-primary);
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .teacher-attendance-stat span {
            display: block;
            color: var(--ms-text-muted);
            font-size: 0.625rem;
            margin-top: 0.15rem;
        }

        .teacher-attendance-alert {
            padding: 1rem 1.2rem;
            border-left: 4px solid #0ea5e9;
            background: rgba(14, 165, 233, 0.08);
            color: var(--ms-text-primary);
            border-radius: 0.75rem;
        }

        .teacher-attendance-alert--danger {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
        }

        .teacher-attendance-alert--success {
            border-left-color: #22c55e;
            background: rgba(34, 197, 94, 0.08);
        }

        .teacher-attendance-form-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 0.8fr;
            gap: 1rem;
        }

        .teacher-attendance-field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--ms-text-secondary);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .teacher-attendance-field select,
        .teacher-attendance-field input {
            width: 100%;
            border-radius: 0.65rem;
            border: 1px solid var(--ms-card-border);
            background: var(--ms-cell-bg);
            color: var(--ms-text-primary);
            padding: 0.75rem 0.9rem;
            outline: none;
        }

        .teacher-attendance-field select:focus,
        .teacher-attendance-field input:focus {
            border-color: var(--lumina-primary, #0f766e);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--lumina-primary, #0f766e) 18%, transparent);
        }

        .teacher-attendance-table {
            overflow: hidden;
        }

        .teacher-attendance-table-head,
        .teacher-attendance-row {
            display: grid;
            grid-template-columns: 4rem minmax(0, 1fr) 7rem;
            gap: 0.75rem;
            align-items: center;
        }

        .teacher-attendance-table-head {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--ms-card-border);
            color: var(--ms-text-secondary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .teacher-attendance-row {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--ms-bar-bg);
        }

        .teacher-attendance-row:last-child {
            border-bottom: 0;
        }

        .teacher-attendance-student {
            font-weight: 700;
            color: var(--ms-text-primary);
        }

        .teacher-attendance-meta {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.75rem;
            color: var(--ms-text-muted);
        }

        .teacher-attendance-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--ms-text-primary);
        }

        .teacher-attendance-checkbox input[type="checkbox"] {
            width: 1.1rem;
            height: 1.1rem;
            accent-color: var(--lumina-primary, #0f766e);
        }

        .teacher-attendance-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        @media (max-width: 1024px) {
            .teacher-attendance-grid,
            .teacher-attendance-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .teacher-attendance-table-head,
            .teacher-attendance-row {
                grid-template-columns: 4rem minmax(0, 1fr) 7rem;
            }

            .teacher-attendance-row .attendance-select-col {
                grid-column: span 2;
            }
        }

        @media (max-width: 640px) {
            .teacher-attendance-grid,
            .teacher-attendance-form-grid,
            .teacher-attendance-table-head,
            .teacher-attendance-row {
                grid-template-columns: 1fr;
            }

            .teacher-attendance-table-head {
                display: none;
            }

            .teacher-attendance-row {
                gap: 0.5rem;
                align-items: start;
            }
        }
    </style>

    <div class="teacher-attendance-shell">
        <div class="teacher-attendance-card teacher-attendance-hero">
            <div class="teacher-attendance-title">
                <div class="teacher-attendance-icon">
                    <x-filament::icon icon="fas-user-check" class="w-6 h-6" />
                </div>
                <div>
                    <h2>Lançar Frequência</h2>
                    <p>Registre presença, falta ou falta justificada para sua turma e disciplina.</p>
                </div>
            </div>

            <div class="teacher-attendance-hero-meta">
                <div class="teacher-attendance-badges">
                    <span class="teacher-attendance-badge">Professor: {{ $teacher?->name ?? '—' }}</span>
                    <span class="teacher-attendance-badge">Vínculos: {{ $assignments->count() }}</span>
                    <span class="teacher-attendance-badge">Data: {{ $selectedDate }}</span>
                </div>

                <div class="teacher-attendance-grid">
                    <div class="teacher-attendance-stat">
                        <small>Total</small>
                        <strong>{{ $summary['total'] }}</strong>
                        <span>alunos</span>
                    </div>
                    <div class="teacher-attendance-stat">
                        <small>Presentes</small>
                        <strong>{{ $summary['present'] }}</strong>
                        <span>marcados</span>
                    </div>
                    <div class="teacher-attendance-stat">
                        <small>Faltas</small>
                        <strong>{{ $summary['absent'] }}</strong>
                        <span>marcados</span>
                    </div>
                </div>
            </div>
        </div>

        @if($saveSummary)
            <div class="teacher-attendance-alert teacher-attendance-alert--success">
                <strong>Frequência salva.</strong>
                {{ $saveSummary['created'] }} registros criados e {{ $saveSummary['updated'] }} atualizados.
            </div>
        @endif

        @if($isBlocked)
            <div class="teacher-attendance-alert teacher-attendance-alert--danger">
                <strong>Acesso bloqueado.</strong> Professores afastados, inativos ou desligados não podem lançar frequência.
            </div>
        @endif

        @if($schoolYearClosed)
            <div class="teacher-attendance-alert teacher-attendance-alert--danger">
                <strong>Período letivo fechado.</strong> A edição de frequência nesta turma está bloqueada.
            </div>
        @endif

        @if($contextError)
            <div class="teacher-attendance-alert teacher-attendance-alert--danger">
                <strong>Contexto inválido.</strong> {{ $contextError }}
            </div>
        @endif

        <div class="teacher-attendance-card" style="padding:1.1rem 1.2rem">
            <div class="teacher-attendance-form-grid">
                <div class="teacher-attendance-field">
                    <label for="teacher-attendance-class">Turma</label>
                    <select id="teacher-attendance-class" wire:model.live="selectedClassId">
                        <option value="">Selecione</option>
                        @foreach($classes as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="teacher-attendance-field">
                    <label for="teacher-attendance-subject">Disciplina</label>
                    <select id="teacher-attendance-subject" wire:model.live="selectedSubjectId">
                        <option value="">Selecione</option>
                        @foreach($subjects as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="teacher-attendance-field">
                    <label for="teacher-attendance-date">Data da aula</label>
                    <input id="teacher-attendance-date" type="date" wire:model.live="selectedDate">
                </div>
            </div>

            @if($data['context'])
                <div style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:0.5rem">
                    <span class="teacher-attendance-badge">Turma: {{ $selectedClassName }}</span>
                    <span class="teacher-attendance-badge">Disciplina: {{ $selectedSubjectName }}</span>
                    <span class="teacher-attendance-badge">Período: {{ $selectedSchoolYear ?? '—' }}</span>
                </div>
            @endif
        </div>

        @if($students->isEmpty())
            <div class="teacher-attendance-card" style="padding:2.25rem 1.5rem;text-align:center">
                <x-filament::icon icon="fas-clipboard-user" class="w-12 h-12 mx-auto mb-4" />
                <h3 style="margin:0 0 0.5rem;font-size:1.05rem;font-weight:800;color:var(--ms-text-primary)">
                    Selecione turma, disciplina e data
                </h3>
                <p style="margin:0;color:var(--ms-text-secondary);font-size:0.875rem">
                    A lista de alunos aparece somente quando o contexto está vinculado ao seu cadastro.
                </p>
            </div>
        @else
            <div class="teacher-attendance-card teacher-attendance-table">
                <div class="teacher-attendance-table-head">
                    <div>#</div>
                    <div>Aluno</div>
                    <div style="text-align:center">Presente</div>
                </div>

                @foreach($students as $row)
                    <div class="teacher-attendance-row" wire:key="attendance-row-{{ $row['student_id'] }}">
                        <div style="font-weight:700;color:var(--ms-text-secondary)">{{ $row['roll_number'] ?? '—' }}</div>

                        <div>
                            <div class="teacher-attendance-student">
                                <span style="display:inline-block;min-width:4.5rem;color:var(--ms-text-muted);font-weight:600">
                                    {{ $row['registration_number'] }}
                                </span>
                                {{ $row['student_name'] }}
                            </div>
                        </div>

                        <div style="display:flex;justify-content:center">
                            <label class="teacher-attendance-checkbox" for="attendance-present-{{ $row['student_id'] }}">
                                <input
                                    id="attendance-present-{{ $row['student_id'] }}"
                                    type="checkbox"
                                    wire:model.live="attendanceRows.{{ $row['student_id'] }}.present"
                                    aria-label="Presente"
                                />
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="teacher-attendance-actions" style="margin-top:1rem">
                @if($canSubmit)
                    <x-filament::button
                        type="button"
                        color="primary"
                        icon="fas-floppy-disk"
                        wire:click="saveAttendance"
                    >
                        Salvar frequência
                    </x-filament::button>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>