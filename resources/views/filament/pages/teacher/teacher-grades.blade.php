<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $classes = $data['classes'];
        $subjects = $data['subjects'];
        $assessments = $data['assessments'];
        $students = $data['students'];
        $summary = $data['summary'];
        $context = $data['context'];
        $canSave = $data['canSave'];
        $canPublish = $data['canPublish'];
        $isBlocked = $data['isBlocked'];
        $assessmentClosed = $data['assessmentClosed'];
        $schoolYearClosed = $data['schoolYearClosed'];
        $hasLockedGrades = $data['hasLockedGrades'];
        $contextError = $data['contextError'];
        $saveSummary = $data['saveSummary'];

        $selectedAssessment = $context['assessment'] ?? null;
        $maxScore = $selectedAssessment?->max_score ?? 10;
        $assessmentDate = $selectedAssessment?->date?->format('d/m/Y') ?? '—';
    @endphp

    <style>
        .teacher-grades-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .teacher-grades-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.875rem;
            box-shadow: var(--lumina-shadow);
        }

        .teacher-grades-hero {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .teacher-grades-title {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .teacher-grades-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(14, 116, 144, 0.18), rgba(245, 158, 11, 0.16));
            color: #0e7490;
            flex-shrink: 0;
        }

        .teacher-grades-title h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--ms-text-primary);
        }

        .teacher-grades-title p {
            margin: 0.2rem 0 0;
            color: var(--ms-text-secondary);
            font-size: 0.8125rem;
        }

        .teacher-grades-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .teacher-grades-badge {
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

        .teacher-grades-summary {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .teacher-grades-stat {
            padding: 0.55rem 0.75rem;
            border-radius: 0.65rem;
            background: var(--ms-cell-bg);
            border: 1px solid var(--ms-card-border);
            min-width: 6rem;
        }

        .teacher-grades-stat small {
            display: block;
            color: var(--ms-text-secondary);
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.2rem;
        }

        .teacher-grades-stat strong {
            display: block;
            color: var(--ms-text-primary);
            font-size: 1.05rem;
            font-weight: 800;
        }

        .teacher-grades-alert {
            padding: 1rem 1.2rem;
            border-left: 4px solid #0e7490;
            background: rgba(14, 116, 144, 0.08);
            color: var(--ms-text-primary);
            border-radius: 0.75rem;
        }

        .teacher-grades-alert--danger {
            border-left-color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
        }

        .teacher-grades-alert--success {
            border-left-color: #22c55e;
            background: rgba(34, 197, 94, 0.08);
        }

        .teacher-grades-form-grid {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1.2fr;
            gap: 1rem;
        }

        .teacher-grades-field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--ms-text-secondary);
            margin-bottom: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .teacher-grades-field select,
        .teacher-grades-field input,
        .teacher-grades-field textarea {
            width: 100%;
            border-radius: 0.65rem;
            border: 1px solid var(--ms-card-border);
            background: var(--ms-cell-bg);
            color: var(--ms-text-primary);
            padding: 0.7rem 0.85rem;
            outline: none;
        }

        .teacher-grades-field textarea {
            min-height: 3rem;
            resize: vertical;
        }

        .teacher-grades-table {
            overflow: hidden;
        }

        .teacher-grades-table-head,
        .teacher-grades-row {
            display: grid;
            grid-template-columns: 4rem minmax(0, 1fr) 6rem 10rem 1fr;
            gap: 0.75rem;
            align-items: center;
        }

        .teacher-grades-table-head {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--ms-card-border);
            color: var(--ms-text-secondary);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .teacher-grades-row {
            padding: 0.9rem 1.1rem;
            border-bottom: 1px solid var(--ms-bar-bg);
        }

        .teacher-grades-row:last-child {
            border-bottom: 0;
        }

        .teacher-grades-student {
            font-weight: 700;
            color: var(--ms-text-primary);
        }

        .teacher-grades-meta {
            display: inline-block;
            min-width: 4.5rem;
            color: var(--ms-text-muted);
            font-weight: 600;
        }

        .teacher-grades-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        @media (max-width: 1024px) {
            .teacher-grades-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .teacher-grades-table-head,
            .teacher-grades-row {
                grid-template-columns: 4rem minmax(0, 1fr) 6rem 1fr;
            }

            .teacher-grades-row .grades-comment {
                grid-column: span 2;
            }
        }

        @media (max-width: 640px) {
            .teacher-grades-form-grid,
            .teacher-grades-table-head,
            .teacher-grades-row {
                grid-template-columns: 1fr;
            }

            .teacher-grades-table-head {
                display: none;
            }

            .teacher-grades-row {
                gap: 0.5rem;
                align-items: start;
            }
        }
    </style>

    <div class="teacher-grades-shell">
        <div class="teacher-grades-card teacher-grades-hero">
            <div class="teacher-grades-title">
                <div class="teacher-grades-icon">
                    <x-filament::icon icon="fas-pen-to-square" class="w-6 h-6" />
                </div>
                <div>
                    <h2>Lançar Notas</h2>
                    <p>Informe notas e observações das avaliações vinculadas a você.</p>
                </div>
            </div>

            <div>
                <div class="teacher-grades-badges">
                    <span class="teacher-grades-badge">Professor: {{ $teacher?->name ?? '—' }}</span>
                    <span class="teacher-grades-badge">Avaliacao: {{ $selectedAssessment?->title ?? '—' }}</span>
                    <span class="teacher-grades-badge">Data: {{ $assessmentDate }}</span>
                </div>
                <div class="teacher-grades-summary" style="margin-top:0.75rem">
                    <div class="teacher-grades-stat">
                        <small>Total</small>
                        <strong>{{ $summary['total'] }}</strong>
                    </div>
                    <div class="teacher-grades-stat">
                        <small>Preenchidas</small>
                        <strong>{{ $summary['filled'] }}</strong>
                    </div>
                    <div class="teacher-grades-stat">
                        <small>Restantes</small>
                        <strong>{{ $summary['remaining'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        @if($saveSummary)
            <div class="teacher-grades-alert teacher-grades-alert--success">
                <strong>{{ $saveSummary['published'] ? 'Notas publicadas.' : 'Rascunho salvo.' }}</strong>
                {{ $saveSummary['total'] }} registros gravados.
            </div>
        @endif

        @if($isBlocked)
            <div class="teacher-grades-alert teacher-grades-alert--danger">
                <strong>Acesso bloqueado.</strong> Professor afastado, inativo ou desligado não pode lançar notas.
            </div>
        @endif

        @if($assessmentClosed)
            <div class="teacher-grades-alert teacher-grades-alert--danger">
                <strong>Avaliação fechada.</strong> Edição bloqueada.
            </div>
        @endif

        @if($schoolYearClosed)
            <div class="teacher-grades-alert teacher-grades-alert--danger">
                <strong>Período letivo fechado.</strong> Edição bloqueada.
            </div>
        @endif

        @if($hasLockedGrades)
            <div class="teacher-grades-alert teacher-grades-alert--danger">
                <strong>Notas publicadas.</strong> Não é possível editar registros publicados.
            </div>
        @endif

        @if($contextError)
            <div class="teacher-grades-alert teacher-grades-alert--danger">
                <strong>Contexto inválido.</strong> {{ $contextError }}
            </div>
        @endif

        <div class="teacher-grades-card" style="padding:1.1rem 1.2rem">
            <div class="teacher-grades-form-grid">
                <div class="teacher-grades-field">
                    <label for="teacher-grades-class">Turma</label>
                    <select id="teacher-grades-class" wire:model.live="selectedClassId">
                        <option value="">Selecione</option>
                        @foreach($classes as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="teacher-grades-field">
                    <label for="teacher-grades-subject">Disciplina</label>
                    <select id="teacher-grades-subject" wire:model.live="selectedSubjectId">
                        <option value="">Selecione</option>
                        @foreach($subjects as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="teacher-grades-field">
                    <label for="teacher-grades-assessment">Avaliação</label>
                    <select id="teacher-grades-assessment" wire:model.live="selectedAssessmentId">
                        <option value="">Selecione</option>
                        @foreach($assessments as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        @if($students->isEmpty())
            <div class="teacher-grades-card" style="padding:2.25rem 1.5rem;text-align:center">
                <x-filament::icon icon="fas-clipboard-user" class="w-12 h-12 mx-auto mb-4" />
                <h3 style="margin:0 0 0.5rem;font-size:1.05rem;font-weight:800;color:var(--ms-text-primary)">
                    Selecione turma, disciplina e avaliação
                </h3>
                <p style="margin:0;color:var(--ms-text-secondary);font-size:0.875rem">
                    A lista de alunos aparece apenas quando a avaliação está vinculada ao seu cadastro.
                </p>
            </div>
        @else
            <div class="teacher-grades-card teacher-grades-table">
                <div class="teacher-grades-table-head">
                    <div>#</div>
                    <div>Aluno</div>
                    <div>Nota</div>
                    <div>Max</div>
                    <div>Observacao</div>
                </div>

                @foreach($students as $row)
                    <div class="teacher-grades-row" wire:key="grade-row-{{ $row['student_id'] }}">
                        <div style="font-weight:700;color:var(--ms-text-secondary)">{{ $row['roll_number'] ?? '—' }}</div>
                        <div>
                            <div class="teacher-grades-student">
                                <span class="teacher-grades-meta">{{ $row['registration_number'] }}</span>
                                {{ $row['student_name'] }}
                            </div>
                        </div>
                        <div class="teacher-grades-field">
                            <input
                                type="number"
                                min="0"
                                step="0.01"
                                max="{{ $maxScore }}"
                                wire:model.defer="gradeRows.{{ $row['student_id'] }}.score"
                                @if($row['locked'] || $assessmentClosed || $schoolYearClosed) disabled @endif
                            />
                        </div>
                        <div style="font-weight:700;color:var(--ms-text-secondary)">{{ $row['max_score'] }}</div>
                        <div class="teacher-grades-field grades-comment">
                            <textarea
                                wire:model.defer="gradeRows.{{ $row['student_id'] }}.comment"
                                @if($row['locked'] || $assessmentClosed || $schoolYearClosed) disabled @endif
                            ></textarea>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="teacher-grades-actions" style="margin-top:1rem">
                @if($canSave)
                    <x-filament::button
                        type="button"
                        color="primary"
                        icon="fas-floppy-disk"
                        wire:click="saveDraft"
                    >
                        Salvar rascunho
                    </x-filament::button>
                @endif
                @if($canPublish)
                    <x-filament::button
                        type="button"
                        color="warning"
                        icon="fas-paper-plane"
                        wire:click="publishGrades"
                    >
                        Publicar notas
                    </x-filament::button>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
