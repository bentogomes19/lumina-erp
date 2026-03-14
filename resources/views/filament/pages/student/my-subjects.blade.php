<x-filament-panels::page>
    <style>
        /* ── Light mode (default — sem classe .dark no html) ── */
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

        /* ── Dark mode — controlado pela classe .dark do Filament ── */
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

        /* ── Base card styles ── */
        .ms-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.75rem;
        }

        /* ── Subject card (link) ── */
        a.ms-subject-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.75rem;
            overflow: hidden;
            text-decoration: none;
            display: block;
            transition: background 0.2s, border-color 0.2s, transform 0.2s;
        }
        a.ms-subject-card:hover {
            background: var(--ms-hover-bg);
            border-color: color-mix(in srgb, var(--ms-card-border) 60%, currentColor 40%);
            transform: translateY(-2px);
        }

        /* ── Responsive grid ── */
        @media (max-width: 768px) {
            .ms-stats-grid  { grid-template-columns: repeat(2, 1fr) !important; }
            .ms-subject-grid { grid-template-columns: 1fr !important; }
        }

        .ms-icon-xs { width: 0.875rem !important; height: 0.875rem !important; }
        .ms-icon-sm { width: 1rem !important; height: 1rem !important; }
        .ms-icon-md { width: 1.125rem !important; height: 1.125rem !important; }
        .ms-icon-lg { width: 1.4rem !important; height: 1.4rem !important; }
        .ms-icon-xl { width: 1.75rem !important; height: 1.75rem !important; }
    </style>

    @php
        $data = $this->getPageData();
        $student = $data['student'];
        $currentClass = $data['currentClass'];
        $subjects = $data['subjects'];
        $stats = $data['stats'];

        $categoryStyles = [
            'linguagens'          => ['accent' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)', 'text' => '#7c3aed'],
            'matematica'          => ['accent' => '#f43f5e', 'bg' => 'rgba(244,63,94,0.12)',   'text' => '#e11d48'],
            'ciencias_da_natureza'=> ['accent' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)',  'text' => '#059669'],
            'ciencias_humanas'    => ['accent' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)',  'text' => '#d97706'],
            'ciencias_exatas'     => ['accent' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.12)',   'text' => '#0891b2'],
        ];
        $defaultStyle = ['accent' => '#6b7280', 'bg' => 'rgba(107,114,128,0.12)', 'text' => '#6b7280'];
    @endphp

    <div style="display:flex;flex-direction:column;gap:1.5rem">

        {{-- ▸ Class info header --}}
        @if($currentClass)
            <div class="ms-card" style="padding:1.25rem 1.5rem">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                    <div style="display:flex;align-items:center;gap:1rem">
                        <div
                            style="width:2.75rem;height:2.75rem;border-radius:0.5rem;background:rgba(217,119,6,0.15);display:flex;align-items:center;justify-content:center">
                            @svg('heroicon-o-academic-cap', 'ms-icon-lg', ['style' => 'color:#fbbf24'])
                        </div>
                        <div>
                            <h2 style="font-size:1.125rem;font-weight:700;color:var(--ms-text-primary);margin:0">
                                {{ $currentClass->name }}
                            </h2>
                            <p style="font-size:0.8125rem;color:var(--ms-text-secondary);margin:0.125rem 0 0 0">
                                {{ $currentClass->gradeLevel?->name ?? '' }}
                                @if($currentClass->shift)
                                    &middot; Turno: {{ $currentClass->shift->label() }}
                                @endif
                                @if($currentClass->schoolYear)
                                    &middot; Ano Letivo:
                                    {{ $currentClass->schoolYear->year ?? $currentClass->schoolYear->name ?? '' }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:var(--ms-text-secondary)">
                        @svg('heroicon-o-identification', 'ms-icon-sm', ['style' => 'color:var(--ms-text-secondary)'])
                        <span>Matrícula: <strong style="color:var(--ms-text-primary)">{{ $student?->registration_number }}</strong></span>
                    </div>
                </div>
            </div>
        @endif

        {{-- ▸ Stats overview cards --}}
        @if($currentClass && $subjects->isNotEmpty())
            <div class="ms-stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                @php
                    $avgColor  = ($stats['overall_average'] !== null && $stats['overall_average'] >= 6) ? '#22c55e' : '#ef4444';
                    $freqColor = ($stats['attendance_percent'] !== null && $stats['attendance_percent'] >= 75) ? '#22c55e' : '#f97316';
                    $statCards = [
                        ['icon' => 'heroicon-o-book-open',   'value' => $stats['total_subjects'],                                                                                              'label' => 'Disciplinas',  'color' => '#f59e0b'],
                        ['icon' => 'heroicon-o-chart-bar',   'value' => $stats['overall_average'] !== null    ? number_format($stats['overall_average'], 1, ',', '.')    . '' : '—',           'label' => 'Média Geral',  'color' => $avgColor],
                        ['icon' => 'heroicon-o-check-circle','value' => $stats['attendance_percent'] !== null ? number_format($stats['attendance_percent'], 1, ',', '.') . '%' : '—',          'label' => 'Frequência',   'color' => $freqColor],
                        ['icon' => 'heroicon-o-clock',       'value' => $stats['total_hours_weekly'] ?: '—',                                                                                   'label' => 'Horas/Semana', 'color' => '#a855f7'],
                    ];
                @endphp
                @foreach($statCards as $card)
                    <div class="ms-card" style="padding:1rem">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <div
                                style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:{{ $card['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                @svg($card['icon'], 'ms-icon-md', ['style' => 'color:' . $card['color']])
                            </div>
                            <div>
                                <p style="font-size:1.5rem;font-weight:700;color:var(--ms-text-primary);margin:0;line-height:1.2">
                                    {{ $card['value'] }}</p>
                                <p style="font-size:0.6875rem;color:var(--ms-text-secondary);margin:0">{{ $card['label'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ▸ Subjects grouped by category --}}
        @if($subjects->isNotEmpty())
            @php
                $grouped = $subjects->groupBy(fn($s) => $s->category?->value ?? 'sem_categoria');
            @endphp

            @foreach($grouped as $categoryKey => $categorySubjects)
                @php
                    $cs = $categoryStyles[$categoryKey] ?? $defaultStyle;
                    $categoryLabel = $categorySubjects->first()->category?->label() ?? 'Sem Categoria';
                @endphp

                <div style="display:flex;flex-direction:column;gap:0.75rem">
                    {{-- Category header --}}
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0 0.25rem">
                        <div
                            style="width:0.625rem;height:0.625rem;border-radius:50%;background:{{ $cs['accent'] }};flex-shrink:0">
                        </div>
                        <h3
                            style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:{{ $cs['text'] }};margin:0">
                            {{ $categoryLabel }}
                        </h3>
                        <div style="flex:1;border-bottom:1px solid {{ $cs['accent'] }}33"></div>
                        <span style="font-size:0.6875rem;color:{{ $cs['text'] }}">{{ $categorySubjects->count() }}
                            {{ $categorySubjects->count() === 1 ? 'disciplina' : 'disciplinas' }}</span>
                    </div>

                    {{-- Subject cards grid --}}
                    <div class="ms-subject-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                        @foreach($categorySubjects as $subject)
                            @php $cs = $categoryStyles[$subject->category?->value ?? ''] ?? $defaultStyle; @endphp
                            <a href="{{ url('/lumina/subject-detail?subject=' . $subject->id) }}"
                                class="ms-subject-card">
                                {{-- Color accent bar --}}
                                <div style="height:3px;background:{{ $cs['accent'] }}"></div>

                                <div style="padding:1.25rem;display:flex;flex-direction:column;gap:0.875rem">
                                    {{-- Subject header --}}
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem">
                                        <div style="display:flex;align-items:center;gap:0.75rem;min-width:0">
                                            <div
                                                style="width:2.25rem;height:2.25rem;border-radius:0.5rem;background:{{ $cs['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                                @svg('heroicon-o-book-open', 'ms-icon-md', ['style' => 'color:' . $cs['accent']])
                                            </div>
                                            <div style="min-width:0">
                                                <h4
                                                    style="font-size:0.9375rem;font-weight:600;color:var(--ms-text-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                                    {{ $subject->name }}
                                                </h4>
                                                <div
                                                    style="display:flex;align-items:center;gap:0.375rem;font-size:0.6875rem;color:var(--ms-text-secondary);margin-top:0.125rem">
                                                    @if($subject->code)
                                                        <span style="font-family:monospace">{{ $subject->code }}</span>
                                                    @endif
                                                    @if($subject->hours_weekly)
                                                        <span>&middot; {{ $subject->hours_weekly }}h/sem</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Overall average badge --}}
                                        @if($subject->overall_average !== null)
                                            @php
                                                $badgeBg    = $subject->overall_average >= 7 ? 'rgba(34,197,94,0.15)'  : ($subject->overall_average >= 5 ? 'rgba(234,179,8,0.15)'  : 'rgba(239,68,68,0.15)');
                                                $badgeColor = $subject->overall_average >= 7 ? '#22c55e'               : ($subject->overall_average >= 5 ? '#eab308'               : '#ef4444');
                                            @endphp
                                            <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center">
                                                <span
                                                    style="width:2.5rem;height:2.5rem;border-radius:50%;background:{{ $badgeBg }};color:{{ $badgeColor }};font-size:0.8125rem;font-weight:700;display:flex;align-items:center;justify-content:center">
                                                    {{ number_format($subject->overall_average, 1, ',', '') }}
                                                </span>
                                                <span style="font-size:0.5625rem;color:var(--ms-text-muted);margin-top:2px">Média</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Teacher --}}
                                    @if($subject->teacher_name)
                                        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:var(--ms-text-secondary)">
                                            @svg('heroicon-o-user', 'ms-icon-xs', ['style' => 'color:var(--ms-text-muted);flex-shrink:0'])
                                            <span>Prof. {{ $subject->teacher_name }}</span>
                                        </div>
                                    @endif

                                    {{-- Term grades --}}
                                    @if(collect($subject->term_averages)->filter()->isNotEmpty())
                                        <div style="display:flex;flex-direction:column;gap:0.375rem">
                                            <p
                                                style="font-size:0.6875rem;font-weight:500;color:var(--ms-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0">
                                                Notas por Bimestre</p>
                                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.375rem">
                                                @foreach(['b1' => '1º Bim', 'b2' => '2º Bim', 'b3' => '3º Bim', 'b4' => '4º Bim'] as $termKey => $termLabel)
                                                    @php
                                                        $termVal   = $subject->term_averages[$termKey];
                                                        $termColor = $termVal !== null ? ($termVal >= 7 ? '#22c55e' : ($termVal >= 5 ? '#eab308' : '#ef4444')) : 'var(--ms-text-muted)';
                                                    @endphp
                                                    <div style="border-radius:0.375rem;padding:0.375rem;text-align:center;background:var(--ms-cell-bg)">
                                                        <p style="font-size:0.5625rem;color:var(--ms-text-muted);margin:0">{{ $termLabel }}</p>
                                                        <p style="font-size:0.8125rem;font-weight:700;color:{{ $termColor }};margin:0.125rem 0 0 0">
                                                            {{ $termVal !== null ? number_format($termVal, 1, ',', '') : '—' }}
                                                        </p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Attendance bar --}}
                                    @if($subject->attendance_percent !== null)
                                        @php
                                            $barColor = $subject->attendance_percent >= 75 ? '#22c55e' : ($subject->attendance_percent >= 50 ? '#eab308' : '#ef4444');
                                        @endphp
                                        <div style="display:flex;flex-direction:column;gap:0.25rem">
                                            <div
                                                style="display:flex;align-items:center;justify-content:space-between;font-size:0.6875rem">
                                                <span style="color:var(--ms-text-secondary);font-weight:500">Frequência</span>
                                                <div style="display:flex;align-items:center;gap:0.5rem">
                                                    <span style="color:var(--ms-text-muted)">{{ $subject->presences }}P /
                                                        {{ $subject->absences }}F</span>
                                                    <span style="font-weight:600;color:{{ $barColor }}">
                                                        {{ number_format($subject->attendance_percent, 1, ',', '') }}%
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="width:100%;height:5px;border-radius:999px;background:var(--ms-bar-bg);overflow:hidden">
                                                <div
                                                    style="height:100%;border-radius:999px;background:{{ $barColor }};width:{{ min($subject->attendance_percent, 100) }}%;transition:width 0.3s">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Description --}}
                                    @if($subject->description)
                                        <p
                                            style="font-size:0.6875rem;color:var(--ms-text-muted);margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
                                            {{ $subject->description }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @else
            {{-- Empty state --}}
            <div class="ms-card" style="padding:3rem;text-align:center">
                <div
                    style="width:4rem;height:4rem;border-radius:50%;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                    @svg('heroicon-o-book-open', 'ms-icon-xl', ['style' => 'color:#f59e0b'])
                </div>
                <h3 style="font-size:1.125rem;font-weight:600;color:var(--ms-text-primary);margin:0 0 0.5rem">
                    Nenhuma Disciplina Encontrada
                </h3>
                <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0;max-width:28rem;margin-left:auto;margin-right:auto">
                    @if(!$currentClass)
                        Você não está matriculado em nenhuma turma no ano letivo vigente.
                    @else
                        Nenhuma disciplina foi atribuída à sua turma ({{ $currentClass->name }}) até o momento.
                    @endif
                </p>
            </div>
        @endif

    </div>
</x-filament-panels::page>
