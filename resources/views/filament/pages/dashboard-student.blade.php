<x-filament-panels::page>
    <style>
        /* ── Variáveis de tema ── */
        :root {
            --ms-card-bg:        #ffffff;
            --ms-card-border:    #e2e8f0;
            --ms-cell-bg:        #f1f5f9;
            --ms-bar-bg:         #e2e8f0;
            --ms-text-primary:   #1e293b;
            --ms-text-secondary: #64748b;
            --ms-text-muted:     #94a3b8;
            --ms-hover-bg:       #f8fafc;
        }
        .dark {
            --ms-card-bg:        #080a0c;
            --ms-card-border:    #334155;
            --ms-cell-bg:        #0f172a;
            --ms-bar-bg:         #10141d;
            --ms-text-primary:   #f1f5f9;
            --ms-text-secondary: #94a3b8;
            --ms-text-muted:     #64748b;
            --ms-hover-bg:       #0a0d11;
        }

        .ms-card { background: var(--ms-card-bg); border: 1px solid var(--ms-card-border); border-radius: 0.75rem; }

        /* ── Avatar de iniciais ── */
        .ms-avatar {
            width: 4rem; height: 4rem; border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.375rem; font-weight: 800; color: #fff; flex-shrink: 0;
        }

        /* ── Linha da agenda ── */
        .ms-lesson-row {
            display: flex; align-items: flex-start; gap: 0.875rem;
            padding: 0.75rem 0; border-bottom: 1px solid var(--ms-bar-bg);
        }
        .ms-lesson-row:last-child { border-bottom: none; }

        /* ── Barra de frequência ── */
        .ms-bar-track { width: 100%; height: 5px; border-radius: 999px; background: var(--ms-bar-bg); overflow: hidden; }

        /* ── Card de atalho ── */
        a.ms-shortcut {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.875rem 1rem; border-radius: 0.625rem;
            background: var(--ms-cell-bg); border: 1px solid var(--ms-card-border);
            text-decoration: none; transition: background 0.15s, transform 0.15s;
        }
        a.ms-shortcut:hover { background: var(--ms-hover-bg); transform: translateX(3px); }

        /* ── Ponto de status da aula ── */
        .ms-lesson-dot {
            width: 0.625rem; height: 0.625rem; border-radius: 50%; flex-shrink: 0; margin-top: 0.375rem;
        }

        /* ── Grade responsiva ── */
        @media (max-width: 900px) {
            .ms-two-col { grid-template-columns: 1fr !important; }
        }
        @media (max-width: 640px) {
            .ms-stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>

    @php
        $data                = $this->getPageData();
        $student             = $data['student'];
        $currentClass        = $data['currentClass'];
        $schoolYear          = $data['schoolYear'];
        $attendance          = $data['attendance'];
        $grades              = $data['grades'];
        $todayLessons        = $data['todayLessons'];
        $upcomingAssessments = $data['upcomingAssessments'];
        $recentGrades        = $data['recentGrades'];

        $now = now();

        // Cor por percentual
        $freqColor  = fn($r) => $r >= 75 ? '#22c55e' : ($r >= 60 ? '#eab308' : '#ef4444');
        $gradeColor = fn($v) => $v === null ? 'var(--ms-text-muted)' : ($v >= 6.0 ? '#22c55e' : ($v >= 4.0 ? '#eab308' : '#ef4444'));

        // Iniciais do nome
        $initials = collect(explode(' ', $student?->name ?? 'A'))
            ->filter()->take(2)->map(fn($w) => strtoupper($w[0]))->implode('');

        $freqRate = $attendance['frequency'] ?? 0;
        $freqC    = $freqColor($freqRate);
        $gradeAvg = $grades['average'];
        $gradeC   = $gradeColor($gradeAvg);

        $assessmentTypeLabels = [
            'test'          => 'Prova',
            'quiz'          => 'Quiz',
            'work'          => 'Trabalho',
            'project'       => 'Projeto',
            'participation' => 'Participação',
            'recovery'      => 'Recuperação',
        ];
    @endphp

    @if(!$student)
        <div class="ms-card" style="padding:3rem;text-align:center">
            <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                @svg('heroicon-o-exclamation-triangle', '', ['style' => 'width:2rem;height:2rem;color:#ef4444'])
            </div>
            <h3 style="font-size:1.125rem;font-weight:600;color:var(--ms-text-primary);margin:0 0 0.5rem">Perfil de aluno não encontrado</h3>
            <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">
                Seu usuário não está vinculado a um cadastro de aluno. Contate a secretaria.
            </p>
        </div>
    @elseif(!$currentClass)
        <div class="ms-card" style="padding:3rem;text-align:center">
            <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                @svg('heroicon-o-academic-cap', '', ['style' => 'width:2rem;height:2rem;color:#f59e0b'])
            </div>
            <h3 style="font-size:1.125rem;font-weight:600;color:var(--ms-text-primary);margin:0 0 0.5rem">Nenhuma turma ativa</h3>
            <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">
                Você não está matriculado em nenhuma turma no ano letivo vigente. Contate a secretaria.
            </p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            {{-- ▸ Banner de perfil --}}
            <div class="ms-card" style="padding:1.5rem;overflow:hidden;position:relative">
                {{-- Faixa decorativa --}}
                <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#f59e0b,#d97706)"></div>

                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem;margin-top:0.5rem">
                    {{-- Identidade --}}
                    <div style="display:flex;align-items:center;gap:1.125rem">
                        @if($student->photo_url)
                            <img src="{{ $student->photo_url }}" alt="{{ $student->name }}"
                                 style="width:4rem;height:4rem;border-radius:50%;object-fit:cover;border:2px solid #f59e0b;flex-shrink:0">
                        @else
                            <div class="ms-avatar">{{ $initials }}</div>
                        @endif
                        <div>
                            <h1 style="font-size:1.25rem;font-weight:800;color:var(--ms-text-primary);margin:0">
                                {{ $student->name }}
                            </h1>
                            <div style="display:flex;align-items:center;gap:0.625rem;flex-wrap:wrap;margin-top:0.375rem">
                                <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.8rem;color:var(--ms-text-secondary)">
                                    @svg('heroicon-o-identification', '', ['style' => 'width:0.875rem;height:0.875rem'])
                                    {{ $student->registration_number }}
                                </span>
                                <span style="color:var(--ms-text-muted)">·</span>
                                <span style="font-size:0.8rem;color:var(--ms-text-secondary)">{{ $currentClass->name }}</span>
                                @if($currentClass->gradeLevel)
                                    <span style="color:var(--ms-text-muted)">·</span>
                                    <span style="font-size:0.8rem;color:var(--ms-text-secondary)">{{ $currentClass->gradeLevel->name }}</span>
                                @endif
                                @if($currentClass->shift ?? null)
                                    <span style="color:var(--ms-text-muted)">·</span>
                                    <span style="font-size:0.8rem;color:var(--ms-text-secondary)">{{ $currentClass->shift->label() }}</span>
                                @endif
                            </div>
                            <div style="margin-top:0.375rem">
                                <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;padding:0.2rem 0.625rem;border-radius:999px;background:rgba(34,197,94,0.12);color:#16a34a;font-weight:600">
                                    @svg('heroicon-o-check-circle', '', ['style' => 'width:0.75rem;height:0.75rem'])
                                    Ativo · Ano Letivo {{ $schoolYear->year ?? now()->year }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Indicadores rápidos --}}
                    <div style="display:flex;gap:1.5rem;align-items:center;flex-wrap:wrap">
                        <div style="text-align:center">
                            <p style="font-size:0.6875rem;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;color:var(--ms-text-muted);margin:0">Frequência</p>
                            <p style="font-size:1.75rem;font-weight:800;color:{{ $freqC }};margin:0.125rem 0 0;line-height:1">
                                {{ $freqRate > 0 ? number_format($freqRate, 1, ',', '') . '%' : '—' }}
                            </p>
                            @if($attendance['alert'] ?? false)
                                <p style="font-size:0.5625rem;color:#ef4444;margin:0;font-weight:600">ABAIXO DO MÍNIMO</p>
                            @endif
                        </div>
                        <div style="width:1px;height:3rem;background:var(--ms-bar-bg)"></div>
                        <div style="text-align:center">
                            <p style="font-size:0.6875rem;font-weight:500;text-transform:uppercase;letter-spacing:0.05em;color:var(--ms-text-muted);margin:0">Média Geral</p>
                            <p style="font-size:1.75rem;font-weight:800;color:{{ $gradeC }};margin:0.125rem 0 0;line-height:1">
                                {{ $gradeAvg !== null ? number_format($gradeAvg, 1, ',', '') : '—' }}
                            </p>
                            @if($grades['failed'] > 0)
                                <p style="font-size:0.5625rem;color:#ef4444;margin:0;font-weight:600">{{ $grades['failed'] }} REPROVADA(S)</p>
                            @elseif($grades['recovery'] > 0)
                                <p style="font-size:0.5625rem;color:#eab308;margin:0;font-weight:600">{{ $grades['recovery'] }} EM RECUPERAÇÃO</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ▸ Alertas críticos --}}
            @if(($attendance['alert'] ?? false) || $grades['failed'] > 0)
                <div style="display:flex;flex-direction:column;gap:0.5rem">
                    @if($attendance['alert'] ?? false)
                        <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.25);border-left:4px solid #ef4444;border-radius:0.75rem;padding:0.875rem 1.125rem;display:flex;align-items:center;gap:0.75rem">
                            @svg('heroicon-s-exclamation-triangle', '', ['style' => 'width:1.125rem;height:1.125rem;color:#ef4444;flex-shrink:0'])
                            <p style="font-size:0.875rem;color:var(--ms-text-primary);margin:0">
                                Sua <strong style="color:#ef4444">frequência está abaixo de 75%</strong>. Você pode ser reprovado(a) por falta. Procure a coordenação.
                            </p>
                        </div>
                    @endif
                    @if($grades['failed'] > 0)
                        <div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.25);border-left:4px solid #ef4444;border-radius:0.75rem;padding:0.875rem 1.125rem;display:flex;align-items:center;gap:0.75rem">
                            @svg('heroicon-s-x-circle', '', ['style' => 'width:1.125rem;height:1.125rem;color:#ef4444;flex-shrink:0'])
                            <p style="font-size:0.875rem;color:var(--ms-text-primary);margin:0">
                                Você está <strong style="color:#ef4444">reprovado(a) em {{ $grades['failed'] }} {{ $grades['failed'] === 1 ? 'disciplina' : 'disciplinas' }}</strong>. Acesse <a href="{{ url('/lumina/my-grades') }}" style="color:#ef4444;text-decoration:underline">Minhas Notas</a> para mais detalhes.
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ▸ Cards de resumo rápido --}}
            <div class="ms-stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                @php
                    $summaryCards = [
                        [
                            'icon'  => 'heroicon-o-check-circle',
                            'value' => $freqRate > 0 ? number_format($freqRate, 1, ',', '') . '%' : '—',
                            'label' => 'Frequência',
                            'color' => $freqC,
                            'sub'   => ($attendance['total'] ?? 0) . ' aulas registradas',
                        ],
                        [
                            'icon'  => 'heroicon-o-chart-bar',
                            'value' => $gradeAvg !== null ? number_format($gradeAvg, 1, ',', '') : '—',
                            'label' => 'Média Geral',
                            'color' => $gradeC,
                            'sub'   => ($grades['total'] ?? 0) . ' disciplinas',
                        ],
                        [
                            'icon'  => 'heroicon-o-calendar-days',
                            'value' => $todayLessons->count(),
                            'label' => 'Aulas Hoje',
                            'color' => '#f59e0b',
                            'sub'   => now()->translatedFormat('l, d/m'),
                        ],
                        [
                            'icon'  => 'heroicon-o-clipboard-document-list',
                            'value' => $upcomingAssessments->count(),
                            'label' => 'Próximas Avaliações',
                            'color' => '#a855f7',
                            'sub'   => 'nos próximos 7 dias',
                        ],
                    ];
                @endphp
                @foreach($summaryCards as $sc)
                    <div class="ms-card" style="padding:1rem">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <div style="width:2.75rem;height:2.75rem;border-radius:0.625rem;background:{{ $sc['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                @svg($sc['icon'], '', ['style' => 'width:1.25rem;height:1.25rem;color:'.$sc['color']])
                            </div>
                            <div style="min-width:0">
                                <p style="font-size:1.375rem;font-weight:800;color:{{ $sc['color'] }};margin:0;line-height:1.2">{{ $sc['value'] }}</p>
                                <p style="font-size:0.6875rem;font-weight:600;color:var(--ms-text-primary);margin:0">{{ $sc['label'] }}</p>
                                <p style="font-size:0.5625rem;color:var(--ms-text-muted);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $sc['sub'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ▸ Grade principal: Agenda + Avaliações --}}
            <div class="ms-two-col" style="display:grid;grid-template-columns:3fr 2fr;gap:1rem">

                {{-- Agenda do dia --}}
                <div class="ms-card" style="padding:1.25rem">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem">
                        @svg('heroicon-o-clock', '', ['style' => 'width:1.125rem;height:1.125rem;color:#f59e0b'])
                        <h3 style="font-size:1rem;font-weight:700;color:var(--ms-text-primary);margin:0">Agenda de Hoje</h3>
                        <span style="font-size:0.75rem;color:var(--ms-text-muted);margin-left:auto">{{ now()->translatedFormat('l, d \d\e F') }}</span>
                    </div>

                    @if($todayLessons->isEmpty())
                        <div style="padding:2rem;text-align:center">
                            <div style="width:3rem;height:3rem;border-radius:50%;background:rgba(100,116,139,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem">
                                @svg('heroicon-o-calendar-days', '', ['style' => 'width:1.5rem;height:1.5rem;color:var(--ms-text-muted)'])
                            </div>
                            <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">Nenhuma aula agendada para hoje.</p>
                        </div>
                    @else
                        @foreach($todayLessons as $lesson)
                            @php
                                $start  = \Carbon\Carbon::parse($lesson->start_time);
                                $end    = \Carbon\Carbon::parse($lesson->end_time);
                                $isPast    = $end->lt($now);
                                $isCurrent = $start->lte($now) && $end->gte($now);
                                $dotColor  = $isCurrent ? '#22c55e' : ($isPast ? 'var(--ms-text-muted)' : '#f59e0b');
                            @endphp
                            <div class="ms-lesson-row" style="{{ $isPast ? 'opacity:0.55' : '' }}">
                                {{-- Indicador de status --}}
                                <div style="display:flex;flex-direction:column;align-items:center;gap:3px;padding-top:2px">
                                    <div class="ms-lesson-dot" style="background:{{ $dotColor }};{{ $isCurrent ? 'box-shadow:0 0 0 3px '.$dotColor.'33' : '' }}"></div>
                                </div>

                                {{-- Horário --}}
                                <div style="text-align:right;flex-shrink:0;min-width:4.5rem">
                                    <p style="font-size:0.8125rem;font-weight:700;color:var(--ms-text-primary);margin:0">{{ $start->format('H:i') }}</p>
                                    <p style="font-size:0.6875rem;color:var(--ms-text-muted);margin:0">{{ $end->format('H:i') }}</p>
                                </div>

                                {{-- Conteúdo --}}
                                <div style="flex:1;min-width:0">
                                    <p style="font-size:0.9375rem;font-weight:600;color:var(--ms-text-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        {{ $lesson->subject?->name ?? 'Disciplina' }}
                                    </p>
                                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.75rem;color:var(--ms-text-muted);margin-top:0.125rem;flex-wrap:wrap">
                                        @if($lesson->teacher?->user?->name)
                                            <span>Prof. {{ $lesson->teacher->user->name }}</span>
                                        @endif
                                        @if($lesson->topic)
                                            <span style="color:var(--ms-text-muted)">·</span>
                                            <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:12rem">{{ $lesson->topic }}</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Badge de status --}}
                                @if($isCurrent)
                                    <span style="padding:0.25rem 0.5rem;border-radius:999px;font-size:0.5625rem;font-weight:700;background:rgba(34,197,94,0.15);color:#16a34a;flex-shrink:0">EM ANDAMENTO</span>
                                @elseif($isPast)
                                    <span style="padding:0.25rem 0.5rem;border-radius:999px;font-size:0.5625rem;font-weight:600;background:var(--ms-cell-bg);color:var(--ms-text-muted);flex-shrink:0">ENCERRADA</span>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Próximas avaliações --}}
                <div class="ms-card" style="padding:1.25rem">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem">
                        @svg('heroicon-o-clipboard-document-list', '', ['style' => 'width:1.125rem;height:1.125rem;color:#a855f7'])
                        <h3 style="font-size:1rem;font-weight:700;color:var(--ms-text-primary);margin:0">Próximas Avaliações</h3>
                    </div>

                    @if($upcomingAssessments->isEmpty())
                        <div style="padding:1.5rem;text-align:center">
                            <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">Nenhuma avaliação nos próximos 7 dias.</p>
                        </div>
                    @else
                        <div style="display:flex;flex-direction:column;gap:0.625rem">
                            @foreach($upcomingAssessments as $assessment)
                                @php
                                    $daysUntil = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($assessment->scheduled_at)->startOfDay(), false);
                                    $isUrgent  = $daysUntil <= 2;
                                    $urgColor  = $isUrgent ? '#ef4444' : ($daysUntil <= 4 ? '#eab308' : '#a855f7');
                                @endphp
                                <div style="display:flex;align-items:flex-start;gap:0.75rem;padding:0.625rem;border-radius:0.5rem;background:var(--ms-cell-bg)">
                                    {{-- Data --}}
                                    <div style="text-align:center;background:{{ $urgColor }}1a;border-radius:0.375rem;padding:0.25rem 0.5rem;flex-shrink:0;min-width:2.5rem">
                                        <p style="font-size:1rem;font-weight:800;color:{{ $urgColor }};margin:0;line-height:1">{{ \Carbon\Carbon::parse($assessment->scheduled_at)->format('d') }}</p>
                                        <p style="font-size:0.5rem;text-transform:uppercase;color:{{ $urgColor }};margin:0">{{ \Carbon\Carbon::parse($assessment->scheduled_at)->translatedFormat('M') }}</p>
                                    </div>
                                    {{-- Info --}}
                                    <div style="min-width:0;flex:1">
                                        <p style="font-size:0.8125rem;font-weight:600;color:var(--ms-text-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            {{ $assessment->title ?? 'Avaliação' }}
                                        </p>
                                        <p style="font-size:0.6875rem;color:var(--ms-text-secondary);margin:0.125rem 0 0">
                                            {{ $assessment->subject?->name ?? '—' }}
                                            @if($assessment->weight && $assessment->weight != 1)
                                                · peso {{ $assessment->weight }}
                                            @endif
                                        </p>
                                        @if($isUrgent)
                                            <span style="font-size:0.5625rem;font-weight:700;color:#ef4444">
                                                {{ $daysUntil === 0 ? 'HOJE' : ($daysUntil === 1 ? 'AMANHÃ' : "EM {$daysUntil} DIAS") }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ▸ Grade secundária: Últimas Notas + Atalhos --}}
            <div class="ms-two-col" style="display:grid;grid-template-columns:3fr 2fr;gap:1rem">

                {{-- Últimas notas --}}
                <div class="ms-card" style="padding:1.25rem">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem">
                        @svg('heroicon-o-chart-bar', '', ['style' => 'width:1.125rem;height:1.125rem;color:#22c55e'])
                        <h3 style="font-size:1rem;font-weight:700;color:var(--ms-text-primary);margin:0">Últimas Notas Lançadas</h3>
                        <a href="{{ url('/lumina/my-grades') }}" style="font-size:0.75rem;color:#f59e0b;margin-left:auto;text-decoration:none">Ver todas →</a>
                    </div>

                    @if($recentGrades->isEmpty())
                        <div style="padding:1.5rem;text-align:center">
                            <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">Nenhuma nota lançada ainda.</p>
                        </div>
                    @else
                        <div style="display:flex;flex-direction:column;gap:0">
                            @foreach($recentGrades as $grade)
                                @php
                                    $gc = $grade->score >= 6.0 ? '#22c55e' : ($grade->score >= 4.0 ? '#eab308' : '#ef4444');
                                    $typeLabel = $assessmentTypeLabels[$grade->assessment_type?->value ?? ''] ?? 'Avaliação';
                                @endphp
                                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0;border-bottom:1px solid var(--ms-bar-bg)">
                                    <div style="width:2.25rem;height:2.25rem;border-radius:50%;background:{{ $gc }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                        <span style="font-size:0.8125rem;font-weight:700;color:{{ $gc }}">{{ number_format($grade->score, 1, ',', '') }}</span>
                                    </div>
                                    <div style="flex:1;min-width:0">
                                        <p style="font-size:0.875rem;font-weight:600;color:var(--ms-text-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            {{ $grade->subject?->name ?? '—' }}
                                        </p>
                                        <p style="font-size:0.6875rem;color:var(--ms-text-muted);margin:0">
                                            {{ $typeLabel }}
                                            @if($grade->date_recorded)
                                                · {{ \Carbon\Carbon::parse($grade->date_recorded)->format('d/m/Y') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <span style="font-size:0.75rem;color:var(--ms-text-muted)">/{{ number_format($grade->max_score ?? 10, 0) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Atalhos rápidos --}}
                <div class="ms-card" style="padding:1.25rem">
                    <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem">
                        @svg('heroicon-o-squares-2x2', '', ['style' => 'width:1.125rem;height:1.125rem;color:#64748b'])
                        <h3 style="font-size:1rem;font-weight:700;color:var(--ms-text-primary);margin:0">Acesso Rápido</h3>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:0.5rem">
                        @php
                            $shortcuts = [
                                ['href' => '/lumina/my-grades',         'icon' => 'heroicon-o-chart-bar',        'label' => 'Minhas Notas',       'color' => '#22c55e',
                                 'badge' => $grades['failed'] > 0 ? $grades['failed'] : ($grades['recovery'] > 0 ? $grades['recovery'] : null),
                                 'badgeColor' => $grades['failed'] > 0 ? '#ef4444' : '#eab308'],
                                ['href' => '/lumina/my-subjects',       'icon' => 'heroicon-o-book-open',        'label' => 'Minhas Disciplinas', 'color' => '#06b6d4', 'badge' => null],
                                ['href' => '/lumina/student-attendance','icon' => 'heroicon-o-calendar-days',   'label' => 'Frequência',         'color' => '#f59e0b',
                                 'badge' => ($attendance['alert'] ?? false) ? '!' : null,
                                 'badgeColor' => '#ef4444'],
                                ['href' => '/lumina/academic-calendar', 'icon' => 'heroicon-o-calendar',         'label' => 'Calendário Escolar', 'color' => '#a855f7', 'badge' => null],
                            ];
                        @endphp
                        @foreach($shortcuts as $sh)
                            <a href="{{ url($sh['href']) }}" class="ms-shortcut">
                                <div style="width:2.25rem;height:2.25rem;border-radius:0.5rem;background:{{ $sh['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                    @svg($sh['icon'], '', ['style' => 'width:1.125rem;height:1.125rem;color:'.$sh['color']])
                                </div>
                                <span style="font-size:0.9rem;font-weight:600;color:var(--ms-text-primary);flex:1">{{ $sh['label'] }}</span>
                                @if($sh['badge'] !== null)
                                    <span style="width:1.25rem;height:1.25rem;border-radius:50%;background:{{ $sh['badgeColor'] }};color:#fff;font-size:0.625rem;font-weight:700;display:flex;align-items:center;justify-content:center">{{ $sh['badge'] }}</span>
                                @endif
                                @svg('heroicon-o-chevron-right', '', ['style' => 'width:0.875rem;height:0.875rem;color:var(--ms-text-muted)'])
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    @endif

</x-filament-panels::page>
