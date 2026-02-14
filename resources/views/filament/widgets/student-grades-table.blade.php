<x-filament-widgets::widget>
    <x-filament::section heading="Minhas Notas">
        @php
            $data = $this->getViewData();
            $gradesByTerm = $data['gradesByTerm'];
            $assessmentColumns = $data['assessmentColumns'];
            $termLabels = $data['termLabels'];
            $termAverages = $data['termAverages'];
        @endphp

        <style>
            .grades-wrapper { display: flex; flex-direction: column; gap: 2rem; }

            /* Card do bimestre */
            .grades-card { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
            .dark .grades-card { border-color: #374151; }

            /* Header do bimestre */
            .grades-header { background: linear-gradient(135deg, #3b82f6, #6366f1); padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
            .grades-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #fff; }
            .grades-header .term-avg-label { font-size: 0.75rem; color: rgba(255,255,255,0.8); }
            .grades-header .term-avg-value { font-size: 1.5rem; font-weight: 800; color: #fff; }

            /* Tabela */
            .grades-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }

            /* Thead */
            .grades-table thead tr { background-color: #f8fafc; }
            .dark .grades-table thead tr { background-color: #1e293b; }

            .grades-table th {
                padding: 12px 16px; font-weight: 600; border-bottom: 2px solid #e5e7eb;
                white-space: nowrap; color: #374151;
            }
            .dark .grades-table th { color: #d1d5db; border-bottom-color: #374151; }

            .grades-table th.text-center { text-align: center; }
            .grades-table th.text-left { text-align: left; }

            /* Tbody */
            .grades-table tbody tr { border-bottom: 1px solid #f3f4f6; transition: background 0.15s; }
            .dark .grades-table tbody tr { border-bottom-color: #1e293b; }
            .grades-table tbody tr:hover { background-color: #f9fafb; }
            .dark .grades-table tbody tr:hover { background-color: #1e293b; }

            .grades-table td { padding: 14px 16px; white-space: nowrap; }
            .grades-table td.text-center { text-align: center; }

            /* Disciplina */
            .grades-subject { font-weight: 600; color: #1f2937; }
            .dark .grades-subject { color: #f3f4f6; }

            /* Badge turma */
            .grades-class-badge {
                display: inline-block; background: #eff6ff; color: #3b82f6;
                padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600;
            }
            .dark .grades-class-badge { background: #1e3a5f; color: #93c5fd; }

            /* Círculo de nota */
            .grade-circle {
                display: inline-flex; align-items: center; justify-content: center;
                width: 48px; height: 48px; border-radius: 50%;
                font-size: 0.85rem; font-weight: 700; border: 2px solid;
            }
            .grade-circle.good { background: #dcfce7; color: #166534; border-color: #86efac; }
            .grade-circle.warn { background: #fef9c3; color: #854d0e; border-color: #fde047; }
            .grade-circle.bad  { background: #fee2e2; color: #991b1b; border-color: #fca5a5; }

            .dark .grade-circle.good { background: #14532d; color: #86efac; border-color: #22c55e; }
            .dark .grade-circle.warn { background: #713f12; color: #fde047; border-color: #eab308; }
            .dark .grade-circle.bad  { background: #7f1d1d; color: #fca5a5; border-color: #ef4444; }

            /* Círculo de média */
            .grade-avg {
                display: inline-flex; align-items: center; justify-content: center;
                width: 52px; height: 52px; border-radius: 50%;
                font-size: 0.9rem; font-weight: 800; border: 3px solid;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .grade-avg.good { background: #dcfce7; color: #166534; border-color: #22c55e; }
            .grade-avg.warn { background: #fef9c3; color: #854d0e; border-color: #eab308; }
            .grade-avg.bad  { background: #fee2e2; color: #991b1b; border-color: #ef4444; }

            .dark .grade-avg.good { background: #14532d; color: #86efac; border-color: #22c55e; }
            .dark .grade-avg.warn { background: #713f12; color: #fde047; border-color: #eab308; }
            .dark .grade-avg.bad  { background: #7f1d1d; color: #fca5a5; border-color: #ef4444; }

            /* Data */
            .grades-date { color: #6b7280; font-size: 0.85rem; }
            .dark .grades-date { color: #9ca3af; }

            /* Placeholder */
            .grades-empty { color: #d1d5db; }
            .dark .grades-empty { color: #4b5563; }

            .grades-no-data { padding: 32px; text-align: center; color: #9ca3af; }
            .dark .grades-no-data { color: #6b7280; }

            /* Legenda */
            .grades-legend {
                display: flex; gap: 24px; justify-content: center; padding: 16px;
                background: #f8fafc; border-radius: 8px; border: 1px solid #e5e7eb; flex-wrap: wrap;
            }
            .dark .grades-legend { background: #1e293b; border-color: #374151; }

            .grades-legend-item { display: flex; align-items: center; gap: 8px; }
            .grades-legend-text { font-size: 0.8rem; color: #374151; }
            .dark .grades-legend-text { color: #d1d5db; }

            .legend-dot {
                display: inline-block; width: 16px; height: 16px;
                border-radius: 50%; border: 2px solid;
            }
            .legend-dot.good { background: #dcfce7; border-color: #86efac; }
            .legend-dot.warn { background: #fef9c3; border-color: #fde047; }
            .legend-dot.bad  { background: #fee2e2; border-color: #fca5a5; }
            .dark .legend-dot.good { background: #14532d; border-color: #22c55e; }
            .dark .legend-dot.warn { background: #713f12; border-color: #eab308; }
            .dark .legend-dot.bad  { background: #7f1d1d; border-color: #ef4444; }

            /* Empty global */
            .grades-empty-global {
                padding: 48px; text-align: center; color: #9ca3af;
                border: 1px solid #e5e7eb; border-radius: 12px;
            }
            .dark .grades-empty-global { color: #6b7280; border-color: #374151; }
        </style>

        <div class="grades-wrapper">
            @foreach ($gradesByTerm as $term => $disciplines)
                <div class="grades-card">
                    {{-- Header do Bimestre --}}
                    <div class="grades-header">
                        <h3>{{ $termLabels[$term] ?? $term }}</h3>
                        <div style="text-align: right;">
                            <span class="term-avg-label">Média do Bimestre</span>
                            <br>
                            <span class="term-avg-value">
                                {{ number_format($termAverages[$term] ?? 0, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    @if ($disciplines->count() > 0)
                        <div style="overflow-x: auto;">
                            <table class="grades-table">
                                <thead>
                                    <tr>
                                        <th class="text-left">Disciplina</th>
                                        <th class="text-left">Turma</th>
                                        @foreach ($assessmentColumns as $assessment)
                                            <th class="text-center">{{ $assessment }}</th>
                                        @endforeach
                                        <th class="text-center">MÉDIA</th>
                                        <th class="text-center">DATA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($disciplines as $subjectId => $subjectData)
                                        <tr>
                                            <td class="grades-subject">{{ $subjectData['name'] }}</td>
                                            <td><span class="grades-class-badge">{{ $subjectData['class'] }}</span></td>

                                            @foreach ($assessmentColumns as $assessment)
                                                <td class="text-center">
                                                    @if (isset($subjectData['grades'][$assessment]))
                                                        @php
                                                            $score = $subjectData['grades'][$assessment];
                                                            $level = $score >= 7 ? 'good' : ($score >= 5 ? 'warn' : 'bad');
                                                        @endphp
                                                        <span class="grade-circle {{ $level }}">
                                                            {{ number_format($score, 1, ',', '.') }}
                                                        </span>
                                                    @else
                                                        <span class="grades-empty">—</span>
                                                    @endif
                                                </td>
                                            @endforeach

                                            <td class="text-center">
                                                @php
                                                    $avg = $subjectData['average'];
                                                    $level = $avg >= 7 ? 'good' : ($avg >= 5 ? 'warn' : 'bad');
                                                @endphp
                                                <span class="grade-avg {{ $level }}">
                                                    {{ number_format($avg, 1, ',', '.') }}
                                                </span>
                                            </td>

                                            <td class="text-center grades-date">
                                                {{ $subjectData['lastDate'] ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="grades-no-data">
                            Nenhuma nota registrada neste bimestre.
                        </div>
                    @endif
                </div>
            @endforeach

            @if (count($gradesByTerm) === 0)
                <div class="grades-empty-global">
                    <div style="font-size: 3rem; margin-bottom: 8px;">📚</div>
                    <p style="font-size: 1.1rem; margin: 0;">Nenhuma nota registrada ainda.</p>
                </div>
            @endif

            <div class="grades-legend">
                <div class="grades-legend-item">
                    <span class="legend-dot good"></span>
                    <span class="grades-legend-text">Bom (≥ 7,0)</span>
                </div>
                <div class="grades-legend-item">
                    <span class="legend-dot warn"></span>
                    <span class="grades-legend-text">Atenção (5,0 a 6,9)</span>
                </div>
                <div class="grades-legend-item">
                    <span class="legend-dot bad"></span>
                    <span class="grades-legend-text">Baixo (&lt; 5,0)</span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
