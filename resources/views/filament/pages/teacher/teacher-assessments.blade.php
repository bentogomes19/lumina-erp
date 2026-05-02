<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $assignments = $data['assignments'];
        $stats = $data['stats'];
        $canCreate = $data['canCreate'];
        $isBlocked = $data['isBlocked'];

        $teacherStatus = $teacher?->status?->label() ?? 'Sem vínculo';
    @endphp

    <style>
        .teacher-assessments-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .teacher-assessments-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.875rem;
            box-shadow: var(--lumina-shadow);
        }

        .teacher-assessments-hero {
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .teacher-assessments-title {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }

        .teacher-assessments-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.18), rgba(15, 118, 110, 0.16));
            color: #d97706;
            flex-shrink: 0;
        }

        .teacher-assessments-title h2 {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--ms-text-primary);
        }

        .teacher-assessments-title p {
            margin: 0.2rem 0 0;
            color: var(--ms-text-secondary);
            font-size: 0.8125rem;
        }

        .teacher-assessments-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .teacher-assessments-badge {
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

        .teacher-assessments-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .teacher-assessments-stat {
            padding: 1rem 1.1rem;
        }

        .teacher-assessments-stat small {
            display: block;
            color: var(--ms-text-secondary);
            font-size: 0.725rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 0.45rem;
        }

        .teacher-assessments-stat strong {
            display: block;
            color: var(--ms-text-primary);
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .teacher-assessments-stat span {
            display: block;
            color: var(--ms-text-muted);
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }

        .teacher-assessments-alert {
            padding: 1rem 1.2rem;
            border-left: 4px solid #d97706;
            background: rgba(245, 158, 11, 0.08);
            color: var(--ms-text-primary);
            border-radius: 0.75rem;
        }

        .teacher-assessments-alert strong {
            font-weight: 700;
        }

        .teacher-assessments-table {
            padding: 0.25rem 0 0;
        }

        @media (max-width: 1024px) {
            .teacher-assessments-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .teacher-assessments-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="teacher-assessments-shell">
        <div class="teacher-assessments-card teacher-assessments-hero">
            <div class="teacher-assessments-title">
                <div class="teacher-assessments-icon">
                    <x-filament::icon icon="fas-clipboard-question" class="w-6 h-6" />
                </div>
                <div>
                    <h2>Avaliações</h2>
                    <p>Gerencie as avaliações das turmas e disciplinas vinculadas ao seu perfil.</p>
                </div>
            </div>

            <div class="teacher-assessments-badges">
                <span class="teacher-assessments-badge">Professor: {{ $teacher?->name ?? '—' }}</span>
                <span class="teacher-assessments-badge">Status: {{ $teacherStatus }}</span>
                <span class="teacher-assessments-badge">Vínculos: {{ $assignments->count() }}</span>
            </div>
        </div>

        @if($teacher && $isBlocked)
            <div class="teacher-assessments-alert">
                <strong>Criação desabilitada.</strong> Professores afastados, inativos ou desligados não podem criar avaliações.
            </div>
        @endif

        @if($teacher && ! $canCreate && $assignments->isEmpty())
            <div class="teacher-assessments-alert">
                <strong>Sem vínculos disponíveis.</strong> Você ainda não possui turmas e disciplinas atribuídas para criar avaliações.
            </div>
        @endif

        <div class="teacher-assessments-grid">
            <div class="teacher-assessments-card teacher-assessments-stat">
                <small>Total</small>
                <strong>{{ $stats['total'] }}</strong>
                <span>avaliações cadastradas</span>
            </div>
            <div class="teacher-assessments-card teacher-assessments-stat">
                <small>Abertas</small>
                <strong>{{ $stats['open'] }}</strong>
                <span>disponíveis para edição</span>
            </div>
            <div class="teacher-assessments-card teacher-assessments-stat">
                <small>Fechadas</small>
                <strong>{{ $stats['closed'] }}</strong>
                <span>bloqueadas para edição</span>
            </div>
            <div class="teacher-assessments-card teacher-assessments-stat">
                <small>Próxima</small>
                <strong>{{ $stats['next']?->scheduled_at?->format('d/m') ?? '—' }}</strong>
                <span>{{ $stats['next']?->title ?? 'Sem próxima avaliação' }}</span>
            </div>
        </div>

        <div class="teacher-assessments-card teacher-assessments-table">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>