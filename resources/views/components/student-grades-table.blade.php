<div class="space-y-8">
    @foreach ($gradesByTerm as $term => $disciplines)
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    {{ $termLabels[$term] ?? $term }}
                </h3>
                <div class="text-right">
                    <p class="text-sm text-gray-600">Média do Bimestre:</p>
                    <p class="text-2xl font-bold text-blue-600">
                        {{ number_format($termAverages[$term] ?? 0, 2, ',', '.') }}
                    </p>
                </div>
            </div>

            @if ($disciplines->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-b-2 border-blue-200">
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Disciplina
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 hidden sm:table-cell">
                                    Turma
                                </th>

                                @foreach ($assessmentColumns as $assessment)
                                    <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                                        <span class="block">{{ explode(' ', $assessment)[0] }}</span>
                                        <span class="text-xs font-normal">{{ explode(' ', $assessment)[1] ?? '' }}</span>
                                    </th>
                                @endforeach

                                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                                    <span class="block">Média</span>
                                </th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 hidden md:table-cell">
                                    Data
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($disciplines as $subjectId => $subjectData)
                                <tr class="border-b border-gray-150 hover:bg-gray-50 transition">
                                    <!-- Disciplina -->
                                    <td class="px-4 py-3 font-semibold text-gray-800 text-sm sm:text-base">
                                        {{ $subjectData['name'] }}
                                        <br class="sm:hidden">
                                        <span class="sm:hidden text-xs text-gray-500">{{ $subjectData['class'] }}</span>
                                    </td>

                                    <!-- Turma -->
                                    <td class="px-4 py-3 text-gray-700 text-sm hidden sm:table-cell">
                                        <span class="inline-block bg-gray-100 px-2 py-1 rounded text-xs font-medium">
                                            {{ $subjectData['class'] }}
                                        </span>
                                    </td>

                                    <!-- Notas das Provas -->
                                    @foreach ($assessmentColumns as $assessment)
                                        <td class="px-4 py-3 text-center">
                                            @if (isset($subjectData['grades'][$assessment]))
                                                <span class="inline-flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full text-xs sm:text-sm font-bold transition
                                                    @if ($subjectData['grades'][$assessment] >= 7)
                                                        bg-green-100 text-green-800
                                                    @elseif ($subjectData['grades'][$assessment] >= 5)
                                                        bg-yellow-100 text-yellow-800
                                                    @else
                                                        bg-red-100 text-red-800
                                                    @endif
                                                ">
                                                    {{ number_format($subjectData['grades'][$assessment], 1, ',', '.') }}
                                                </span>
                                            @else
                                                <span class="text-gray-300 text-sm">—</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Média Geral -->
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-full text-xs sm:text-sm font-bold border-2 transition
                                            @if ($subjectData['average'] >= 7)
                                                bg-green-50 border-green-300 text-green-800
                                            @elseif ($subjectData['average'] >= 5)
                                                bg-yellow-50 border-yellow-300 text-yellow-800
                                            @else
                                                bg-red-50 border-red-300 text-red-800
                                            @endif
                                        ">
                                            {{ number_format($subjectData['average'], 1, ',', '.') }}
                                        </span>
                                    </td>

                                    <!-- Data da Última Nota -->
                                    <td class="px-4 py-3 text-gray-700 text-sm hidden md:table-cell">
                                        {{ $subjectData['lastDate'] ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-6 bg-gray-50 rounded">
                    Nenhuma nota registrada neste bimestre.
                </p>
            @endif
        </div>
    @endforeach

    @if (count($gradesByTerm) === 0)
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="inline-block">
                <div class="text-5xl mb-3">📚</div>
                <p class="text-gray-500 text-lg">Nenhuma nota registrada ainda.</p>
            </div>
        </div>
    @endif

    <!-- Legenda -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mt-6 border-l-4 border-blue-500">
        <p class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zm3 0a1 1 0 11-2 0 1 1 0 012 0zm3 0a1 1 0 11-2 0 1 1 0 012 0z" clip-rule="evenodd" />
            </svg>
            Legenda de Classificação
        </p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="flex items-center gap-3 bg-white p-3 rounded">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-100 text-green-800 text-sm font-bold">✓</span>
                <div>
                    <p class="text-xs font-semibold text-gray-600">Bom Desempenho</p>
                    <p class="text-sm font-bold text-green-700">≥ 7.0</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-white p-3 rounded">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-yellow-100 text-yellow-800 text-sm font-bold">⚠</span>
                <div>
                    <p class="text-xs font-semibold text-gray-600">Atenção Necessária</p>
                    <p class="text-sm font-bold text-yellow-700">5.0 a 6.9</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-white p-3 rounded">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-100 text-red-800 text-sm font-bold">✕</span>
                <div>
                    <p class="text-xs font-semibold text-gray-600">Desempenho Baixo</p>
                    <p class="text-sm font-bold text-red-700">&lt; 5.0</p>
                </div>
            </div>
        </div>
    </div>
</div>

