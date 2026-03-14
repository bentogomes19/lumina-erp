<x-filament-panels::page>
    <style>
        /* ── Light mode ── */
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
        /* ── Dark mode via Filament .dark ── */
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

        .ms-period-btn {
            padding: 0.375rem 0.875rem; border-radius: 999px; font-size: 0.8125rem;
            font-weight: 500; cursor: pointer; border: 1px solid var(--ms-card-border);
            background: var(--ms-card-bg); color: var(--ms-text-secondary); transition: all 0.15s;
        }
        .ms-period-btn:hover  { background: var(--ms-hover-bg); }
        .ms-period-btn.active { background: #f59e0b; border-color: #f59e0b; color: #fff; }

        .ms-freq-bar-track { width: 100%; height: 5px; border-radius: 999px; background: var(--ms-bar-bg); overflow: hidden; }

        .ms-assess-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: 0.5rem; padding: 0.375rem 0; font-size: 0.75rem;
            border-bottom: 1px solid var(--ms-bar-bg);
        }
        .ms-assess-row:last-child { border-bottom: none; }

        @media (max-width: 768px) {
            .ms-stats-grid   { grid-template-columns: repeat(3, 1fr) !important; }
            .ms-subject-grid { grid-template-columns: 1fr !important; }
            .ms-term-grid    { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>

    @php
        $data         = $this->getPageData();
        $student      = $data['student'];
        $currentClass = $data['currentClass'];
        $subjects     = $data['subjects'];
        $stats        = $data['stats'];
        $minApproval  = $data['min_approval'];
        $period       = $data['selected_period'];
        $periodLabel  = $data['period_label'];

        $periods = ['all' => 'Ano Letivo', 'b1' => '1º Bim', 'b2' => '2º Bim', 'b3' => '3º Bim', 'b4' => '4º Bim'];

        $statusCfg = [
            'approved' => ['color' => '#22c55e', 'label' => 'Aprovado',    'bg' => 'rgba(34,197,94,0.12)'],
            'recovery' => ['color' => '#eab308', 'label' => 'Recuperação', 'bg' => 'rgba(234,179,8,0.12)'],
            'failed'   => ['color' => '#ef4444', 'label' => 'Reprovado',   'bg' => 'rgba(239,68,68,0.12)'],
            'ongoing'  => ['color' => '#64748b', 'label' => 'Cursando',    'bg' => 'rgba(100,116,139,0.12)'],
        ];

        $avgColor = fn($v) => $v === null ? 'var(--ms-text-muted)' : ($v >= $minApproval ? '#22c55e' : ($v >= 4.0 ? '#eab308' : '#ef4444'));

        $termLabels = ['b1' => '1º Bim', 'b2' => '2º Bim', 'b3' => '3º Bim', 'b4' => '4º Bim'];
    @endphp

    @if(!$student || !$currentClass)
        <div class="ms-card" style="padding:3rem;text-align:center">
            <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                @svg('heroicon-o-chart-bar', '', ['style' => 'width:2rem;height:2rem;color:#f59e0b'])
            </div>
            <h3 style="font-size:1.125rem;font-weight:600;color:var(--ms-text-primary);margin:0 0 0.5rem">
                Nenhuma turma ativa encontrada
            </h3>
            <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">
                Você não está matriculado em nenhuma turma no ano letivo vigente.
            </p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            {{-- ▸ Class header --}}
            <div class="ms-card" style="padding:1.25rem 1.5rem">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                    <div style="display:flex;align-items:center;gap:1rem">
                        <div style="width:2.75rem;height:2.75rem;border-radius:0.5rem;background:rgba(217,119,6,0.15);display:flex;align-items:center;justify-content:center">
                            @svg('heroicon-o-academic-cap', '', ['style' => 'width:1.4rem;height:1.4rem;color:#fbbf24'])
                        </div>
                        <div>
                            <h2 style="font-size:1.125rem;font-weight:700;color:var(--ms-text-primary);margin:0">{{ $currentClass->name }}</h2>
                            <p style="font-size:0.8125rem;color:var(--ms-text-secondary);margin:0.125rem 0 0">
                                {{ $currentClass->gradeLevel?->name ?? '' }}
                                @if($currentClass->schoolYear)
                                    &middot; Ano Letivo: {{ $currentClass->schoolYear->year ?? $currentClass->schoolYear->name ?? '' }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:var(--ms-text-secondary)">
                        @svg('heroicon-o-identification', '', ['style' => 'width:1rem;height:1rem'])
                        <span>Matrícula: <strong style="color:var(--ms-text-primary)">{{ $student->registration_number }}</strong></span>
                    </div>
                </div>
            </div>

            {{-- ▸ Period filter --}}
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
                @foreach($periods as $key => $label)
                    <button wire:click="setPeriod('{{ $key }}')" class="ms-period-btn {{ $period === $key ? 'active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
                <span style="font-size:0.8125rem;color:var(--ms-text-muted);margin-left:0.25rem">{{ $periodLabel }}</span>
            </div>

            {{-- ▸ Alerts --}}
            @if($stats['failed'] > 0 || $stats['recovery'] > 0)
                <div style="display:flex;flex-direction:column;gap:0.5rem">
                    @if($stats['failed'] > 0)
                        <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-left:4px solid #ef4444;border-radius:0.75rem;padding:0.875rem 1.125rem;display:flex;align-items:center;gap:0.75rem">
                            @svg('heroicon-s-x-circle', '', ['style' => 'width:1.125rem;height:1.125rem;color:#ef4444;flex-shrink:0'])
                            <p style="font-size:0.875rem;color:var(--ms-text-primary);margin:0">
                                Você está <strong style="color:#ef4444">reprovado(a)</strong> em
                                <strong style="color:#ef4444">{{ $stats['failed'] }} {{ $stats['failed'] === 1 ? 'disciplina' : 'disciplinas' }}</strong>.
                                Procure a coordenação para regularizar sua situação.
                            </p>
                        </div>
                    @endif
                    @if($stats['recovery'] > 0)
                        <div style="background:rgba(234,179,8,0.08);border:1px solid rgba(234,179,8,0.25);border-left:4px solid #eab308;border-radius:0.75rem;padding:0.875rem 1.125rem;display:flex;align-items:center;gap:0.75rem">
                            @svg('heroicon-s-exclamation-triangle', '', ['style' => 'width:1.125rem;height:1.125rem;color:#eab308;flex-shrink:0'])
                            <p style="font-size:0.875rem;color:var(--ms-text-primary);margin:0">
                                Você está convocado(a) para <strong style="color:#eab308">recuperação</strong> em
                                <strong style="color:#eab308">{{ $stats['recovery'] }} {{ $stats['recovery'] === 1 ? 'disciplina' : 'disciplinas' }}</strong>.
                            </p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ▸ Overall stats --}}
            <div class="ms-stats-grid" style="display:grid;grid-template-columns:repeat(6,1fr);gap:1rem">
                @php
                    $overallCards = [
                        ['icon'=>'heroicon-o-book-open',   'value'=>$stats['total'],    'label'=>'Disciplinas',  'color'=>'#f59e0b'],
                        ['icon'=>'heroicon-o-check-circle', 'value'=>$stats['approved'],'label'=>'Aprovadas',    'color'=>'#22c55e'],
                        ['icon'=>'heroicon-o-arrow-path',   'value'=>$stats['recovery'],'label'=>'Recuperação',  'color'=>'#eab308'],
                        ['icon'=>'heroicon-o-x-circle',     'value'=>$stats['failed'],  'label'=>'Reprovadas',   'color'=>'#ef4444'],
                        ['icon'=>'heroicon-o-clock',        'value'=>$stats['ongoing'], 'label'=>'Cursando',     'color'=>'#64748b'],
                    ];
                @endphp
                @foreach($overallCards as $card)
                    <div class="ms-card" style="padding:0.875rem">
                        <div style="display:flex;align-items:center;gap:0.625rem">
                            <div style="width:2.25rem;height:2.25rem;border-radius:0.5rem;background:{{ $card['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                @svg($card['icon'], '', ['style' => 'width:1.125rem;height:1.125rem;color:'.$card['color']])
                            </div>
                            <div>
                                <p style="font-size:1.25rem;font-weight:700;color:var(--ms-text-primary);margin:0;line-height:1.2">{{ $card['value'] }}</p>
                                <p style="font-size:0.6rem;color:var(--ms-text-secondary);margin:0;text-transform:uppercase;letter-spacing:0.04em">{{ $card['label'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Overall average --}}
                @if($stats['average'] !== null)
                    @php $oc = $avgColor($stats['average']); @endphp
                    <div class="ms-card" style="padding:0.875rem;border-color:{{ $oc }}44">
                        <div style="display:flex;align-items:center;gap:0.625rem">
                            <div style="width:2.25rem;height:2.25rem;border-radius:50%;background:{{ $oc }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                @svg('heroicon-o-star', '', ['style' => 'width:1.125rem;height:1.125rem;color:'.$oc])
                            </div>
                            <div>
                                <p style="font-size:1.25rem;font-weight:700;color:{{ $oc }};margin:0;line-height:1.2">{{ number_format($stats['average'], 1, ',', '') }}</p>
                                <p style="font-size:0.6rem;color:var(--ms-text-secondary);margin:0;text-transform:uppercase;letter-spacing:0.04em">Média Geral</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ▸ Subject cards --}}
            @if(!empty($subjects))
                <div class="ms-subject-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                    @foreach($subjects as $item)
                        @php
                            $subject = $item['subject'];
                            $sc      = $statusCfg[$item['status']] ?? $statusCfg['ongoing'];
                            $overall = $item['overall_average'];
                            $oc      = $avgColor($overall);
                            $terms   = $item['terms'];
                            $showAllTerms = ($period === 'all');
                        @endphp

                        <div class="ms-card" style="overflow:hidden;border-left:3px solid {{ $sc['color'] }}">
                            {{-- Top accent bar --}}
                            <div style="height:2px;background:{{ $sc['color'] }}55"></div>

                            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1rem">

                                {{-- Subject header --}}
                                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem">
                                    <div style="min-width:0">
                                        <h4 style="font-size:1rem;font-weight:700;color:var(--ms-text-primary);margin:0">
                                            {{ $subject?->name ?? 'Disciplina' }}
                                        </h4>
                                        @if($subject?->code)
                                            <span style="font-size:0.6875rem;color:var(--ms-text-muted);font-family:monospace">{{ $subject->code }}</span>
                                        @endif
                                    </div>
                                    <div style="display:flex;align-items:center;gap:0.625rem;flex-shrink:0">
                                        {{-- Status badge --}}
                                        <span style="padding:0.25rem 0.625rem;border-radius:999px;font-size:0.6875rem;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['color'] }}">
                                            {{ $sc['label'] }}
                                        </span>
                                        {{-- Overall average circle --}}
                                        @if($overall !== null)
                                            <div style="width:3rem;height:3rem;border-radius:50%;background:{{ $oc }}1a;border:2px solid {{ $oc }}44;display:flex;align-items:center;justify-content:center">
                                                <span style="font-size:0.9375rem;font-weight:700;color:{{ $oc }}">{{ number_format($overall, 1, ',', '') }}</span>
                                            </div>
                                        @else
                                            <div style="width:3rem;height:3rem;border-radius:50%;background:var(--ms-cell-bg);display:flex;align-items:center;justify-content:center">
                                                <span style="font-size:1rem;color:var(--ms-text-muted)">—</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Term averages grid (visible when period=all) --}}
                                @if($showAllTerms)
                                    <div class="ms-term-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.5rem">
                                        @foreach($termLabels as $termKey => $termLabel)
                                            @php
                                                $td  = $terms[$termKey];
                                                $tc  = $avgColor($td['final_average']);
                                                $haR = $td['recovery'] !== null;
                                            @endphp
                                            <div style="border-radius:0.5rem;padding:0.625rem 0.5rem;text-align:center;background:var(--ms-cell-bg);position:relative">
                                                <p style="font-size:0.5625rem;text-transform:uppercase;letter-spacing:0.05em;color:var(--ms-text-muted);margin:0">{{ $termLabel }}</p>
                                                <p style="font-size:1.125rem;font-weight:700;color:{{ $td['final_average'] !== null ? $tc : 'var(--ms-text-muted)' }};margin:0.25rem 0 0">
                                                    {{ $td['final_average'] !== null ? number_format($td['final_average'], 1, ',', '') : '—' }}
                                                </p>
                                                @if($haR)
                                                    <div style="position:absolute;top:4px;right:4px;width:6px;height:6px;border-radius:50%;background:#06b6d4" title="Recuperação lançada"></div>
                                                @endif
                                                @if($td['average'] !== null && $td['final_average'] !== $td['average'])
                                                    <p style="font-size:0.5rem;color:var(--ms-text-muted);margin:0">orig: {{ number_format($td['average'], 1, ',', '') }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Individual assessment details --}}
                                @php
                                    $termsToShow = $showAllTerms
                                        ? array_filter($terms, fn($td) => $td['has_grades'])
                                        : array_filter($terms, fn($td, $k) => $k === $period && $td['has_grades'], ARRAY_FILTER_USE_BOTH);
                                @endphp

                                @foreach($termsToShow as $termKey => $td)
                                    <div style="display:flex;flex-direction:column;gap:0.25rem">
                                        @if($showAllTerms)
                                            <p style="font-size:0.625rem;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;color:var(--ms-text-muted);margin:0">
                                                {{ $termLabels[$termKey] ?? $termKey }}
                                            </p>
                                        @endif
                                        @foreach($td['grades'] as $grade)
                                            <div class="ms-assess-row">
                                                <div style="display:flex;align-items:center;gap:0.375rem;min-width:0">
                                                    <span style="font-size:0.6875rem;color:var(--ms-text-secondary);white-space:nowrap">
                                                        {{ $grade->assessment_type?->label() ?? 'Avaliação' }}
                                                        @if($grade->sequence > 1){{ $grade->sequence }}@endif
                                                    </span>
                                                    @if($grade->date_recorded)
                                                        <span style="font-size:0.5625rem;color:var(--ms-text-muted)">{{ \Carbon\Carbon::parse($grade->date_recorded)->format('d/m') }}</span>
                                                    @endif
                                                </div>
                                                <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0">
                                                    @if($grade->weight && $grade->weight != 1)
                                                        <span style="font-size:0.5625rem;color:var(--ms-text-muted);background:var(--ms-cell-bg);padding:1px 5px;border-radius:999px">×{{ $grade->weight }}</span>
                                                    @endif
                                                    <span style="font-size:0.8125rem;font-weight:700;color:{{ $avgColor($grade->score) }}">
                                                        {{ $grade->score !== null ? number_format($grade->score, 1, ',', '') : '—' }}
                                                    </span>
                                                    <span style="font-size:0.6875rem;color:var(--ms-text-muted)">/{{ number_format($grade->max_score ?? 10, 0, ',', '') }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if($td['recovery'] !== null)
                                            <div class="ms-assess-row" style="border-top:1px dashed rgba(6,182,212,0.3);margin-top:0.25rem;padding-top:0.375rem">
                                                <span style="font-size:0.6875rem;color:#06b6d4;font-weight:500">Recuperação</span>
                                                <div style="display:flex;align-items:center;gap:0.5rem">
                                                    <span style="font-size:0.8125rem;font-weight:700;color:#06b6d4">
                                                        {{ number_format($td['recovery']->score, 1, ',', '') }}
                                                    </span>
                                                    @if($td['final_average'] !== $td['average'])
                                                        <span style="font-size:0.6875rem;color:#06b6d4;background:rgba(6,182,212,0.1);padding:1px 6px;border-radius:999px">substituída</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                        @if($td['average'] !== null && $showAllTerms)
                                            <div style="display:flex;justify-content:flex-end;padding-top:0.25rem">
                                                <span style="font-size:0.6875rem;color:var(--ms-text-muted)">
                                                    Média: <strong style="color:{{ $avgColor($td['final_average']) }}">{{ number_format($td['final_average'], 1, ',', '') }}</strong>
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                                {{-- Points needed --}}
                                @if($item['points_needed'] !== null)
                                    <div style="margin-top:0.25rem;padding:0.5rem 0.75rem;background:rgba(234,179,8,0.08);border:1px solid rgba(234,179,8,0.2);border-radius:0.5rem;display:flex;align-items:center;gap:0.5rem">
                                        @svg('heroicon-o-light-bulb', '', ['style' => 'width:0.875rem;height:0.875rem;color:#eab308;flex-shrink:0'])
                                        <span style="font-size:0.75rem;color:var(--ms-text-secondary)">
                                            Você precisa de pelo menos
                                            <strong style="color:#eab308">{{ number_format($item['points_needed'], 1, ',', '') }}</strong>
                                            pontos na próxima avaliação para atingir a média.
                                        </span>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="ms-card" style="padding:3rem;text-align:center">
                    <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(100,116,139,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                        @svg('heroicon-o-chart-bar', '', ['style' => 'width:2rem;height:2rem;color:#94a3b8'])
                    </div>
                    <h3 style="font-size:1.125rem;font-weight:600;color:var(--ms-text-primary);margin:0 0 0.5rem">
                        Nenhuma nota lançada
                    </h3>
                    <p style="font-size:0.875rem;color:var(--ms-text-secondary);margin:0">
                        Não há notas registradas para o período selecionado.
                    </p>
                </div>
            @endif

        </div>
    @endif

</x-filament-panels::page>
