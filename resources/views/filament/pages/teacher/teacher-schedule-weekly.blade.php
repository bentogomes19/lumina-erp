<x-filament-panels::page>
    @php
        $data = $this->getPageData();
        $lessons = $data['lessons'];
        $selectedLesson = $data['selectedLesson'];
        $fallbacks = $data['pedagogicalFallbacks'];
        $filters = $data['filters'];
        $isOnLeave = $data['isOnLeave'];
        $weekStart = $data['weekStart'];
        $weekEnd = $data['weekEnd'];
        $weekDays = $data['weekDays'];

        $grouped = $lessons->groupBy(fn($lesson) => $lesson->date?->toDateString());
        $selectedClass = $selectedLesson?->schoolClass;
        $selectedSubject = $selectedLesson?->subject;
        $selectedFallbackKey = $selectedLesson ? $this->gradeLevelSubjectKey($selectedClass?->grade_level_id, $selectedLesson->subject_id) : null;
        $selectedFallback = $selectedFallbackKey ? ($fallbacks[$selectedFallbackKey] ?? null) : null;
        $selectedPlannedContent = $selectedLesson ? ($selectedLesson->content ?: $selectedLesson->topic ?: data_get($selectedFallback, 'program_content') ?: $selectedSubject?->description) : null;
        $selectedSyllabus = $selectedLesson ? (data_get($selectedFallback, 'syllabus') ?: $selectedSubject?->description ?: $selectedLesson->topic) : null;
        $selectedObjectives = $selectedLesson ? ($selectedLesson->objectives ?: data_get($selectedFallback, 'objectives')) : null;
    @endphp
    <div class="ts-shell">
        @if($isOnLeave)
            <div class="ts-alert">
                <x-filament::icon icon="fas-triangle-exclamation" />
                <span>Você está atualmente afastado(a). Sua agenda pode não refletir aulas ativas.</span>
            </div>
        @endif

        <div class="ts-toolbar">
            <div class="ts-week-title">
                <x-filament::icon icon="fas-calendar-week" />
                <div>
                    <strong>Semana de {{ $weekStart->format('d/m') }} a {{ $weekEnd->format('d/m/Y') }}</strong>
                    <span>{{ $lessons->count() }} aula{{ $lessons->count() === 1 ? '' : 's' }} entre segunda e sexta</span>
                </div>
            </div>
            <div class="ts-week-actions">
                <button wire:click="previousWeek" class="ts-icon-button" type="button" title="Semana anterior"><x-filament::icon icon="fas-chevron-left" /></button>
                <button wire:click="currentWeek" class="ts-filter-reset" type="button"><x-filament::icon icon="fas-calendar-day" />Semana atual</button>
                <button wire:click="nextWeek" class="ts-icon-button" type="button" title="Próxima semana"><x-filament::icon icon="fas-chevron-right" /></button>
            </div>
        </div>

        <div class="ts-filters">
            <div class="ts-filter-group"><label>Período Letivo</label><select wire:model.live="filterSchoolYear"><option value="">Todos</option>@foreach($filters['schoolYears'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="ts-filter-group"><label>Turma</label><select wire:model.live="filterClass"><option value="">Todas</option>@foreach($filters['classes'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="ts-filter-group"><label>Disciplina</label><select wire:model.live="filterSubject"><option value="">Todas</option>@foreach($filters['subjects'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="ts-filter-group"><label>Turno</label><select wire:model.live="filterShift"><option value="">Todos</option>@foreach($filters['shifts'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
            <button wire:click="resetFilters" class="ts-filter-reset" type="button"><x-filament::icon icon="fas-filter-circle-xmark" />Limpar filtros</button>
        </div>

        <div class="ts-week-grid">
            @foreach($weekDays as $day)
                @php
                    $dayLessons = $grouped[$day->toDateString()] ?? collect();
                @endphp
                <section class="ts-day-column">
                    <header class="ts-day-header"><strong>{{ ucfirst($day->translatedFormat('l')) }}</strong><span>{{ $day->format('d/m') }}</span></header>
                    <div class="ts-day-lessons">
                        @forelse($dayLessons as $lesson)
                            @php
                                $class = $lesson->schoolClass;
                                $subject = $lesson->subject;
                                $status = $lesson->status;
                                $statusValue = $status?->value ?? (string) $lesson->status;
                                $fallbackKey = $this->gradeLevelSubjectKey($class?->grade_level_id, $lesson->subject_id);
                                $fallback = $fallbacks[$fallbackKey] ?? null;
                                $plannedContent = $lesson->content ?: $lesson->topic ?: data_get($fallback, 'program_content') ?: $subject?->description;
                                $room = data_get($class, 'room');
                            @endphp
                            <button type="button" wire:click="selectLesson({{ $lesson->id }})" class="ts-lesson-card is-status-{{ $statusValue }} {{ $selectedLesson?->id === $lesson->id ? 'is-selected' : '' }}">
                                <div class="ts-lesson-topline">
                                    <span class="ts-time"><x-filament::icon icon="fas-clock" />{{ $lesson->start_time?->format('H:i') ?? '--:--' }} - {{ $lesson->end_time?->format('H:i') ?? '--:--' }}</span>
                                </div>
                                <div class="ts-lesson-title">{{ $subject?->name ?? 'Disciplina não definida' }}</div>
                                <div class="ts-lesson-subtitle">{{ $class?->name ?? 'Turma não definida' }}</div>
                                @if($plannedContent)<div class="ts-lesson-content">{{ \Illuminate\Support\Str::limit(strip_tags($plannedContent), 120) }}</div>@endif
                                <div class="ts-card-meta">
                                    @if($room)<span class="ts-meta-line"><x-filament::icon icon="fas-location-dot" />Sala {{ $room }}</span>@endif
                                    @if($lesson->topic)<span class="ts-meta-line"><x-filament::icon icon="fas-book-open" />{{ \Illuminate\Support\Str::limit($lesson->topic, 70) }}</span>@endif
                                </div>
                            </button>
                        @empty
                            <div class="ts-day-empty"><x-filament::icon icon="fas-calendar-minus" /><span>Sem aulas</span></div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        @if($selectedLesson)
            @php
                $room = data_get($selectedClass, 'room');
                $schoolYear = $selectedLesson->schoolYear ?? $selectedClass?->schoolYear;
                $shiftLabel = $selectedClass?->shift?->label() ?? null;
                $status = $selectedLesson->status;
                $statusValue = $status?->value ?? (string) $selectedLesson->status;
                $statusLabel = $status?->label() ?? ucfirst($statusValue);
            @endphp
            <div class="ts-details-backdrop" wire:click.self="closeLessonDetails">
                <section class="ts-details-modal">
                    <aside class="ts-details">
                        <div class="ts-details-header">
                            <div><h3>{{ $selectedSubject?->name ?? 'Disciplina não definida' }}</h3><p>{{ $selectedClass?->name ?? 'Turma não definida' }}</p></div>
                            <div class="ts-details-actions">
                                <span class="ts-status is-status-{{ $statusValue }}">{{ $statusLabel }}</span>
                                <button type="button" wire:click="closeLessonDetails" class="ts-details-close" title="Fechar detalhes"><x-filament::icon icon="fas-xmark" /></button>
                            </div>
                        </div>
                    <div class="ts-detail-list">
                        <div class="ts-detail-item"><x-filament::icon icon="fas-calendar-day" /><div><strong>Data</strong>{{ $selectedLesson->date?->translatedFormat('l, d/m/Y') ?? '-' }}</div></div>
                        <div class="ts-detail-item"><x-filament::icon icon="fas-clock" /><div><strong>Horário</strong>{{ $selectedLesson->start_time?->format('H:i') ?? '--:--' }} - {{ $selectedLesson->end_time?->format('H:i') ?? '--:--' }}</div></div>
                        <div class="ts-detail-item"><x-filament::icon icon="fas-users" /><div><strong>Turma</strong>{{ $selectedClass?->name ?? '-' }}</div></div>
                        <div class="ts-detail-item"><x-filament::icon icon="fas-book" /><div><strong>Disciplina</strong>{{ $selectedSubject?->name ?? '-' }}</div></div>
                        @if($room)<div class="ts-detail-item"><x-filament::icon icon="fas-location-dot" /><div><strong>Sala</strong>{{ $room }}</div></div>@endif
                        @if($shiftLabel || $schoolYear)<div class="ts-detail-item"><x-filament::icon icon="fas-layer-group" /><div><strong>Contexto</strong>{{ collect([$shiftLabel, $schoolYear?->name])->filter()->join(' · ') ?: '-' }}</div></div>@endif
                    </div>
                    <div class="ts-detail-block"><h4><x-filament::icon icon="fas-clipboard-list" /> Conteúdo previsto</h4><p>{!! $selectedPlannedContent ? nl2br(e($selectedPlannedContent)) : '<span class="ts-muted">Nenhum conteúdo previsto informado.</span>' !!}</p></div>
                    <div class="ts-detail-block"><h4><x-filament::icon icon="fas-book-open-reader" /> Ementa ou tópico relacionado</h4><p>{!! $selectedSyllabus ? nl2br(e($selectedSyllabus)) : '<span class="ts-muted">Nenhuma ementa ou tópico relacionado informado.</span>' !!}</p></div>
                    <div class="ts-detail-block"><h4><x-filament::icon icon="fas-bullseye" /> Objetivos</h4><p>{!! $selectedObjectives ? nl2br(e($selectedObjectives)) : '<span class="ts-muted">Nenhum objetivo informado.</span>' !!}</p></div>
                    @if($selectedLesson->homework)<div class="ts-detail-block"><h4><x-filament::icon icon="fas-house-laptop" /> Tarefa</h4><p>{!! nl2br(e($selectedLesson->homework)) !!}</p></div>@endif
                    @if($selectedLesson->observations)<div class="ts-detail-block"><h4><x-filament::icon icon="fas-note-sticky" /> Observações</h4><p>{!! nl2br(e($selectedLesson->observations)) !!}</p></div>@endif
                    </aside>
                </section>
            </div>
        @endif
    </div>
</x-filament-panels::page>
