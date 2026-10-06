<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $students = $data['students'];
        $context = $data['context'];
    @endphp

    <style>
        .ta-page {
            --ta-surface: #fafbfc;
            --ta-muted: #f0f2f4;
            --ta-border: #d4d7db;
            --ta-text: #1c1f23;
            --ta-soft: #52585f;
            --ta-primary: #3d5a80;
            --ta-primary-strong: #2e4468;
            display: grid;
            gap: 1.25rem;
            color: var(--ta-text);
            color-scheme: light;
        }
        :root.dark .ta-page {
            --ta-surface: #232b38;
            --ta-muted: #1a212b;
            --ta-border: #3c4756;
            --ta-text: #c7cdd5;
            --ta-soft: #aeb8c5;
            --ta-primary: #5e7ea3;
            --ta-primary-strong: #4c6c91;
            color-scheme: dark;
        }
        .ta-card { background: var(--ta-surface); border: 1px solid var(--ta-border); border-radius: 0; box-shadow: var(--lumina-shadow, 0 2px 10px rgb(0 0 0 / 5%)); }
        .ta-filters { padding: 1.25rem 1.5rem; }
        .ta-section-title { margin: 0 0 1rem; font-size: .92rem; font-weight: 800; }
        .ta-filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .9rem; }
        .ta-field label { display: block; margin-bottom: .4rem; font-size: .76rem; color: var(--ta-soft); font-weight: 700; }
        .ta-field select, .ta-field input { width: 100%; min-height: 2.6rem; padding: .52rem .7rem; border: 1px solid var(--ta-border); border-radius: .5rem; background: var(--ta-surface); color: var(--ta-text); color-scheme: inherit; }
        .dark .ta-field input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); }
        .ta-field :is(select, input):focus-visible, .ta-name:focus-visible, .ta-check:focus-visible, .ta-close:focus-visible { outline: 2px solid var(--ta-primary); outline-offset: 2px; }
        .ta-context { padding: .8rem 1.5rem; border-top: 1px solid var(--ta-border); background: var(--ta-muted); color: var(--ta-soft); font-size: .8rem; }
        .ta-context strong { color: var(--ta-text); }
        .ta-notice { padding: .8rem 1rem; border: 1px solid var(--ta-border); border-left: 4px solid var(--ta-primary); background: var(--ta-surface); border-radius: 0; font-size: .85rem; }
        .ta-notice.is-danger { border-left-color: #c55252; }
        .ta-notice.is-success { border-left-color: #3d8f66; }
        .ta-roster-head { padding: 1.15rem 1.4rem; display: flex; justify-content: space-between; gap: 1rem; align-items: center; flex-wrap: wrap; }
        .ta-roster-head h3 { margin: 0; font-size: 1rem; font-weight: 800; }
        .ta-roster-count { font-size: .78rem; font-weight: 700; color: var(--ta-soft); }
        .ta-table-head, .ta-row { display: grid; grid-template-columns: 3.5rem 4.25rem minmax(0, 1fr) 8rem; align-items: center; gap: .65rem; }
        .ta-table-head { padding: .7rem 1.4rem; background: var(--ta-muted); border-block: 1px solid var(--ta-border); color: var(--ta-soft); font-size: .7rem; letter-spacing: .05em; font-weight: 800; text-transform: uppercase; }
        .ta-row { padding: .72rem 1.4rem; border-bottom: 1px solid var(--ta-border); min-height: 4.25rem; }
        .ta-row:last-child { border-bottom: 0; }
        .ta-row:hover { background: var(--ta-muted); }
        .ta-number { color: var(--ta-soft); font-size: .82rem; font-weight: 700; }
        .ta-avatar { display: grid; place-items: center; width: 2.55rem; height: 2.55rem; border-radius: 50%; overflow: hidden; background: var(--ta-muted); border: 1px solid var(--ta-border); color: var(--ta-soft); }
        .ta-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .ta-avatar svg { width: 1rem; height: 1rem; }
        .ta-name { border: 0; background: none; color: var(--ta-text); padding: 0; text-align: left; font: inherit; font-weight: 700; cursor: pointer; text-decoration: underline; text-decoration-color: transparent; text-underline-offset: .2rem; }
        .ta-name:hover { color: var(--ta-primary); text-decoration-color: currentColor; }
        .ta-name-static { font-weight: 700; }
        .ta-student-line { display: flex; align-items: baseline; gap: .4rem .65rem; flex-wrap: wrap; min-width: 0; }
        .ta-student-meta { color: var(--ta-soft); font-size: .72rem; white-space: nowrap; }
        .ta-presence { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
        .ta-check-label { display: inline-flex; gap: .48rem; align-items: center; cursor: pointer; font-size: .83rem; font-weight: 700; }
        .ta-check { width: 1.12rem; height: 1.12rem; accent-color: var(--ta-primary); cursor: pointer; }
        .ta-check:disabled, .ta-check-label:has(.ta-check:disabled) { cursor: not-allowed; opacity: .7; }
        .ta-status-note { color: var(--ta-soft); font-size: .7rem; }
        .ta-actions { padding: 1.05rem 1.4rem; border-top: 1px solid var(--ta-border); display: flex; justify-content: flex-end; align-items: center; gap: .6rem; flex-wrap: wrap; }
        .ta-actions .ta-save-button { background: var(--ta-primary) !important; border-color: var(--ta-primary) !important; color: #fff !important; }
        .ta-actions .ta-save-button:hover { background: var(--ta-primary-strong) !important; border-color: var(--ta-primary-strong) !important; }
        .ta-empty { padding: 2.5rem 1.5rem; text-align: center; color: var(--ta-soft); }
        .ta-empty strong { display: block; margin-bottom: .3rem; color: var(--ta-text); }
        .ta-modal-backdrop { position: fixed; z-index: 100; inset: 0; padding: 1rem; display: grid; place-items: center; background: rgb(0 0 0 / 55%); }
        .ta-modal { width: min(100%, 34rem); max-height: min(90vh, 46rem); overflow: auto; background: var(--ta-surface); border: 1px solid var(--ta-border); border-radius: 0; box-shadow: 0 20px 50px rgb(0 0 0 / 25%); }
        .ta-modal-head { padding: 1.25rem 1.4rem; display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; border-bottom: 1px solid var(--ta-border); }
        .ta-modal-id { display: flex; align-items: center; gap: .85rem; }
        .ta-modal-id h3 { margin: 0; font-size: 1.1rem; font-weight: 800; }
        .ta-modal-id p { margin: .2rem 0 0; color: var(--ta-soft); font-size: .77rem; }
        .ta-modal-id .ta-avatar { width: 3rem; height: 3rem; flex: none; }
        .ta-close { background: var(--ta-muted); border: 1px solid var(--ta-border); border-radius: .4rem; color: var(--ta-text); width: 2rem; height: 2rem; cursor: pointer; font-size: 1.1rem; }
        .ta-details { padding: 1.25rem 1.4rem; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .ta-detail dt { margin-bottom: .2rem; color: var(--ta-soft); font-size: .7rem; font-weight: 700; text-transform: uppercase; }
        .ta-detail dd { margin: 0; overflow-wrap: anywhere; font-size: .86rem; font-weight: 600; }
        @media (max-width: 960px) { .ta-filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) {
            .ta-filters, .ta-roster-head, .ta-actions { padding-inline: 1rem; }
            .ta-filter-grid, .ta-details { grid-template-columns: 1fr; }
            .ta-table-head { display: none; }
            .ta-row { grid-template-columns: 2.7rem minmax(0, 1fr) auto; padding: .8rem 1rem; gap: .55rem; }
            .ta-number { display: none; }
            .ta-check-label { font-size: .75rem; }
            .ta-student-meta { white-space: normal; }
            .ta-context { padding-inline: 1rem; }
        }
    </style>

    <div class="ta-page">
        @if($data['saveSummary'])
            <div class="ta-notice is-success" role="status">Frequência salva: {{ $data['saveSummary']['created'] }} registros criados e {{ $data['saveSummary']['updated'] }} atualizados.</div>
        @endif
        @if($data['isBlocked'])
            <div class="ta-notice is-danger" role="alert">O vínculo do professor não permite lançar frequência.</div>
        @endif
        @if($data['schoolYearClosed'])
            <div class="ta-notice is-danger" role="alert">A turma ou o ano letivo está encerrado. A chamada fica somente para consulta.</div>
        @endif
        @if($data['contextError'])
            <div class="ta-notice is-danger" role="alert">{{ $data['contextError'] }}</div>
        @endif

        <section class="ta-card" aria-labelledby="ta-filters-title">
            <div class="ta-filters">
                <h3 class="ta-section-title" id="ta-filters-title">Contexto da aula</h3>
                <div class="ta-filter-grid">
                    <div class="ta-field"><label for="ta-class">Turma</label><select id="ta-class" wire:model.live="selectedClassId"><option value="">Selecione</option>@foreach($data['classes'] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="ta-field"><label for="ta-subject">Disciplina</label><select id="ta-subject" wire:model.live="selectedSubjectId"><option value="">Selecione</option>@foreach($data['subjects'] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="ta-field"><label for="ta-date">Data da aula</label><input id="ta-date" type="date" wire:model.live="selectedDate"></div>
                    <div class="ta-field"><label for="ta-lesson">Aula / horário</label><select id="ta-lesson" wire:model.live="selectedLessonId"><option value="">Selecione</option>@foreach($data['lessons'] as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>
                </div>
            </div>
            @if($context)
                <div class="ta-context"><strong>{{ $context['class']->name }}</strong> · {{ $context['subject']->name }} · {{ $context['lesson']->start_time?->format('H:i') }}–{{ $context['lesson']->end_time?->format('H:i') }} · {{ $context['lesson']->date?->format('d/m/Y') }}</div>
            @endif
        </section>

        <section class="ta-card" aria-labelledby="ta-roster-title">
            <div class="ta-roster-head">
                <h3 id="ta-roster-title">Alunos da turma</h3>
                <span class="ta-roster-count">{{ $students->count() }} alunos</span>
            </div>
            @if($students->isEmpty())
                <div class="ta-empty"><strong>Nenhuma aula ou aluno para esta seleção.</strong> Confira a turma, a disciplina, a data e o horário.</div>
            @else
                <div class="ta-table-head" aria-hidden="true"><span>Nº</span><span>Foto</span><span>Aluno</span><span>Presente</span></div>
                <div class="ta-rows">
                    @foreach($students as $row)
                        @php
                            $status = $row['status'];
                            $present = in_array($status, [\App\Enums\AttendanceStatus::PRESENT->value, \App\Enums\AttendanceStatus::LATE->value], true);
                            $specialStatus = in_array($status, [\App\Enums\AttendanceStatus::LATE->value, \App\Enums\AttendanceStatus::EXCUSED->value], true)
                                ? \App\Enums\AttendanceStatus::from($status)->label()
                                : null;
                        @endphp
                        <div class="ta-row" wire:key="ta-row-{{ $row['student_id'] }}">
                            <span class="ta-number">{{ $row['roll_number'] ?? '—' }}</span>
                            <span class="ta-avatar">
                                @if($row['photo_url'])<img src="{{ $row['photo_url'] }}" alt="Foto de {{ $row['student_name'] }}" loading="lazy">
                                @else <x-filament::icon icon="fas-user" aria-hidden="true" /> @endif
                            </span>
                            <span class="ta-student-line">
                                @if($data['studentDetailsEnabled'])
                                    <button type="button" class="ta-name" wire:click="openStudentDetails({{ $row['student_id'] }})" aria-label="Ver detalhes de {{ $row['student_name'] }}">{{ $row['student_name'] }}</button>
                                @else
                                    <span class="ta-name-static">{{ $row['student_name'] }}</span>
                                @endif
                                <span class="ta-student-meta">Nº {{ $row['roll_number'] ?? '—' }} · Matrícula {{ $row['registration_number'] }}</span>
                            </span>
                            <span class="ta-presence">
                                <label class="ta-check-label" for="ta-presence-{{ $row['student_id'] }}">
                                    <input class="ta-check" id="ta-presence-{{ $row['student_id'] }}" type="checkbox"
                                           wire:key="ta-presence-{{ $row['student_id'] }}-{{ $status ?? 'none' }}"
                                           wire:click="togglePresence({{ $row['student_id'] }})"
                                           @checked($present) @disabled(!$data['canEdit'])
                                           aria-label="Presente: {{ $row['student_name'] }}">
                                    Presente
                                </label>
                                @if($specialStatus)<span class="ta-status-note" aria-live="polite">{{ $specialStatus }}</span>@endif
                            </span>
                        </div>
                    @endforeach
                </div>
                <div class="ta-actions">
                    @if($context)
                        <x-filament::button tag="a" :href="route('professor.reports.attendance', $context['lesson'])" target="_blank" color="gray" icon="fas-file-pdf">Imprimir PDF</x-filament::button>
                    @endif
                    @if($data['canEdit'])
                        <x-filament::button type="button" color="gray" wire:click="markAllPresent">Marcar todos presentes</x-filament::button>
                        <x-filament::button type="button" class="ta-save-button" icon="fas-floppy-disk" wire:click="saveAttendance" :disabled="!$data['canSubmit']">Salvar chamada</x-filament::button>
                    @endif
                </div>
            @endif
        </section>
    </div>

    @if($data['studentDetailsEnabled'] && $data['studentDetails'])
        @php($detail = $data['studentDetails'])
        <div class="ta-page ta-modal-backdrop" wire:click.self="closeStudentDetails" x-data x-init="$nextTick(() => $refs.close.focus())" x-on:keydown.escape.window="$wire.closeStudentDetails()">
            <section class="ta-modal" role="dialog" aria-modal="true" aria-labelledby="ta-detail-title">
                <div class="ta-modal-head">
                    <div class="ta-modal-id">
                        <span class="ta-avatar">@if($detail['photo'])<img src="{{ $detail['photo'] }}" alt="Foto de {{ $detail['name'] }}">@else<x-filament::icon icon="fas-user" aria-hidden="true" />@endif</span>
                        <div><h3 id="ta-detail-title">{{ $detail['name'] }}</h3><p>Dados do aluno · Turma {{ $detail['class'] }}</p></div>
                    </div>
                    <button class="ta-close" type="button" x-ref="close" wire:click="closeStudentDetails" aria-label="Fechar detalhes do aluno">×</button>
                </div>
                <dl class="ta-details">
                    <div class="ta-detail"><dt>Matrícula</dt><dd>{{ $detail['registration'] ?? '—' }}</dd></div>
                    <div class="ta-detail"><dt>Número de chamada</dt><dd>{{ $detail['rollNumber'] ?? '—' }}</dd></div>
                    <div class="ta-detail"><dt>Situação da matrícula</dt><dd>{{ $detail['enrollmentStatus'] }}</dd></div>
                    <div class="ta-detail"><dt>Data de nascimento</dt><dd>{{ $detail['birthDate'] ?? 'Não informada' }}</dd></div>
                    <div class="ta-detail"><dt>Responsável principal</dt><dd>{{ $detail['guardian'] ?: 'Não informado' }}</dd></div>
                    <div class="ta-detail"><dt>Telefone do responsável</dt><dd>{{ $detail['guardianPhone'] ?: 'Não informado' }}</dd></div>
                </dl>
            </section>
        </div>
    @endif
</x-filament-panels::page>
