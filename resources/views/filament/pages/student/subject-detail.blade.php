<x-filament-panels::page>
    @php
        $data = $this->getSubjectData();
        $subject = $data['subject'];
        $teacher = $data['teacher'];
        $currentClass = $data['currentClass'];
        $hoursWeekly = $data['hours_weekly'];
        $overallAverage = $data['overall_average'];
        $attendancePercent = $data['attendance_percent'];
        $totalClasses = $data['total_classes'];
        $presences = $data['presences'];
        $absences = $data['absences'];
        $lates = $data['lates'];
        $lessons = $data['lessons'];
        $termAverages = $data['term_averages'];
        $syllabus = $data['syllabus'];
        $objectives = $data['objectives'];

        $categoryStyles = [
            'linguagens'          => ['accent' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.12)', 'text' => '#7c3aed'],
            'matematica'          => ['accent' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)',  'text' => '#2563eb'],
            'ciencias_da_natureza'=> ['accent' => '#10b981', 'bg' => 'rgba(16,185,129,0.12)', 'text' => '#059669'],
            'ciencias_humanas'    => ['accent' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.12)', 'text' => '#d97706'],
            'ciencias_exatas'     => ['accent' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.12)',  'text' => '#0891b2'],
        ];
        $defaultStyle = ['accent' => '#6b7280', 'bg' => 'rgba(107,114,128,0.12)', 'text' => '#6b7280'];
        $cs = $categoryStyles[$subject?->category?->value ?? ''] ?? $defaultStyle;
    @endphp

    @if(!$subject)
        {{-- Error state --}}
        <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:3rem;text-align:center">
            <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(239,68,68,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                @svg('heroicon-o-exclamation-triangle', '', ['style' => 'width:2rem;height:2rem;color:#ef4444'])
            </div>
            <h3 style="font-size:1.125rem;font-weight:600;color:#f1f5f9;margin:0 0 0.5rem">
                Disciplina não encontrada
            </h3>
            <p style="font-size:0.875rem;color:#94a3b8;margin:0">
                Não foi possível carregar os dados desta disciplina.
            </p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:1.5rem">

            {{-- ▸ Back button --}}
            <div>
                <a href="{{ url('/lumina/my-subjects') }}" 
                   style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.5rem;color:#94a3b8;text-decoration:none;font-size:0.875rem;transition:all 0.2s"
                   onmouseover="this.style.background='rgba(30,41,59,0.9)'; this.style.borderColor='rgba(255,255,255,0.12)'"
                   onmouseout="this.style.background='rgba(30,41,59,0.7)'; this.style.borderColor='rgba(255,255,255,0.08)'">
                    @svg('heroicon-o-arrow-left', '', ['style' => 'width:1rem;height:1rem'])
                    <span>Voltar para Disciplinas</span>
                </a>
            </div>

            {{-- ▸ Subject header --}}
            <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;overflow:hidden">
                <div style="height:4px;background:{{ $cs['accent'] }}"></div>
                <div style="padding:1.5rem;display:flex;align-items:flex-start;justify-content:space-between;gap:1.5rem;flex-wrap:wrap">
                    <div style="display:flex;align-items:start;gap:1rem;min-width:0;flex:1">
                        <div style="width:3.5rem;height:3.5rem;border-radius:0.75rem;background:{{ $cs['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                            @svg('heroicon-o-book-open', '', ['style' => 'width:1.75rem;height:1.75rem;color:'.$cs['accent']])
                        </div>
                        <div style="min-width:0">
                            <h2 style="font-size:1.5rem;font-weight:700;color:#f1f5f9;margin:0">
                                {{ $subject->name }}
                            </h2>
                            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-top:0.375rem">
                                @if($subject->code)
                                    <span style="display:inline-flex;align-items:center;gap:0.375rem;font-size:0.8125rem;color:#94a3b8">
                                        @svg('heroicon-o-hashtag', '', ['style' => 'width:0.875rem;height:0.875rem;color:#64748b'])
                                        <span style="font-family:monospace">{{ $subject->code }}</span>
                                    </span>
                                @endif
                                <span style="display:inline-flex;align-items:center;gap:0.375rem;font-size:0.8125rem;color:#94a3b8">
                                    @svg('heroicon-o-tag', '', ['style' => 'width:0.875rem;height:0.875rem;color:#64748b'])
                                    {{ $subject->category?->label() ?? 'Sem Categoria' }}
                                </span>
                                @if($currentClass)
                                    <span style="display:inline-flex;align-items:center;gap:0.375rem;font-size:0.8125rem;color:#94a3b8">
                                        @svg('heroicon-o-academic-cap', '', ['style' => 'width:0.875rem;height:0.875rem;color:#64748b'])
                                        {{ $currentClass->name }}
                                    </span>
                                @endif
                            </div>
                            @if($teacher)
                                <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.75rem;font-size:0.9375rem;color:#e2e8f0">
                                    @svg('heroicon-o-user', '', ['style' => 'width:1rem;height:1rem;color:#94a3b8'])
                                    <span>Professor(a): <strong>{{ $teacher->name }}</strong></span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Quick stats badges --}}
                    <div style="display:flex;gap:0.75rem;flex-shrink:0">
                        @if($overallAverage !== null)
                            @php
                                $avgBg = $overallAverage >= 7 ? 'rgba(34,197,94,0.15)' : ($overallAverage >= 5 ? 'rgba(234,179,8,0.15)' : 'rgba(239,68,68,0.15)');
                                $avgColor = $overallAverage >= 7 ? '#22c55e' : ($overallAverage >= 5 ? '#eab308' : '#ef4444');
                            @endphp
                            <div style="text-align:center">
                                <div style="width:4rem;height:4rem;border-radius:50%;background:{{ $avgBg }};color:{{ $avgColor }};font-size:1.25rem;font-weight:700;display:flex;align-items:center;justify-content:center">
                                    {{ number_format($overallAverage, 1, ',', '') }}
                                </div>
                                <p style="font-size:0.6875rem;color:#94a3b8;margin:0.25rem 0 0 0">Média Geral</p>
                            </div>
                        @endif
                        @if($attendancePercent !== null)
                            @php
                                $freqBg = $attendancePercent >= 75 ? 'rgba(34,197,94,0.15)' : ($attendancePercent >= 50 ? 'rgba(234,179,8,0.15)' : 'rgba(239,68,68,0.15)');
                                $freqColor = $attendancePercent >= 75 ? '#22c55e' : ($attendancePercent >= 50 ? '#eab308' : '#ef4444');
                            @endphp
                            <div style="text-align:center">
                                <div style="width:4rem;height:4rem;border-radius:50%;background:{{ $freqBg }};color:{{ $freqColor }};font-size:1.125rem;font-weight:700;display:flex;align-items:center;justify-content:center">
                                    {{ number_format($attendancePercent, 0) }}%
                                </div>
                                <p style="font-size:0.6875rem;color:#94a3b8;margin:0.25rem 0 0 0">Frequência</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ▸ Stats overview cards --}}
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                @php
                    $statCards = [
                        ['icon'=>'heroicon-o-calendar-days','value'=>$totalClasses,'label'=>'Aulas Dadas','color'=>'#3b82f6'],
                        ['icon'=>'heroicon-o-check-circle','value'=>$presences,'label'=>'Presenças','color'=>'#22c55e'],
                        ['icon'=>'heroicon-o-clock','value'=>$lates,'label'=>'Atrasos','color'=>'#eab308'],
                        ['icon'=>'heroicon-o-x-circle','value'=>$absences,'label'=>'Faltas','color'=>'#ef4444'],
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

            {{-- ▸ Term grades --}}
            @if(collect($termAverages)->filter(fn($t) => $t['average'] !== null)->isNotEmpty())
                <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:1.25rem">
                    <h3 style="font-size:1rem;font-weight:600;color:#f1f5f9;margin:0 0 1rem 0;display:flex;align-items:center;gap:0.5rem">
                        @svg('heroicon-o-chart-bar', '', ['style' => 'width:1.125rem;height:1.125rem;color:#3b82f6'])
                        Notas por Bimestre
                    </h3>
                    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem">
                        @foreach(['b1' => '1º Bimestre', 'b2' => '2º Bimestre', 'b3' => '3º Bimestre', 'b4' => '4º Bimestre'] as $termKey => $termLabel)
                            @php
                                $termData = $termAverages[$termKey];
                                $termVal = $termData['average'];
                                $termColor = $termVal !== null ? ($termVal >= 7 ? '#22c55e' : ($termVal >= 5 ? '#eab308' : '#ef4444')) : '#475569';
                                $termBg = $termVal !== null ? ($termVal >= 7 ? 'rgba(34,197,94,0.12)' : ($termVal >= 5 ? 'rgba(234,179,8,0.12)' : 'rgba(239,68,68,0.12)')) : 'rgba(71,85,105,0.12)';
                            @endphp
                            <div style="border-radius:0.75rem;padding:1rem;background:{{ $termBg }};border:1px solid {{ $termColor }}33">
                                <p style="font-size:0.6875rem;font-weight:500;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0">
                                    {{ $termLabel }}
                                </p>
                                <p style="font-size:2rem;font-weight:700;color:{{ $termColor }};margin:0.5rem 0">
                                    {{ $termVal !== null ? number_format($termVal, 1, ',', '') : '—' }}
                                </p>
                                @if($termData['grades']->isNotEmpty())
                                    <div style="display:flex;flex-direction:column;gap:0.25rem;margin-top:0.75rem">
                                        @foreach($termData['grades'] as $grade)
                                            <div style="display:flex;justify-content:space-between;font-size:0.75rem;color:#cbd5e1">
                                                <span>{{ $grade->assessment_type?->label() ?? 'Avaliação' }}</span>
                                                <span style="font-weight:600;color:{{ $termColor }}">{{ number_format($grade->score, 1, ',', '') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ▸ Syllabus & Description --}}
            @if($subject->description || $syllabus || $objectives)
                <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:1.25rem">
                    <h3 style="font-size:1rem;font-weight:600;color:#f1f5f9;margin:0 0 1rem 0;display:flex;align-items:center;gap:0.5rem">
                        @svg('heroicon-o-document-text', '', ['style' => 'width:1.125rem;height:1.125rem;color:#8b5cf6'])
                        Ementa e Descrição
                    </h3>

                    @if($subject->description)
                        <div style="margin-bottom:1.25rem">
                            <h4 style="font-size:0.8125rem;font-weight:600;color:#cbd5e1;margin:0 0 0.5rem 0;text-transform:uppercase;letter-spacing:0.05em">
                                Descrição
                            </h4>
                            <p style="font-size:0.9375rem;color:#e2e8f0;margin:0;line-height:1.6">
                                {{ $subject->description }}
                            </p>
                        </div>
                    @endif

                    @if($syllabus)
                        <div style="margin-bottom:1.25rem">
                            <h4 style="font-size:0.8125rem;font-weight:600;color:#cbd5e1;margin:0 0 0.5rem 0;text-transform:uppercase;letter-spacing:0.05em">
                                Ementa
                            </h4>
                            <div style="font-size:0.9375rem;color:#e2e8f0;line-height:1.6">
                                {!! nl2br(e($syllabus)) !!}
                            </div>
                        </div>
                    @endif

                    @if($objectives)
                        <div>
                            <h4 style="font-size:0.8125rem;font-weight:600;color:#cbd5e1;margin:0 0 0.5rem 0;text-transform:uppercase;letter-spacing:0.05em">
                                Objetivos de Aprendizagem
                            </h4>
                            <div style="font-size:0.9375rem;color:#e2e8f0;line-height:1.6">
                                {!! nl2br(e($objectives)) !!}
                            </div>
                        </div>
                    @endif

                    @if($hoursWeekly)
                        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.08)">
                            <div style="display:inline-flex;align-items:center;gap:0.5rem;padding:0.5rem 0.875rem;background:rgba(139,92,246,0.12);border-radius:0.5rem">
                                @svg('heroicon-o-clock', '', ['style' => 'width:1rem;height:1rem;color:#a78bfa'])
                                <span style="font-size:0.875rem;color:#e9d5ff">
                                    <strong>{{ $hoursWeekly }}</strong> horas semanais
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ▸ Lessons list --}}
            <div style="background:rgba(30,41,59,0.7);border:1px solid rgba(255,255,255,0.08);border-radius:0.75rem;padding:1.25rem">
                <h3 style="font-size:1rem;font-weight:600;color:#f1f5f9;margin:0 0 1rem 0;display:flex;align-items:center;gap:0.5rem">
                    @svg('heroicon-o-calendar-days', '', ['style' => 'width:1.125rem;height:1.125rem;color:#22c55e'])
                    Histórico de Aulas
                    <span style="font-size:0.75rem;color:#94a3b8;font-weight:400;margin-left:auto">
                        {{ $lessons->count() }} {{ $lessons->count() === 1 ? 'aula' : 'aulas' }}
                    </span>
                </h3>

                @if($lessons->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:0.5rem">
                        @foreach($lessons as $lesson)
                            @php
                                $statusInfo = [
                                    'present' => ['label' => 'Presente', 'color' => '#22c55e', 'bg' => 'rgba(34,197,94,0.12)', 'icon' => 'heroicon-o-check-circle'],
                                    'absent' => ['label' => 'Falta', 'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.12)', 'icon' => 'heroicon-o-x-circle'],
                                    'late' => ['label' => 'Atraso', 'color' => '#eab308', 'bg' => 'rgba(234,179,8,0.12)', 'icon' => 'heroicon-o-clock'],
                                    'justified' => ['label' => 'Justificado', 'color' => '#06b6d4', 'bg' => 'rgba(6,182,212,0.12)', 'icon' => 'heroicon-o-document-text'],
                                ];
                                $attendance = $lesson->student_attendance;
                                $status = $attendance?->status;
                                $statusValue = $status ? (is_object($status) ? $status->value : $status) : null;
                                $info = $statusValue ? ($statusInfo[$statusValue] ?? null) : null;
                            @endphp
                            <div style="background:rgba(15,23,42,0.5);border:1px solid rgba(255,255,255,0.06);border-radius:0.5rem;padding:1rem;display:flex;align-items:center;gap:1rem">
                                {{-- Date --}}
                                <div style="text-align:center;flex-shrink:0">
                                    <p style="font-size:1.5rem;font-weight:700;color:#f1f5f9;margin:0;line-height:1">
                                        {{ \Carbon\Carbon::parse($lesson->date)->format('d') }}
                                    </p>
                                    <p style="font-size:0.6875rem;color:#94a3b8;margin:0">
                                        {{ ucfirst(\Carbon\Carbon::parse($lesson->date)->locale('pt_BR')->translatedFormat('M')) }}
                                    </p>
                                </div>

                                <div style="width:1px;height:2.5rem;background:rgba(255,255,255,0.08)"></div>

                                {{-- Time --}}
                                <div style="flex-shrink:0">
                                    <p style="font-size:0.6875rem;color:#94a3b8;margin:0">Horário</p>
                                    <p style="font-size:0.875rem;font-weight:600;color:#e2e8f0;margin:0">
                                        {{ \Carbon\Carbon::parse($lesson->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($lesson->end_time)->format('H:i') }}
                                    </p>
                                </div>

                                {{-- Topic --}}
                                <div style="flex:1;min-width:0">
                                    @if($lesson->topic)
                                        <p style="font-size:0.6875rem;color:#94a3b8;margin:0">Conteúdo</p>
                                        <p style="font-size:0.875rem;color:#f1f5f9;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="{{ $lesson->topic }}">
                                            {{ $lesson->topic }}
                                        </p>
                                    @else
                                        <p style="font-size:0.875rem;color:#64748b;margin:0;font-style:italic">
                                            Conteúdo não informado
                                        </p>
                                    @endif
                                </div>

                                {{-- Status badge --}}
                                @if($info)
                                    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem 0.875rem;background:{{ $info['bg'] }};border-radius:0.5rem;flex-shrink:0">
                                        @svg($info['icon'], '', ['style' => 'width:1rem;height:1rem;color:'.$info['color']])
                                        <span style="font-size:0.8125rem;font-weight:600;color:{{ $info['color'] }}">
                                            {{ $info['label'] }}
                                        </span>
                                    </div>
                                @else
                                    <div style="padding:0.5rem 0.875rem;background:rgba(71,85,105,0.12);border-radius:0.5rem;flex-shrink:0">
                                        <span style="font-size:0.8125rem;color:#94a3b8">Sem registro</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding:2rem;text-align:center">
                        <div style="width:3rem;height:3rem;border-radius:50%;background:rgba(71,85,105,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem">
                            @svg('heroicon-o-calendar-days', '', ['style' => 'width:1.5rem;height:1.5rem;color:#94a3b8'])
                        </div>
                        <p style="font-size:0.9375rem;color:#94a3b8;margin:0">
                            Nenhuma aula registrada até o momento.
                        </p>
                    </div>
                @endif
            </div>

        </div>
    @endif

    {{-- Responsive adjustments --}}
    <style>
        @media (max-width: 768px) {
            [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2, 1fr) !important; }
        }
        @media (max-width: 480px) {
            [style*="grid-template-columns:repeat(2"] { grid-template-columns: 1fr !important; }
        }
    </style>
</x-filament-panels::page>
