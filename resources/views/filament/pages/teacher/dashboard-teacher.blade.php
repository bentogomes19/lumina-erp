<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $teacher = $data['teacher'];
        $assignments = $data['assignments'];
        $classes = $data['classes'];
        $subjects = $data['subjects'];
        $lessonsThisWeek = $data['lessonsThisWeek'];
        $assessments = $data['assessments'];
        $cards = $data['cards'];

        $initials = collect(explode(' ', $teacher?->name ?? auth()->user()?->name ?? 'P'))
            ->filter()
            ->take(2)
            ->map(fn ($word) => strtoupper($word[0]))
            ->implode('');
    @endphp

    <style>
        .teacher-dashboard {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .teacher-dashboard .ms-card,
        .teacher-dashboard-empty {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem;
            box-shadow: var(--lumina-shadow);
        }

        .teacher-dashboard-hero {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem;
            overflow: hidden;
        }

        .teacher-dashboard-accent {
            position: absolute;
            inset: 0 0 auto;
            height: 4px;
            background: linear-gradient(90deg, #f59e0b, #0f766e);
        }

        .teacher-dashboard-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .teacher-dashboard-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            color: #fff;
            background: linear-gradient(135deg, #f59e0b, #b45309);
            border-radius: 999px;
            font-size: 1.25rem;
            font-weight: 800;
            box-shadow: 0 0 0 4px var(--lumina-primary-soft);
            flex-shrink: 0;
        }

        .teacher-dashboard-identity h2 {
            color: var(--ms-text-primary);
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.2;
            margin: 0;
        }

        .teacher-dashboard-identity p {
            color: var(--ms-text-secondary);
            font-size: 0.8125rem;
            margin: 0.25rem 0 0;
        }

        .teacher-dashboard-hero-metrics {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .teacher-dashboard-hero-metrics div {
            min-width: 6rem;
            padding: 0.75rem 1rem;
            background: var(--ms-cell-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.625rem;
            text-align: center;
        }

        .teacher-dashboard-hero-metrics span {
            display: block;
            color: var(--lumina-primary);
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .teacher-dashboard-hero-metrics small {
            color: var(--ms-text-secondary);
            font-size: 0.6875rem;
        }

        .teacher-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .teacher-dashboard-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
            padding: 1rem;
        }

        .teacher-dashboard-card-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            color: var(--teacher-card-color);
            background: color-mix(in srgb, var(--teacher-card-color) 14%, transparent);
            border-radius: 0.625rem;
            flex-shrink: 0;
        }

        .teacher-dashboard-card-icon svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .teacher-dashboard-card strong,
        .teacher-dashboard-card span,
        .teacher-dashboard-card small {
            display: block;
        }

        .teacher-dashboard-card strong {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1;
        }

        .teacher-dashboard-card span {
            color: var(--ms-text-primary);
            font-size: 0.8125rem;
            font-weight: 700;
            margin-top: 0.125rem;
        }

        .teacher-dashboard-card small {
            color: var(--ms-text-muted);
            font-size: 0.6875rem;
            margin-top: 0.125rem;
        }

        .teacher-dashboard-columns {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .teacher-dashboard-panel {
            padding: 1.25rem;
        }

        .teacher-dashboard-panel header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .teacher-dashboard-panel header svg {
            width: 1.125rem;
            height: 1.125rem;
            color: var(--lumina-primary);
        }

        .teacher-dashboard-panel h3 {
            color: var(--ms-text-primary);
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        .teacher-dashboard-list {
            display: flex;
            flex-direction: column;
        }

        .teacher-dashboard-list-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--ms-bar-bg);
        }

        .teacher-dashboard-list-item:last-child {
            border-bottom: 0;
        }

        .teacher-dashboard-date {
            min-width: 3rem;
            padding: 0.375rem 0.5rem;
            background: var(--lumina-primary-soft);
            border-radius: 0.5rem;
            text-align: center;
            flex-shrink: 0;
        }

        .teacher-dashboard-date strong,
        .teacher-dashboard-date span {
            display: block;
        }

        .teacher-dashboard-date strong {
            color: var(--lumina-primary);
            font-size: 1rem;
            font-weight: 800;
            line-height: 1;
        }

        .teacher-dashboard-date span {
            color: var(--ms-text-secondary);
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .teacher-dashboard-list-item > div:last-child {
            min-width: 0;
        }

        .teacher-dashboard-list-item > div:last-child strong {
            display: block;
            color: var(--ms-text-primary);
            font-size: 0.875rem;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .teacher-dashboard-list-item > div:last-child span,
        .teacher-dashboard-panel-empty,
        .teacher-dashboard-empty p {
            color: var(--ms-text-secondary);
            font-size: 0.8125rem;
        }

        .teacher-dashboard-panel-empty {
            padding: 1.5rem;
            background: var(--ms-cell-bg);
            border-radius: 0.625rem;
            text-align: center;
        }

        .teacher-dashboard-empty {
            padding: 3rem;
            text-align: center;
        }

        .teacher-dashboard-empty-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            margin: 0 auto 1rem;
            color: var(--lumina-primary);
            background: var(--lumina-primary-soft);
            border-radius: 999px;
        }

        .teacher-dashboard-empty-icon svg {
            width: 2rem;
            height: 2rem;
        }

        .teacher-dashboard-empty h3 {
            color: var(--ms-text-primary);
            font-size: 1.125rem;
            font-weight: 700;
            margin: 0 0 0.5rem;
        }

        .teacher-dashboard-empty p {
            margin: 0;
        }

        @media (max-width: 1024px) {
            .teacher-dashboard-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .teacher-dashboard-columns {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .teacher-dashboard-hero {
                align-items: stretch;
                flex-direction: column;
            }

            .teacher-dashboard-hero-metrics {
                display: grid;
                grid-template-columns: 1fr;
            }

            .teacher-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if(! $teacher)
        <div class="ms-card teacher-dashboard-empty">
            <div class="teacher-dashboard-empty-icon">
                @svg('fas-triangle-exclamation')
            </div>
            <h3>Perfil de professor não encontrado</h3>
            <p>Seu usuário não está vinculado a um cadastro de professor. Contate a secretaria.</p>
        </div>
    @elseif($assignments->isEmpty())
        <div class="ms-card teacher-dashboard-empty">
            <div class="teacher-dashboard-empty-icon">
                @svg('fas-chalkboard-user')
            </div>
            <h3>Nenhuma alocação encontrada</h3>
            <p>Você ainda não possui turmas ou disciplinas vinculadas no ano letivo atual.</p>
        </div>
    @else
        <div class="teacher-dashboard">
            <section class="ms-card teacher-dashboard-hero">
                <div class="teacher-dashboard-accent"></div>

                <div class="teacher-dashboard-identity">
                    <div class="teacher-dashboard-avatar">{{ $initials }}</div>
                    <div>
                        <h2>{{ $teacher->name }}</h2>
                        <p>
                            {{ $teacher->employee_number ? 'Matrícula ' . $teacher->employee_number : 'Professor' }}
                            @if($teacher->academic_title)
                                · {{ $teacher->academic_title->label() ?? $teacher->academic_title->value ?? $teacher->academic_title }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="teacher-dashboard-hero-metrics">
                    <div>
                        <span>{{ $classes->count() }}</span>
                        <small>Turmas</small>
                    </div>
                    <div>
                        <span>{{ $subjects->count() }}</span>
                        <small>Disciplinas</small>
                    </div>
                    <div>
                        <span>{{ $data['recordedAttendance'] }}</span>
                        <small>Chamadas em 7 dias</small>
                    </div>
                </div>
            </section>

            <section class="teacher-dashboard-grid">
                @foreach($cards as $card)
                    <article class="ms-card teacher-dashboard-card">
                        <div class="teacher-dashboard-card-icon" style="--teacher-card-color: {{ $card['color'] }}">
                            @svg($card['icon'])
                        </div>
                        <div>
                            <strong style="color: {{ $card['color'] }}">{{ $card['value'] }}</strong>
                            <span>{{ $card['label'] }}</span>
                            <small>{{ $card['description'] }}</small>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="teacher-dashboard-columns">
                <article class="ms-card teacher-dashboard-panel">
                    <header>
                        @svg('fas-calendar-week')
                        <h3>Aulas da Semana</h3>
                    </header>

                    @if($lessonsThisWeek->isEmpty())
                        <div class="teacher-dashboard-panel-empty">
                            Nenhuma aula programada para esta semana.
                        </div>
                    @else
                        <div class="teacher-dashboard-list">
                            @foreach($lessonsThisWeek->take(6) as $lesson)
                                <div class="teacher-dashboard-list-item">
                                    <div class="teacher-dashboard-date">
                                        <strong>{{ $lesson->date?->format('d') }}</strong>
                                        <span>{{ $lesson->date?->translatedFormat('M') }}</span>
                                    </div>
                                    <div>
                                        <strong>{{ $lesson->subject?->name ?? 'Disciplina não definida' }}</strong>
                                        <span>
                                            {{ $lesson->schoolClass?->name ?? 'Turma não definida' }}
                                            @if($lesson->start_time)
                                                · {{ \Carbon\Carbon::parse($lesson->start_time)->format('H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>

                <article class="ms-card teacher-dashboard-panel">
                    <header>
                        @svg('fas-clipboard-list')
                        <h3>Próximas Avaliações</h3>
                    </header>

                    @if($assessments->isEmpty())
                        <div class="teacher-dashboard-panel-empty">
                            Nenhuma avaliação futura cadastrada para suas turmas.
                        </div>
                    @else
                        <div class="teacher-dashboard-list">
                            @foreach($assessments as $assessment)
                                <div class="teacher-dashboard-list-item">
                                    <div class="teacher-dashboard-date">
                                        <strong>{{ $assessment->scheduled_at?->format('d') }}</strong>
                                        <span>{{ $assessment->scheduled_at?->translatedFormat('M') }}</span>
                                    </div>
                                    <div>
                                        <strong>{{ $assessment->title }}</strong>
                                        <span>
                                            {{ $assessment->subject?->name ?? 'Disciplina' }}
                                            · {{ $assessment->schoolClass?->name ?? 'Turma' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            </section>
        </div>
    @endif
</x-filament-panels::page>
