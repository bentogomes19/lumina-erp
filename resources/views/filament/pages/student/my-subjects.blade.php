<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $student = $data['student'];
        $currentClass = $data['currentClass'];
        $subjects = $data['subjects'];
        $stats = $data['stats'];

        $categoryStyles = [
            'linguagens'          => ['accent' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)', 'text' => '#7c3aed'],
            'matematica'          => ['accent' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)',  'text' => '#2563eb'],
            'ciencias_da_natureza'=> ['accent' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)', 'text' => '#059669'],
            'ciencias_humanas'    => ['accent' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)', 'text' => '#d97706'],
            'ciencias_exatas'     => ['accent' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.12)',  'text' => '#0891b2'],
        ];
        $defaultStyle = ['accent' => '#6b7280', 'bg' => 'rgba(107,114,128,0.12)', 'text' => '#6b7280'];
    @endphp

    <div style="display:flex;flex-direction:column;gap:1.5rem">

        {{-- ▸ Class info header --}}
        @if($currentClass)
            <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:1.25rem 1.5rem">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
                    <div style="display:flex;align-items:center;gap:1rem">
                        <div style="width:2.75rem;height:2.75rem;border-radius:0.5rem;background:rgba(37,99,235,0.15);display:flex;align-items:center;justify-content:center">
                            @svg('heroicon-o-academic-cap', '', ['style' => 'width:1.4rem;height:1.4rem;color:#60a5fa'])
                        </div>
                        <div>
                            <h2 style="font-size:1.125rem;font-weight:700;color:#f1f5f9;margin:0">
                                {{ $currentClass->name }}
                            </h2>
                            <p style="font-size:0.8125rem;color:#94a3b8;margin:0.125rem 0 0 0">
                                {{ $currentClass->gradeLevel?->name ?? '' }}
                                @if($currentClass->shift)
                                    &middot; Turno: {{ $currentClass->shift->label() }}
                                @endif
                                @if($currentClass->schoolYear)
                                    &middot; Ano Letivo: {{ $currentClass->schoolYear->year ?? $currentClass->schoolYear->name ?? '' }}
                                @endif
                            </p>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#94a3b8">
                        @svg('heroicon-o-identification', '', ['style' => 'width:1rem;height:1rem;color:#94a3b8'])
                        <span>Matrícula: <strong style="color:#e2e8f0">{{ $student?->registration_number }}</strong></span>
                    </div>
                </div>
            </div>
        @endif

        {{-- ▸ Stats overview cards --}}
        @if($currentClass && $subjects->isNotEmpty())
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                @php
                    $avgColor = ($stats['overall_average'] !== null && $stats['overall_average'] >= 6) ? '#22c55e' : '#ef4444';
                    $freqColor = ($stats['attendance_percent'] !== null && $stats['attendance_percent'] >= 75) ? '#22c55e' : '#f97316';
                    $statCards = [
                        ['icon'=>'heroicon-o-book-open','value'=>$stats['total_subjects'],'label'=>'Disciplinas','color'=>'#3b82f6'],
                        ['icon'=>'heroicon-o-chart-bar','value'=>$stats['overall_average'] !== null ? number_format($stats['overall_average'],1,',','.') : '—','label'=>'Média Geral','color'=>$avgColor],
                        ['icon'=>'heroicon-o-check-circle','value'=>$stats['attendance_percent'] !== null ? number_format($stats['attendance_percent'],1,',','.') . '%' : '—','label'=>'Frequência','color'=>$freqColor],
                        ['icon'=>'heroicon-o-clock','value'=>$stats['total_hours_weekly'] ?: '—','label'=>'Horas/Semana','color'=>'#a855f7'],
                    ];
                @endphp
                @foreach($statCards as $card)
                    <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:1rem">
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:{{ $card['color'] }}1a;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                @svg($card['icon'], '', ['style' => 'width:1.25rem;height:1.25rem;color:'.$card['color']])
                            </div>
                            <div>
                                <p style="font-size:1.5rem;font-weight:700;color:#f1f5f9;margin:0;line-height:1.2">{{ $card['value'] }}</p>
                                <p style="font-size:0.6875rem;color:#94a3b8;margin:0">{{ $card['label'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ▸ Subjects grouped by category --}}
        @if($subjects->isNotEmpty())
            @php
                $grouped = $subjects->groupBy(fn ($s) => $s->category?->value ?? 'sem_categoria');
            @endphp

            @foreach($grouped as $categoryKey => $categorySubjects)
                @php
                    $cs = $categoryStyles[$categoryKey] ?? $defaultStyle;
                    $categoryLabel = $categorySubjects->first()->category?->label() ?? 'Sem Categoria';
                @endphp

                <div style="display:flex;flex-direction:column;gap:0.75rem">
                    {{-- Category header --}}
                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0 0.25rem">
                        <div style="width:0.625rem;height:0.625rem;border-radius:50%;background:{{ $cs['accent'] }};flex-shrink:0"></div>
                        <h3 style="font-size:0.75rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;color:{{ $cs['text'] }};margin:0">
                            {{ $categoryLabel }}
                        </h3>
                        <div style="flex:1;border-bottom:1px solid {{ $cs['accent'] }}33"></div>
                        <span style="font-size:0.6875rem;color:{{ $cs['text'] }}">{{ $categorySubjects->count() }} {{ $categorySubjects->count() === 1 ? 'disciplina' : 'disciplinas' }}</span>
                    </div>

                    {{-- Subject cards grid --}}
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1rem">
                        @foreach($categorySubjects as $subject)
                            @php $cs = $categoryStyles[$subject->category?->value ?? ''] ?? $defaultStyle; @endphp
                            <a href="{{ url('/lumina/subject-detail?subject=' . $subject->id) }}" 
                               style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;overflow:hidden;text-decoration:none;display:block;transition:all 0.2s"
                               onmouseover="this.style.background='rgba(30,41,59,0.9)'; this.style.borderColor='rgba(255,255,255,0.12)'; this.style.transform='translateY(-2px)'"
                               onmouseout="this.style.background='rgba(30,41,59,0.7)'; this.style.borderColor='rgba(255,255,255,0.08)'; this.style.transform='translateY(0)'">
                                {{-- Color accent bar --}}
                                <div style="height:3px;background:{{ $cs['accent'] }}"></div>

                                <div style="padding:1.25rem;display:flex;flex-direction:column;gap:0.875rem">
                                    {{-- Subject header --}}
                                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem">
                                        <div style="display:flex;align-items:center;gap:0.75rem;min-width:0">
                                            <div style="width:2.25rem;height:2.25rem;border-radius:0.5rem;background:{{ $cs['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                                @svg('heroicon-o-book-open', '', ['style' => 'width:1.125rem;height:1.125rem;color:'.$cs['accent']])
                                            </div>
                                            <div style="min-width:0">
                                                <h4 style="font-size:0.9375rem;font-weight:600;color:#f1f5f9;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                                    {{ $subject->name }}
                                                </h4>
                                                <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.6875rem;color:#94a3b8;margin-top:0.125rem">
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
                                                $badgeBg = $subject->overall_average >= 7 ? 'rgba(34,197,94,0.15)' : ($subject->overall_average >= 5 ? 'rgba(234,179,8,0.15)' : 'rgba(239,68,68,0.15)');
                                                $badgeColor = $subject->overall_average >= 7 ? '#22c55e' : ($subject->overall_average >= 5 ? '#eab308' : '#ef4444');
                                            @endphp
                                            <div style="flex-shrink:0;display:flex;flex-direction:column;align-items:center">
                                                <span style="width:2.5rem;height:2.5rem;border-radius:50%;background:{{ $badgeBg }};color:{{ $badgeColor }};font-size:0.8125rem;font-weight:700;display:flex;align-items:center;justify-content:center">
                                                    {{ number_format($subject->overall_average, 1, ',', '') }}
                                                </span>
                                                <span style="font-size:0.5625rem;color:#64748b;margin-top:2px">Média</span>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Teacher --}}
                                    @if($subject->teacher_name)
                                        <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.8125rem;color:#94a3b8">
                                            @svg('heroicon-o-user', '', ['style' => 'width:0.875rem;height:0.875rem;color:#64748b;flex-shrink:0'])
                                            <span>Prof. {{ $subject->teacher_name }}</span>
                                        </div>
                                    @endif

                                    {{-- Term grades --}}
                                    @if(collect($subject->term_averages)->filter()->isNotEmpty())
                                        <div style="display:flex;flex-direction:column;gap:0.375rem">
                                            <p style="font-size:0.6875rem;font-weight:500;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0">Notas por Bimestre</p>
                                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.375rem">
                                                @foreach(['b1' => '1º Bim', 'b2' => '2º Bim', 'b3' => '3º Bim', 'b4' => '4º Bim'] as $termKey => $termLabel)
                                                    @php
                                                        $termVal = $subject->term_averages[$termKey];
                                                        $termColor = $termVal !== null ? ($termVal >= 7 ? '#22c55e' : ($termVal >= 5 ? '#eab308' : '#ef4444')) : '#475569';
                                                    @endphp
                                                    <div style="border-radius:0.375rem;padding:0.375rem;text-align:center;background:rgba(255,255,255,0.03)">
                                                        <p style="font-size:0.5625rem;color:#64748b;margin:0">{{ $termLabel }}</p>
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
                                            <div style="display:flex;align-items:center;justify-content:space-between;font-size:0.6875rem">
                                                <span style="color:#94a3b8;font-weight:500">Frequência</span>
                                                <div style="display:flex;align-items:center;gap:0.5rem">
                                                    <span style="color:#64748b">{{ $subject->presences }}P / {{ $subject->absences }}F</span>
                                                    <span style="font-weight:600;color:{{ $barColor }}">
                                                        {{ number_format($subject->attendance_percent, 1, ',', '') }}%
                                                    </span>
                                                </div>
                                            </div>
                                            <div style="width:100%;height:5px;border-radius:999px;background:rgba(255,255,255,0.06);overflow:hidden">
                                                <div style="height:100%;border-radius:999px;background:{{ $barColor }};width:{{ min($subject->attendance_percent, 100) }}%;transition:width 0.3s"></div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Description --}}
                                    @if($subject->description)
                                        <p style="font-size:0.6875rem;color:#64748b;margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
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
            <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:3rem;text-align:center">
                <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                    @svg('heroicon-o-book-open', '', ['style' => 'width:2rem;height:2rem;color:#3b82f6'])
                </div>
                <h3 style="font-size:1.125rem;font-weight:600;color:#f1f5f9;margin:0 0 0.5rem">
                    Nenhuma Disciplina Encontrada
                </h3>
                <p style="font-size:0.875rem;color:#94a3b8;margin:0;max-width:28rem;margin-left:auto;margin-right:auto">
                    @if(!$currentClass)
                        Você não está matriculado em nenhuma turma no ano letivo vigente.
                    @else
                        Nenhuma disciplina foi atribuída à sua turma ({{ $currentClass->name }}) até o momento.
                    @endif
                </p>
            </div>
        @endif

    </div>

    {{-- Responsive: stack on small screens --}}
    <style>
        @media (max-width: 768px) {
            [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2, 1fr) !important; }
            [style*="grid-template-columns:repeat(2,1fr)"] { grid-template-columns: 1fr !important; }
        }
    </style>
</x-filament-panels::page>
