<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $context = $data['context'];
        $students = $data['students'];
        $assessment = $context['assessment'] ?? null;
        $minimum = $data['minimumGrade'];
        $maxScore = (float) ($assessment?->max_score ?? 10);
        $minimumLabel = number_format($minimum, 1, ',', '');
    @endphp

    <style>
        .tg-page { --tg-surface:#fafbfc; --tg-muted:#f0f2f4; --tg-border:#d4d7db; --tg-text:#1c1f23; --tg-soft:#52585f; --tg-primary:#3d5a80; display:grid; gap:1.25rem; color:var(--tg-text); color-scheme:light; }
        :root.dark .tg-page { --tg-surface:#232b38; --tg-muted:#1a212b; --tg-border:#3c4756; --tg-text:#e1e5ea; --tg-soft:#aeb8c5; --tg-primary:#7c9bc1; color-scheme:dark; }
        .tg-card { background:var(--tg-surface); border:1px solid var(--tg-border); border-radius:0; box-shadow:var(--lumina-shadow, 0 2px 10px rgb(0 0 0 / 5%)); }
        .tg-filters { padding:1.25rem 1.5rem; }
        .tg-title { margin:0 0 1rem; font-size:.92rem; font-weight:800; }
        .tg-filter-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.9rem; }
        .tg-field label { display:block; margin-bottom:.4rem; font-size:.76rem; color:var(--tg-soft); font-weight:700; }
        .tg-field select, .tg-field input, .tg-field textarea { width:100%; min-height:2.6rem; padding:.52rem .7rem; border:1px solid var(--tg-border); border-radius:.5rem; background:var(--tg-surface); color:var(--tg-text); color-scheme:inherit; }
        .tg-field :is(select,input,textarea):focus-visible { outline:2px solid var(--tg-primary); outline-offset:2px; }
        .tg-field :is(input,textarea):disabled { opacity:.65; cursor:not-allowed; }
        .tg-context { padding:.8rem 1.5rem; border-top:1px solid var(--tg-border); background:var(--tg-muted); color:var(--tg-soft); font-size:.8rem; }
        .tg-context strong { color:var(--tg-text); }
        .tg-notice { padding:.8rem 1rem; border:1px solid var(--tg-border); border-left:4px solid var(--tg-primary); background:var(--tg-surface); font-size:.85rem; }
        .tg-notice.success { border-left-color:#23824d; }
        .tg-notice.danger { border-left-color:#c55252; }
        .tg-head { padding:1.15rem 1.4rem; display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap; }
        .tg-head h3 { margin:0; font-size:1rem; font-weight:800; }
        .tg-head p { margin:.25rem 0 0; color:var(--tg-soft); font-size:.78rem; }
        .tg-count { font-size:.78rem; font-weight:700; color:var(--tg-soft); }
        .tg-table-head, .tg-row { display:grid; grid-template-columns:3rem minmax(11rem,1.4fr) 8rem minmax(10rem,1fr) 7rem; gap:.8rem; align-items:center; }
        .tg-table-head { padding:.7rem 1.4rem; background:var(--tg-muted); border-block:1px solid var(--tg-border); color:var(--tg-soft); font-size:.7rem; letter-spacing:.05em; font-weight:800; text-transform:uppercase; }
        .tg-row { padding:.7rem 1.4rem; border-bottom:1px solid var(--tg-border); min-height:4.3rem; }
        .tg-row:last-child { border-bottom:0; }
        .tg-row:hover { background:var(--tg-muted); }
        .tg-number { color:var(--tg-soft); font-size:.82rem; font-weight:700; }
        .tg-student strong { display:block; font-size:.85rem; }
        .tg-student small { display:block; color:var(--tg-soft); font-size:.72rem; margin-top:.15rem; }
        .tg-score { max-width:8rem; font-size:1rem; font-weight:800; text-align:center; }
        .tg-score.passing { border-color:#23824d; background:#ecfdf3; color:#166534; }
        .tg-score.attention { border-color:#c96727; background:#fff7ed; color:#9a3412; }
        :root.dark .tg-score.passing { background:#123426; color:#a7efc2; }
        :root.dark .tg-score.attention { background:#3d2a1c; color:#ffd4a8; }
        .tg-status { font-size:.73rem; font-weight:800; }
        .tg-status.passing { color:#23824d; }
        .tg-status.attention { color:#b45309; }
        .tg-status.empty { color:var(--tg-soft); }
        .tg-status.locked { color:var(--tg-soft); }
        .tg-comment { resize:vertical; min-height:2.6rem !important; }
        .tg-empty { padding:2.5rem 1.5rem; text-align:center; color:var(--tg-soft); }
        .tg-empty strong { display:block; color:var(--tg-text); margin-bottom:.3rem; }
        .tg-actions { padding:1.05rem 1.4rem; border-top:1px solid var(--tg-border); display:flex; justify-content:space-between; gap:.7rem; align-items:center; flex-wrap:wrap; }
        .tg-actions-buttons { display:flex; gap:.6rem; flex-wrap:wrap; }
        .tg-help { color:var(--tg-soft); font-size:.76rem; }
        @media (max-width:960px) { .tg-filter-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .tg-table-head,.tg-row { grid-template-columns:2.5rem minmax(9rem,1.4fr) 7rem minmax(8rem,1fr); } .tg-table-head span:last-child,.tg-row .tg-status { display:none; } }
        @media (max-width:640px) { .tg-filters,.tg-head,.tg-actions { padding-inline:1rem; } .tg-filter-grid { grid-template-columns:1fr; } .tg-table-head { display:none; } .tg-row { grid-template-columns:minmax(0,1fr) 7rem; padding:.85rem 1rem; } .tg-number { display:none; } .tg-row .tg-comment-field { grid-column:1 / -1; } .tg-row .tg-status { display:block; grid-column:1 / -1; } .tg-context { padding-inline:1rem; } }
    </style>

    <div class="tg-page">
        @if($data['saveSummary'])
            <div class="tg-notice success" role="status">{{ $data['saveSummary']['published'] ? 'Notas publicadas' : 'Rascunho salvo' }}: {{ $data['saveSummary']['created'] }} criadas, {{ $data['saveSummary']['updated'] }} atualizadas e {{ $data['saveSummary']['deleted'] }} removidas.</div>
        @endif
        @if($data['isBlocked'])
            <div class="tg-notice danger" role="alert">O vínculo do professor não permite lançar notas.</div>
        @endif
        @if($data['assessmentClosed'] || $data['hasLockedGrades'])
            <div class="tg-notice danger" role="alert">Esta avaliação está fechada ou tem notas publicadas. Os lançamentos ficam somente para consulta.</div>
        @endif
        @if($data['contextError'])
            <div class="tg-notice danger" role="alert">{{ $data['contextError'] }}</div>
        @endif

        <section class="tg-card" aria-labelledby="tg-filters-title">
            <div class="tg-filters">
                <h3 class="tg-title" id="tg-filters-title">Contexto da avaliação</h3>
                <div class="tg-filter-grid">
                    <div class="tg-field"><label for="tg-class">Turma ativa</label><select id="tg-class" wire:model.live="selectedClassId"><option value="">Selecione</option>@foreach($data['classes'] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="tg-field"><label for="tg-subject">Disciplina</label><select id="tg-subject" wire:model.live="selectedSubjectId"><option value="">Selecione</option>@foreach($data['subjects'] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="tg-field"><label for="tg-assessment">Avaliação</label><select id="tg-assessment" wire:model.live="selectedAssessmentId"><option value="">Selecione</option>@foreach($data['assessments'] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                </div>
            </div>
            @if($context)
                <div class="tg-context"><strong>{{ $context['class']->name }}</strong> · {{ $assessment->subject?->name ?? 'Disciplina' }} · {{ $assessment->title }} · {{ $assessment->date?->format('d/m/Y') ?? 'Sem data' }} · Nota máxima {{ number_format($maxScore, 1, ',', '') }}</div>
            @endif
        </section>

        <section class="tg-card" aria-labelledby="tg-roster-title">
            <div class="tg-head">
                <div><h3 id="tg-roster-title">Notas dos alunos</h3><p>Média da escola: {{ $minimumLabel }}. As cores indicam o desempenho parcial em relação a essa referência.</p></div>
                <span class="tg-count">{{ $data['summary']['filled'] }} de {{ $data['summary']['total'] }} notas preenchidas</span>
            </div>
            @if($students->isEmpty())
                <div class="tg-empty"><strong>Nenhum aluno para esta seleção.</strong> Confira a turma, a disciplina e a avaliação do ano letivo vigente.</div>
            @else
                <div class="tg-table-head" aria-hidden="true"><span>Nº</span><span>Aluno</span><span>Nota</span><span>Observação</span><span>Situação</span></div>
                <div class="tg-rows">
                    @foreach($students as $row)
                        @php
                            $score = $row['score'];
                            $hasScore = $score !== null && $score !== '' && is_numeric($score);
                            $passing = $hasScore && (float) $score >= $minimum;
                            $gradeClass = !$hasScore ? '' : ($passing ? 'passing' : 'attention');
                            $disabled = $row['locked'] || $data['assessmentClosed'] || $data['schoolYearClosed'] || $data['hasLockedGrades'] || $data['isBlocked'];
                        @endphp
                        <div class="tg-row" wire:key="tg-row-{{ $row['student_id'] }}">
                            <span class="tg-number">{{ $row['roll_number'] ?? '—' }}</span>
                            <span class="tg-student"><strong>{{ $row['student_name'] }}</strong><small>Nº {{ $row['roll_number'] ?? '—' }} · Matrícula {{ $row['registration_number'] }}</small></span>
                            <div class="tg-field"><label class="sr-only" for="tg-score-{{ $row['student_id'] }}">Nota de {{ $row['student_name'] }}</label><input class="tg-score {{ $gradeClass }}" id="tg-score-{{ $row['student_id'] }}" type="number" inputmode="decimal" min="0" max="{{ $maxScore }}" step="0.01" placeholder="—" wire:model.live.debounce.400ms="gradeRows.{{ $row['student_id'] }}.score" @disabled($disabled) aria-describedby="tg-limit-{{ $row['student_id'] }}"><span class="sr-only" id="tg-limit-{{ $row['student_id'] }}">Nota de zero a {{ $maxScore }}</span>@error("gradeRows.{$row['student_id']}.score")<small role="alert">{{ $message }}</small>@enderror</div>
                            <div class="tg-field tg-comment-field"><label class="sr-only" for="tg-comment-{{ $row['student_id'] }}">Observação de {{ $row['student_name'] }}</label><input class="tg-comment" id="tg-comment-{{ $row['student_id'] }}" type="text" placeholder="Opcional" wire:model.blur="gradeRows.{{ $row['student_id'] }}.comment" @disabled($disabled)></div>
                            <span class="tg-status {{ $row['locked'] ? 'locked' : ($hasScore ? $gradeClass : 'empty') }}">{{ $row['locked'] ? 'Publicada' : ($hasScore ? ($passing ? 'Na média' : 'Abaixo da média') : 'Pendente') }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="tg-actions">
                    <span class="tg-help">Salve parcialmente como rascunho. Preencha todas as notas antes de publicar; a publicação encerra a edição.</span>
                    <div class="tg-actions-buttons">
                        @if($assessment)<x-filament::button tag="a" :href="route('professor.reports.grades', $assessment)" target="_blank" color="gray" icon="fas-file-pdf">Imprimir PDF</x-filament::button>@endif
                        @if($data['canSave'])<x-filament::button type="button" color="primary" icon="fas-floppy-disk" wire:click="saveDraft" wire:loading.attr="disabled" wire:target="saveDraft,publishGrades">Salvar rascunho</x-filament::button>@endif
                        @if($data['canPublish'])<x-filament::button type="button" color="warning" icon="fas-paper-plane" wire:click="publishGrades" wire:loading.attr="disabled" wire:target="saveDraft,publishGrades" :disabled="$data['summary']['remaining'] > 0">Publicar notas</x-filament::button>@endif
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
