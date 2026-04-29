<x-filament-widgets::widget>
    <x-filament::section heading="Minhas Notas">
        @php
            $data = $this->getViewData();
            $gradesByTerm = $data['gradesByTerm'];
            $assessmentColumns = $data['assessmentColumns'];
            $termLabels = $data['termLabels'];
            $termAverages = $data['termAverages'];
            $availableTerms = $data['availableTerms'] ?? [];
        @endphp

        {{-- Combobox: selecionar boletim por bimestre --}}
        @if (count($availableTerms) > 0)
            <div class="grades-select-wrapper" style="margin-bottom: 1.5rem;">
                <label for="selectedTerm" class="grades-select-label">Boletim (período)</label>
                <select
                    id="selectedTerm"
                    wire:model.live="selectedTerm"
                    class="grades-select"
                >
                    @foreach ($availableTerms as $termKey)
                        <option value="{{ $termKey }}">{{ $termLabels[$termKey] ?? $termKey }}</option>
                    @endforeach
                </select>
            </div>
        @endif
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
                                                        <span class="grade-value {{ $level }}">
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
                                                <span class="grade-avg-value {{ $level }}">
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
                    @svg('fas-book-open', 'grades-empty-icon')
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
