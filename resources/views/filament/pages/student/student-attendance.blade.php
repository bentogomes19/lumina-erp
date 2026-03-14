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

        .ms-card {
            background: var(--ms-card-bg);
            border: 1px solid var(--ms-card-border);
            border-radius: 0.75rem;
        }

        /* ── Period filter pill ── */
        .ms-period-btn {
            padding: 0.375rem 0.875rem;
            border-radius: 999px;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid var(--ms-card-border);
            background: var(--ms-card-bg);
            color: var(--ms-text-secondary);
            transition: all 0.15s;
        }
        .ms-period-btn:hover   { background: var(--ms-hover-bg); }
        .ms-period-btn.active  {
            background: #f59e0b;
            border-color: #f59e0b;
            color: #fff;
        }

        /* ── Calendar grid ── */
        .ms-cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 3px;
        }
        .ms-cal-day {
            aspect-ratio: 1;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.6875rem;
            font-weight: 500;
            color: var(--ms-text-secondary);
            background: var(--ms-cell-bg);
        }
        .ms-cal-day.today      { outline: 2px solid #f59e0b; outline-offset: 1px; }
        .ms-cal-day.present    { background: rgba(34,197,94,0.20);  color: #16a34a; font-weight: 700; }
        .ms-cal-day.absent     { background: rgba(239,68,68,0.20);  color: #dc2626; font-weight: 700; }
        .ms-cal-day.late       { background: rgba(234,179,8,0.20);  color: #ca8a04; font-weight: 700; }
        .ms-cal-day.excused    { background: rgba(6,182,212,0.20);  color: #0891b2; font-weight: 700; }
        .ms-cal-day.weekend    { opacity: 0.35; }
        .ms-cal-day.empty      { background: transparent; }

        /* ── Subject frequency bar ── */
        .ms-freq-bar-track {
            width: 100%;
            height: 6px;
            border-radius: 999px;
            background: var(--ms-bar-bg);
            overflow: hidden;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .ms-stats-grid    { grid-template-columns: repeat(2, 1fr) !important; }
            .ms-subject-grid  { grid-template-columns: 1fr !important; }
            .ms-calendar-grid { grid-template-columns: 1fr !important; }
        }
    </style>

    @php
        $data         = $this->getPageData();
        $stats        = $data['stats'];
        $student      = $data['student'];
        $currentClass = $data['currentClass'];
        $subjectStats = $data['subject_stats'];
        $calendar     = $data['calendar'];
        $minRate      = $data['min_rate'];
        $period       = $data['selected_period'];
        $periodLabel  = $data['period_label'];
        $today        = now()->format('Y-m-d');

        $periods = [
            'all' => 'Ano Letivo',
            'b1'  => '1º Bimestre',
            'b2'  => '2º Bimestre',
            'b3'  => '3º Bimestre',
            'b4'  => '4º Bimestre',
        ];

        $rateColor = fn($r) => $r >= 75 ? '#22c55e' : ($r >= 50 ? '#eab308' : '#ef4444');
    @endphp

    @if(!$student || !$currentClass)
        {{-- Empty state --}}
        <div class="ms-card" style="padding:3rem;text-align:center">
            <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                @svg('heroicon-o-calendar-days', '', ['style' => 'width:2rem;height:2rem;color:#f59e0b'])
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
                            <h2 style="font-size:1.125rem;font-weight:700;color:var(--ms-text-primary);margin:0">
                                {{ $currentClass->name }}
                            </h2>
                            <p style="font-size:0.8125rem;color:var(--ms-text-secondary);margin:0.125rem 0 0 0">
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
                    <button
                        wire:click="setPeriod('{{ $key }}')"
                        class="ms-period-btn {{ $period === $key ? 'active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
                <span style="font-size:0.8125rem;color:var(--ms-text-muted);margin-left:0.5rem">
                    {{ $periodLabel }}
                </span>
            </div>

            {{-- ▸ Alert: below minimum --}}
            @if($stats['alert'])
                @php
                    $overLimit = abs($stats['remaining_absences']);
                    $alreadyFailed = $stats['remaining_absences'] < 0;
                @endphp
                <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);border-left:4px solid #ef4444;border-radius:0.75rem;padding:1rem 1.25rem;display:flex;align-items:flex-start;gap:0.75rem">
                    @svg('heroicon-s-exclamation-triangle', '', ['style' => 'width:1.25rem;height:1.25rem;color:#ef4444;flex-shrink:0;margin-top:1px'])
                    <div>
                        <p style="font-size:0.9375rem;font-weight:600;color:#ef4444;margin:0 0 0.25rem">
                            @if($alreadyFailed)
                                Reprovação por frequência — limite ultrapassado
                            @else
                                Frequência abaixo do mínimo exigido ({{ $minRate }}%)
                            @endif
                        </p>
                        <p style="font-size:0.8125rem;color:var(--ms-text-secondary);margin:0">
                            @if($alreadyFailed)
                                Você excedeu o limite de faltas em <strong style="color:#ef4444">{{ $overLimit }} {{ $overLimit === 1 ? 'falta' : 'faltas' }}</strong>.
                                Procure a coordenação para regularizar sua situação.
                            @else
                                Você pode faltar apenas mais <strong style="color:#f97316">{{ $stats['remaining_absences'] }} {{ $stats['remaining_absences'] === 1 ? 'vez' : 'vezes' }}</strong> sem ser reprovado(a) por falta.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            {{-- ▸ Overall stats --}}
            <div class="ms-stats-grid" style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem">
                @php
                    $overallCards = [
                        ['icon'=>'heroicon-o-calendar-days', 'value'=>$stats['total'],   'label'=>'Total de Aulas',  'color'=>'#f59e0b'],
                        ['icon'=>'heroicon-o-check-circle',  'value'=>$stats['present'], 'label'=>'Presenças',       'color'=>'#22c55e'],
                        ['icon'=>'heroicon-o-x-circle',      'value'=>$stats['absent'],  'label'=>'Faltas',          'color'=>'#ef4444'],
                        ['icon'=>'heroicon-o-clock',         'value'=>$stats['late'],    'label'=>'Atrasos',         'color'=>'#eab308'],
                        ['icon'=>'heroicon-o-document-check','value'=>$stats['excused'], 'label'=>'Justificadas',    'color'=>'#06b6d4'],
                    ];
                @endphp
                @foreach($overallCards as $card)
                    <div class="ms-card" style="padding:1rem">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:{{ $card['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                @svg($card['icon'], '', ['style' => 'width:1.25rem;height:1.25rem;color:'.$card['color']])
                            </div>
                            <div>
                                <p style="font-size:1.375rem;font-weight:700;color:var(--ms-text-primary);margin:0;line-height:1.2">{{ $card['value'] }}</p>
                                <p style="font-size:0.625rem;color:var(--ms-text-secondary);margin:0">{{ $card['label'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ▸ Overall frequency meter --}}
            @if($stats['total'] > 0)
                @php $rc = $rateColor($stats['rate']); @endphp
                <div class="ms-card" style="padding:1.25rem">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.5rem">
                        <div style="display:flex;align-items:center;gap:0.5rem">
                            @svg('heroicon-o-chart-bar', '', ['style' => 'width:1.125rem;height:1.125rem;color:'.$rc])
                            <span style="font-size:1rem;font-weight:600;color:var(--ms-text-primary)">Frequência Geral — {{ $periodLabel }}</span>
                        </div>
                        <div style="display:flex;align-items:baseline;gap:0.25rem">
                            <span style="font-size:2rem;font-weight:800;color:{{ $rc }}">{{ $stats['rate'] }}%</span>
                            <span style="font-size:0.8125rem;color:var(--ms-text-muted)">mín. {{ $minRate }}%</span>
                        </div>
                    </div>
                    <div class="ms-freq-bar-track">
                        <div style="height:100%;border-radius:999px;background:{{ $rc }};width:{{ min($stats['rate'], 100) }}%;transition:width 0.4s"></div>
                    </div>
                    {{-- Min threshold marker --}}
                    <div style="position:relative;height:0">
                        <div style="position:absolute;left:{{ $minRate }}%;top:-10px;width:1px;height:12px;background:#94a3b8;transform:translateX(-50%)"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:0.625rem;font-size:0.75rem;color:var(--ms-text-muted)">
                        <span>0%</span>
                        <span style="color:#94a3b8">▲ {{ $minRate }}% mínimo</span>
                        <span>100%</span>
                    </div>

                    {{-- Remaining absences indicator --}}
                    @if(!$stats['alert'] && $stats['total'] > 0)
                        <div style="margin-top:0.875rem;padding:0.625rem 0.875rem;background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:0.5rem;display:inline-flex;align-items:center;gap:0.5rem">
                            @svg('heroicon-o-shield-check', '', ['style' => 'width:1rem;height:1rem;color:#22c55e'])
                            <span style="font-size:0.8125rem;color:var(--ms-text-secondary)">
                                Você ainda pode faltar
                                <strong style="color:#22c55e">{{ $stats['remaining_absences'] }} {{ $stats['remaining_absences'] === 1 ? 'vez' : 'vezes' }}</strong>
                                sem reprovar por falta.
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ▸ Per-subject frequency --}}
            @if(!empty($subjectStats))
                <div>
                    <h3 style="font-size:0.875rem;font-weight:600;color:var(--ms-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem">
                        Frequência por Disciplina
                    </h3>
                    <div class="ms-subject-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.875rem">
                        @foreach($subjectStats as $ss)
                            @php $sc = $rateColor($ss['rate']); @endphp
                            <div class="ms-card" style="padding:1.125rem;border-left:3px solid {{ $sc }}">
                                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.625rem;gap:0.5rem">
                                    <div style="min-width:0">
                                        <h4 style="font-size:0.9375rem;font-weight:600;color:var(--ms-text-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                            {{ $ss['subject']?->name ?? 'Sem disciplina' }}
                                        </h4>
                                        <div style="display:flex;gap:0.875rem;font-size:0.6875rem;color:var(--ms-text-muted);margin-top:0.25rem;flex-wrap:wrap">
                                            <span>{{ $ss['total'] }} aulas</span>
                                            <span style="color:#22c55e">{{ $ss['present'] }} pres.</span>
                                            <span style="color:#ef4444">{{ $ss['absent'] }} faltas</span>
                                            @if($ss['excused'] > 0)
                                                <span style="color:#06b6d4">{{ $ss['excused'] }} justif.</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="text-align:right;flex-shrink:0">
                                        <span style="font-size:1.375rem;font-weight:700;color:{{ $sc }}">{{ $ss['rate'] }}%</span>
                                        @if($ss['alert'])
                                            <div style="display:flex;align-items:center;gap:0.25rem;justify-content:flex-end;margin-top:0.125rem">
                                                @svg('heroicon-s-exclamation-triangle', '', ['style' => 'width:0.75rem;height:0.75rem;color:#ef4444'])
                                                <span style="font-size:0.5625rem;color:#ef4444;font-weight:600">ABAIXO DO MÍNIMO</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="ms-freq-bar-track">
                                    <div style="height:100%;border-radius:999px;background:{{ $sc }};width:{{ min($ss['rate'], 100) }}%;transition:width 0.4s"></div>
                                </div>
                                @if($ss['total'] > 0)
                                    <p style="font-size:0.6875rem;color:var(--ms-text-muted);margin:0.5rem 0 0;text-align:right">
                                        @if($ss['remaining_absences'] > 0)
                                            Pode faltar mais <strong style="color:{{ $sc }}">{{ $ss['remaining_absences'] }}</strong> {{ $ss['remaining_absences'] === 1 ? 'vez' : 'vezes' }}
                                        @elseif($ss['remaining_absences'] === 0)
                                            Sem margem de faltas
                                        @else
                                            <span style="color:#ef4444">{{ abs($ss['remaining_absences']) }} falta(s) acima do limite</span>
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ▸ Calendar --}}
            @if(!empty($calendar))
                <div>
                    <h3 style="font-size:0.875rem;font-weight:600;color:var(--ms-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem">
                        Calendário de Presença
                    </h3>

                    {{-- Legend --}}
                    <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem">
                        @foreach(['present'=>['#22c55e','Presente'],'absent'=>['#ef4444','Falta'],'late'=>['#eab308','Atraso'],'excused'=>['#06b6d4','Justificado']] as $k=>$v)
                            <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:var(--ms-text-secondary)">
                                <div style="width:0.625rem;height:0.625rem;border-radius:50%;background:{{ $v[0] }}"></div>
                                {{ $v[1] }}
                            </div>
                        @endforeach
                    </div>

                    <div class="ms-calendar-grid" style="display:grid;grid-template-columns:repeat({{ count($calendar) }},1fr);gap:1rem">
                        @foreach($calendar as $month)
                            <div class="ms-card" style="padding:1rem">
                                <h4 style="font-size:0.875rem;font-weight:600;color:var(--ms-text-primary);margin:0 0 0.75rem;text-align:center;text-transform:capitalize">
                                    {{ $month['label'] }}
                                </h4>

                                {{-- Day-of-week headers (Sun=0) --}}
                                <div class="ms-cal-grid" style="margin-bottom:3px">
                                    @foreach(['D','S','T','Q','Q','S','S'] as $dow)
                                        <div style="text-align:center;font-size:0.5625rem;font-weight:700;color:var(--ms-text-muted);padding:2px 0">{{ $dow }}</div>
                                    @endforeach
                                </div>

                                <div class="ms-cal-grid">
                                    {{-- Empty cells before first day --}}
                                    @for($e = 0; $e < $month['first_dow']; $e++)
                                        <div class="ms-cal-day empty"></div>
                                    @endfor

                                    @foreach($month['days'] as $day)
                                        @php
                                            $isWeekend = in_array($day['dow'], [0, 6]);
                                            $isToday   = $day['date'] === $today;
                                            $classes   = 'ms-cal-day';
                                            if ($day['status']) $classes .= ' ' . $day['status'];
                                            elseif ($isWeekend) $classes .= ' weekend';
                                            if ($isToday) $classes .= ' today';
                                        @endphp
                                        <div class="{{ $classes }}" title="{{ $day['date'] }}{{ $day['count'] > 0 ? ' · '.$day['count'].' registro(s)' : '' }}">
                                            {{ $day['day'] }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ▸ Justified absences section --}}
            @php
                $justifiedRecords = collect();
                // We'll show this block only if there are excused records
                // The blade accesses the table data through the existing Filament table
            @endphp

            {{-- ▸ Attendance history table --}}
            <div>
                <h3 style="font-size:0.875rem;font-weight:600;color:var(--ms-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.75rem">
                    Histórico Detalhado
                </h3>
                {{ $this->table }}
            </div>

        </div>
    @endif

</x-filament-panels::page>
