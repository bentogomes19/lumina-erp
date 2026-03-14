<x-filament-panels::page>
<style>
    /* ── Tokens ── */
    :root {
        --ac-bg:          #ffffff;
        --ac-surface:     #f8fafc;
        --ac-border:      #e2e8f0;
        --ac-text:        #1e293b;
        --ac-muted:       #64748b;
        --ac-faint:       #94a3b8;
        --ac-hover:       #f1f5f9;
        --ac-today-ring:  #6366f1;
        --ac-weekend-bg:  #fafafa;
    }
    .dark {
        --ac-bg:          #080a0c;
        --ac-surface:     #0d1117;
        --ac-border:      #1e293b;
        --ac-text:        #f1f5f9;
        --ac-muted:       #94a3b8;
        --ac-faint:       #475569;
        --ac-hover:       #0f172a;
        --ac-today-ring:  #818cf8;
        --ac-weekend-bg:  #090c10;
    }

    /* ── Card ── */
    .ac-card {
        background: var(--ac-bg);
        border: 1px solid var(--ac-border);
        border-radius: 0.875rem;
        padding: 1.25rem;
    }

    /* ── Header nav button ── */
    .ac-nav-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.5rem;
        border: 1px solid var(--ac-border);
        background: var(--ac-bg);
        color: var(--ac-muted);
        cursor: pointer;
        transition: all 0.15s;
    }
    .ac-nav-btn:hover { background: var(--ac-hover); color: var(--ac-text); }

    .ac-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 500;
        border: 1.5px solid var(--ac-border);
        background: var(--ac-bg);
        color: var(--ac-muted);
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .ac-pill-btn:hover  { background: var(--ac-hover); }
    .ac-pill-btn.active { border-color: currentColor; }

    .ac-view-btn {
        padding: 0.35rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        color: var(--ac-muted);
        transition: all 0.15s;
        background: transparent;
        border: none;
    }
    .ac-view-btn.active {
        background: var(--ac-surface);
        color: var(--ac-text);
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }

    /* ── Month grid ── */
    .ac-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
    }
    .ac-dow {
        text-align: center;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--ac-faint);
        padding: 0.5rem 0 0.25rem;
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .ac-cell {
        background: var(--ac-bg);
        border: 1px solid var(--ac-border);
        border-radius: 0.5rem;
        min-height: 5.5rem;
        padding: 0.375rem;
        cursor: default;
        transition: background 0.1s;
        position: relative;
    }
    .ac-cell.has-events { cursor: pointer; }
    .ac-cell.has-events:hover { background: var(--ac-hover); }
    .ac-cell.is-empty {
        background: transparent;
        border-color: transparent;
    }
    .ac-cell.is-weekend {
        background: var(--ac-weekend-bg);
    }
    .ac-cell.is-today {
        outline: 2px solid var(--ac-today-ring);
        outline-offset: -2px;
    }
    .ac-cell-day {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--ac-muted);
        line-height: 1.5rem;
        width: 1.5rem;
        height: 1.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin-bottom: 0.25rem;
    }
    .ac-cell.is-today .ac-cell-day {
        background: var(--ac-today-ring);
        color: #fff;
    }
    .ac-cell.is-weekend .ac-cell-day { color: var(--ac-faint); }

    /* ── Event chips inside cells ── */
    .ac-event-chip {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        font-size: 0.68rem;
        font-weight: 500;
        line-height: 1.4;
        margin-bottom: 2px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    .ac-event-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── List view ── */
    .ac-list-day {
        display: flex;
        gap: 1rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--ac-border);
    }
    .ac-list-day:last-child { border-bottom: none; }
    .ac-list-date-col {
        width: 4.5rem;
        flex-shrink: 0;
        text-align: center;
    }
    .ac-list-date-num {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--ac-text);
        line-height: 1;
    }
    .ac-list-date-weekday {
        font-size: 0.7rem;
        color: var(--ac-faint);
        text-transform: uppercase;
        letter-spacing: .04em;
    }
    .ac-list-date-month {
        font-size: 0.7rem;
        color: var(--ac-muted);
    }
    .ac-list-event {
        flex: 1;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.625rem 0.875rem;
        border-radius: 0.625rem;
        margin-bottom: 0.375rem;
        cursor: pointer;
        transition: opacity 0.15s;
    }
    .ac-list-event:hover { opacity: 0.85; }
    .ac-list-event-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    /* Keep heroicons visually consistent even if external SVG styles leak in. */
    .ac-nav-btn svg,
    .ac-list-event-icon svg,
    .ac-modal-icon svg,
    .ac-modal-close svg,
    .ac-detail-row svg,
    .ac-upcoming-item svg,
    .ac-card > svg {
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
    }

    .ac-empty-state-icon {
        width: 2.25rem;
        height: 2.25rem;
    }

    .ac-icon-xs { width: 0.875rem !important; height: 0.875rem !important; }
    .ac-icon-sm { width: 1rem !important; height: 1rem !important; }
    .ac-icon-md { width: 1.25rem !important; height: 1.25rem !important; }
    .ac-icon-lg { width: 1.5rem !important; height: 1.5rem !important; }

    /* ── Upcoming sidebar card ── */
    .ac-upcoming-item {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--ac-border);
    }
    .ac-upcoming-item:last-child { border-bottom: none; }

    /* ── Modal overlay ── */
    .ac-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0,0,0,.5);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .ac-modal {
        background: var(--ac-bg);
        border: 1px solid var(--ac-border);
        border-radius: 1rem;
        max-width: 30rem;
        width: 100%;
        padding: 1.5rem;
        box-shadow: 0 20px 60px rgba(0,0,0,.25);
    }
    .ac-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    .ac-modal-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.875rem;
        flex-shrink: 0;
    }
    .ac-modal-close {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--ac-faint);
        background: transparent;
        border: none;
        transition: all 0.15s;
    }
    .ac-modal-close:hover { background: var(--ac-hover); color: var(--ac-text); }

    .ac-detail-row {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0;
        font-size: 0.875rem;
        color: var(--ac-muted);
        border-bottom: 1px solid var(--ac-border);
    }
    .ac-detail-row:last-child { border-bottom: none; }
    .ac-detail-label { color: var(--ac-faint); font-size: 0.75rem; min-width: 5rem; }

    /* ── Legend ── */
    .ac-legend-item {
        display: flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.8rem;
        color: var(--ac-muted);
    }
    .ac-legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── Responsive: mobile shows dots only ── */
    @media (max-width: 640px) {
        .ac-cell { min-height: 3.25rem; }
        .ac-event-chip-text { display: none; }
        .ac-event-chip { padding: 0.125rem 0.25rem; }
    }
</style>

@php
    $data        = $this->getPageData();
    $student     = $data['student'];
    $currentClass = $data['currentClass'];
    $schoolYear  = $data['schoolYear'];
    $monthStart  = $data['monthStart'];
    $grid        = $data['grid'];
    $events      = $data['events'];
    $listEvents  = $data['listEvents'];
    $upcoming    = $data['upcoming'];
    $subjects    = $data['subjects'];

    $monthLabel = ucfirst($monthStart->locale('pt_BR')->translatedFormat('F Y'));
    $today      = now()->format('Y-m-d');

    $categories = [
        ['key' => 'assessment',  'label' => 'Avaliações',       'color' => '#3b82f6'],
        ['key' => 'holiday',     'label' => 'Feriados',          'color' => '#ef4444'],
        ['key' => 'recess',      'label' => 'Recesso',           'color' => '#8b5cf6'],
        ['key' => 'school_event','label' => 'Eventos Escolares', 'color' => '#10b981'],
        ['key' => 'period',      'label' => 'Período Letivo',    'color' => '#f97316'],
    ];

    $daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
@endphp

{{-- ═══════════════════════════════════════════════════════════
     ALPINE WRAPPER — event modal state lives here
     ═══════════════════════════════════════════════════════════ --}}
<div
    x-data="{
        showModal: false,
        selectedEvent: null,
        allEvents: @js($events->toArray()),
        openEvent(eventId) {
            this.selectedEvent = this.allEvents.find(e => e.id === eventId) || null;
            if (this.selectedEvent) this.showModal = true;
        },
        closeModal() { this.showModal = false; this.selectedEvent = null; }
    }"
    @keydown.escape.window="closeModal()"
>

{{-- ── No-student state ─────────────────────────────────────── --}}
@if (! $student || ! $currentClass)
    <div class="ac-card text-center py-16">
        <x-heroicon-o-calendar class="ac-empty-state-icon mx-auto mb-4" style="color: var(--ac-faint)" />
        <p class="font-semibold text-lg" style="color: var(--ac-text)">Nenhuma turma ativa encontrada</p>
        <p class="mt-1 text-sm" style="color: var(--ac-muted)">O calendário acadêmico ficará disponível após a matrícula.</p>
    </div>

@else

{{-- ══════════════════════════════════════════════════════════════
     SCHOOL YEAR BANNER
     ══════════════════════════════════════════════════════════════ --}}
@if ($schoolYear)
<div class="ac-card mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3">
        <div class="flex items-center justify-center w-10 h-10 rounded-xl" style="background:#eff6ff">
            <x-heroicon-o-academic-cap class="ac-icon-md" style="color:#3b82f6" />
        </div>
        <div>
            <p class="text-xs font-medium" style="color: var(--ac-faint)">Ano Letivo</p>
            <p class="font-bold text-lg" style="color: var(--ac-text)">{{ $schoolYear->year }}</p>
        </div>
    </div>
    <div class="flex gap-6 text-sm">
        <div>
            <p class="text-xs" style="color: var(--ac-faint)">Início</p>
            <p class="font-semibold" style="color: var(--ac-text)">{{ $schoolYear->starts_at?->format('d/m/Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs" style="color: var(--ac-faint)">Término</p>
            <p class="font-semibold" style="color: var(--ac-text)">{{ $schoolYear->ends_at?->format('d/m/Y') ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs" style="color: var(--ac-faint)">Turma</p>
            <p class="font-semibold" style="color: var(--ac-text)">{{ $currentClass->name ?? '—' }}</p>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     MAIN LAYOUT: calendar + sidebar
     ══════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 xl:grid-cols-4 gap-4">

    {{-- ═══════════════════════════════════════
         LEFT COLUMN: calendar
         ═══════════════════════════════════════ --}}
    <div class="xl:col-span-3 space-y-4">

        {{-- ── Calendar card ─────────────────── --}}
        <div class="ac-card">

            {{-- Header: nav + view toggle --}}
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">

                {{-- Month navigation --}}
                <div class="flex items-center gap-2">
                    <button wire:click="previousMonth" class="ac-nav-btn" aria-label="Mês anterior">
                        <x-heroicon-s-chevron-left class="ac-icon-sm" />
                    </button>

                    <h2 class="text-base font-bold min-w-[9rem] text-center" style="color: var(--ac-text)">
                        {{ $monthLabel }}
                    </h2>

                    <button wire:click="nextMonth" class="ac-nav-btn" aria-label="Próximo mês">
                        <x-heroicon-s-chevron-right class="ac-icon-sm" />
                    </button>

                    @if($monthStart->format('Y-m') !== now()->format('Y-m'))
                        <button wire:click="goToToday"
                            class="ac-nav-btn text-xs font-semibold px-3 w-auto"
                            style="color:#6366f1; border-color:#6366f1"
                            aria-label="Ir para hoje">
                            Hoje
                        </button>
                    @endif
                </div>

                {{-- View mode toggle --}}
                <div class="flex items-center gap-1 p-1 rounded-lg" style="background: var(--ac-surface); border: 1px solid var(--ac-border)">
                    <button wire:click="$set('viewMode','month')"
                        class="ac-view-btn {{ $this->viewMode === 'month' ? 'active' : '' }}">
                        <span class="hidden sm:inline">Mensal</span>
                        <span class="sm:hidden">Mês</span>
                    </button>
                    <button wire:click="$set('viewMode','list')"
                        class="ac-view-btn {{ $this->viewMode === 'list' ? 'active' : '' }}">
                        <span class="hidden sm:inline">Agenda</span>
                        <span class="sm:hidden">Lista</span>
                    </button>
                </div>
            </div>

            {{-- Category filter pills --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($categories as $cat)
                    @php $active = in_array($cat['key'], $this->activeCategories); @endphp
                    <button
                        wire:click="toggleCategory('{{ $cat['key'] }}')"
                        class="ac-pill-btn {{ $active ? 'active' : '' }}"
                        style="{{ $active ? 'color:' . $cat['color'] . '; border-color:' . $cat['color'] : '' }}"
                        aria-pressed="{{ $active ? 'true' : 'false' }}"
                    >
                        <span class="ac-legend-dot" style="background: {{ $cat['color'] }}"></span>
                        {{ $cat['label'] }}
                    </button>
                @endforeach

                {{-- Subject filter --}}
                @if($subjects->isNotEmpty())
                    <select
                        wire:model.live="filterSubjectId"
                        class="ac-pill-btn"
                        style="cursor: pointer"
                        aria-label="Filtrar por disciplina"
                    >
                        <option value="">Todas as disciplinas</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- ════════════════════════════════
                 MONTH VIEW
                 ════════════════════════════════ --}}
            @if($this->viewMode === 'month')

                {{-- Day-of-week headers --}}
                <div class="ac-grid mb-0.5">
                    @foreach($daysOfWeek as $i => $dow)
                        <div class="ac-dow {{ in_array($i, [0,6]) ? 'opacity-50' : '' }}">
                            {{ $dow }}
                        </div>
                    @endforeach
                </div>

                {{-- Calendar cells --}}
                <div class="ac-grid">
                    @foreach($grid as $cell)
                        @if($cell['type'] === 'empty')
                            <div class="ac-cell is-empty" aria-hidden="true"></div>
                        @else
                            @php
                                $hasEvents  = ! empty($cell['events']);
                                $isToday    = $cell['is_today'];
                                $isWeekend  = $cell['is_weekend'];
                                $cellEvents = array_slice($cell['events'], 0, 3);
                                $moreCount  = max(0, count($cell['events']) - 3);
                            @endphp
                            <div
                                class="ac-cell
                                    {{ $hasEvents ? 'has-events' : '' }}
                                    {{ $isToday   ? 'is-today'   : '' }}
                                    {{ $isWeekend ? 'is-weekend' : '' }}"
                                @if($hasEvents)
                                    @click="openEvent('{{ $cell['events'][0]['id'] }}')"
                                @endif
                                role="{{ $hasEvents ? 'button' : '' }}"
                                aria-label="{{ $isToday ? 'Hoje, ' : '' }}{{ $cell['day'] }} — {{ count($cell['events']) }} evento(s)"
                            >
                                <div class="ac-cell-day">{{ $cell['day'] }}</div>

                                @foreach($cellEvents as $ev)
                                    <div
                                        class="ac-event-chip"
                                        style="background: {{ $ev['bg_color'] }}; color: {{ $ev['text_color'] }}"
                                        @click.stop="openEvent('{{ $ev['id'] }}')"
                                        title="{{ $ev['title'] }}"
                                    >
                                        <span class="ac-event-dot" style="background: {{ $ev['dot_color'] }}"></span>
                                        <span class="ac-event-chip-text truncate">{{ $ev['title'] }}</span>
                                    </div>
                                @endforeach

                                @if($moreCount > 0)
                                    <div class="text-center mt-0.5">
                                        <span class="text-xs font-medium" style="color: var(--ac-faint)">+{{ $moreCount }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>

            {{-- ════════════════════════════════
                 AGENDA / LIST VIEW
                 ════════════════════════════════ --}}
            @else
                @if($listEvents->isEmpty())
                    <div class="py-12 text-center">
                        <x-heroicon-o-calendar-days class="ac-icon-lg mx-auto mb-3" style="color: var(--ac-faint)" />
                        <p class="text-sm" style="color: var(--ac-muted)">Nenhum evento neste mês.</p>
                    </div>
                @else
                    @php
                        $grouped = $listEvents->groupBy('date');
                    @endphp
                    <div class="divide-y" style="border-color: var(--ac-border)">
                        @foreach($grouped as $date => $dayEvents)
                            @php
                                $carbon     = \Carbon\Carbon::parse($date);
                                $isToday    = $date === $today;
                                $weekdayPt  = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][$carbon->dayOfWeek];
                            @endphp
                            <div class="ac-list-day">
                                {{-- Date column --}}
                                <div class="ac-list-date-col pt-1">
                                    <div class="ac-list-date-num
                                        {{ $isToday ? 'text-indigo-500' : '' }}">
                                        {{ $carbon->format('d') }}
                                    </div>
                                    <div class="ac-list-date-weekday">{{ $weekdayPt }}</div>
                                    <div class="ac-list-date-month">{{ ucfirst($carbon->locale('pt_BR')->translatedFormat('M')) }}</div>
                                    @if($isToday)
                                        <span class="inline-block mt-1 text-xs font-bold text-indigo-500">Hoje</span>
                                    @endif
                                </div>

                                {{-- Events column --}}
                                <div class="flex-1 space-y-2">
                                    @foreach($dayEvents as $ev)
                                        <div
                                            class="ac-list-event"
                                            style="background: {{ $ev['bg_color'] }}"
                                            @click="openEvent('{{ $ev['id'] }}')"
                                            role="button"
                                            tabindex="0"
                                            @keydown.enter="openEvent('{{ $ev['id'] }}')"
                                        >
                                            <div class="ac-list-event-icon" style="background: {{ $ev['dot_color'] }}22">
                                                @switch($ev['icon'])
                                                    @case('pencil-square')
                                                        <x-heroicon-o-pencil-square class="ac-icon-sm" style="color: {{ $ev['dot_color'] }}" />
                                                        @break
                                                    @case('flag')
                                                        <x-heroicon-o-flag class="ac-icon-sm" style="color: {{ $ev['dot_color'] }}" />
                                                        @break
                                                    @case('sun')
                                                        <x-heroicon-o-sun class="ac-icon-sm" style="color: {{ $ev['dot_color'] }}" />
                                                        @break
                                                    @case('star')
                                                        <x-heroicon-o-star class="ac-icon-sm" style="color: {{ $ev['dot_color'] }}" />
                                                        @break
                                                    @case('academic-cap')
                                                        <x-heroicon-o-academic-cap class="ac-icon-sm" style="color: {{ $ev['dot_color'] }}" />
                                                        @break
                                                    @default
                                                        <x-heroicon-o-calendar class="ac-icon-sm" style="color: {{ $ev['dot_color'] }}" />
                                                @endswitch
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-semibold text-sm leading-tight truncate"
                                                    style="color: {{ $ev['text_color'] }}">
                                                    {{ $ev['title'] }}
                                                </p>
                                                <p class="text-xs mt-0.5 truncate"
                                                    style="color: {{ $ev['dot_color'] }}bb">
                                                    {{ $ev['category_label'] }}
                                                    @if($ev['subject']) · {{ $ev['subject'] }} @endif
                                                    @if($ev['time']) · {{ $ev['time'] }} @endif
                                                </p>
                                            </div>
                                            <x-heroicon-o-chevron-right class="ac-icon-sm flex-shrink-0"
                                                style="color: {{ $ev['dot_color'] }}88" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

        </div>{{-- /ac-card --}}

        {{-- ── Legend ──────────────────────────────────────── --}}
        <div class="ac-card">
            <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--ac-faint)">
                Legenda
            </p>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                @foreach($categories as $cat)
                    <div class="ac-legend-item">
                        <span class="ac-legend-dot" style="background: {{ $cat['color'] }}"
                            role="img" aria-label="{{ $cat['label'] }}"></span>
                        <span>{{ $cat['label'] }}</span>
                    </div>
                @endforeach
                <div class="ac-legend-item">
                    <span class="w-4 h-4 rounded border-2 flex-shrink-0"
                        style="border-color: var(--ac-today-ring)"
                        role="img" aria-label="Hoje"></span>
                    <span>Hoje</span>
                </div>
            </div>
        </div>

    </div>{{-- /xl:col-span-3 --}}

    {{-- ═══════════════════════════════════════
         RIGHT COLUMN: sidebar
         ═══════════════════════════════════════ --}}
    <div class="xl:col-span-1 space-y-4">

        {{-- Upcoming events --}}
        <div class="ac-card">
            <div class="flex items-center gap-2 mb-3">
                <x-heroicon-o-bell-alert class="ac-icon-sm" style="color:#f59e0b" />
                <p class="text-sm font-semibold" style="color: var(--ac-text)">Próximos 14 dias</p>
            </div>

            @if($upcoming->isEmpty())
                <p class="text-xs py-4 text-center" style="color: var(--ac-faint)">
                    Sem eventos próximos.
                </p>
            @else
                @foreach($upcoming as $ev)
                    @php
                        $du = $ev['days_until'];
                        $label = match(true) {
                            $du === 0 => 'Hoje',
                            $du === 1 => 'Amanhã',
                            default   => "Em {$du} dias",
                        };
                        $urgent = $du <= 2;
                    @endphp
                    <div class="ac-upcoming-item">
                        <span class="w-2 h-2 rounded-full flex-shrink-0"
                            style="background: {{ $ev['dot_color'] }}"
                            role="img" aria-label="{{ $ev['category_label'] }}"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold truncate" style="color: var(--ac-text)">
                                {{ $ev['title'] }}
                            </p>
                            @if($ev['subject'])
                                <p class="text-xs truncate" style="color: var(--ac-faint)">{{ $ev['subject'] }}</p>
                            @endif
                        </div>
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full flex-shrink-0
                            {{ $urgent ? 'text-red-600' : 'text-gray-500 dark:text-gray-400' }}"
                            style="{{ $urgent ? 'background:#fef2f2' : 'background: var(--ac-surface)' }}">
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Quick bimester summary --}}
        @if($schoolYear && $schoolYear->starts_at && $schoolYear->ends_at)
            @php
                $start     = $schoolYear->starts_at;
                $total     = (int) $start->diffInDays($schoolYear->ends_at);
                $quarter   = (int) ($total / 4);
                $bimesters = [
                    ['label' => '1º Bimestre', 'start' => $start->copy(), 'end' => $start->copy()->addDays($quarter)],
                    ['label' => '2º Bimestre', 'start' => $start->copy()->addDays($quarter + 1), 'end' => $start->copy()->addDays($quarter * 2)],
                    ['label' => '3º Bimestre', 'start' => $start->copy()->addDays($quarter * 2 + 1), 'end' => $start->copy()->addDays($quarter * 3)],
                    ['label' => '4º Bimestre', 'start' => $start->copy()->addDays($quarter * 3 + 1), 'end' => $schoolYear->ends_at->copy()],
                ];
            @endphp
            <div class="ac-card">
                <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color: var(--ac-faint)">
                    Bimestres {{ $schoolYear->year }}
                </p>
                <div class="space-y-2">
                    @foreach($bimesters as $bi)
                        @php
                            $isPast    = now()->gt($bi['end']);
                            $isCurrent = now()->between($bi['start'], $bi['end']);
                        @endphp
                        <div class="flex items-center justify-between py-1.5 px-2.5 rounded-lg text-sm
                            {{ $isCurrent ? 'font-semibold' : '' }}"
                            style="{{ $isCurrent ? 'background: var(--ac-surface); border: 1px solid var(--ac-border)' : '' }}">
                            <span style="color: {{ $isCurrent ? '#6366f1' : ($isPast ? 'var(--ac-faint)' : 'var(--ac-muted)') }}">
                                {{ $bi['label'] }}
                                @if($isCurrent)
                                    <span class="ml-1 text-xs font-normal"
                                        style="color:#6366f1; background:#eef2ff; padding: 1px 6px; border-radius: 999px">
                                        atual
                                    </span>
                                @endif
                            </span>
                            <span class="text-xs" style="color: var(--ac-faint)">
                                {{ $bi['start']->format('d/m') }} — {{ $bi['end']->format('d/m') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>{{-- /sidebar --}}

</div>{{-- /grid --}}

@endif {{-- end has student --}}

{{-- ══════════════════════════════════════════════════════════════
     EVENT DETAIL MODAL
     ══════════════════════════════════════════════════════════════ --}}
<div
    x-show="showModal"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="ac-modal-overlay"
    @click.self="closeModal()"
    role="dialog"
    aria-modal="true"
    aria-label="Detalhes do evento"
    style="display: none"
>
    <div
        x-show="showModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="ac-modal"
    >
        <template x-if="selectedEvent">
            <div>
                {{-- Modal header --}}
                <div class="ac-modal-header">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="ac-modal-icon"
                            :style="'background:' + (selectedEvent.bg_color || '#f1f5f9')">
                            <template x-if="selectedEvent.icon === 'pencil-square'">
                                <x-heroicon-o-pencil-square class="ac-icon-md" />
                            </template>
                            <template x-if="selectedEvent.icon === 'flag'">
                                <x-heroicon-o-flag class="ac-icon-md" />
                            </template>
                            <template x-if="selectedEvent.icon === 'sun'">
                                <x-heroicon-o-sun class="ac-icon-md" />
                            </template>
                            <template x-if="selectedEvent.icon === 'star'">
                                <x-heroicon-o-star class="ac-icon-md" />
                            </template>
                            <template x-if="selectedEvent.icon === 'academic-cap'">
                                <x-heroicon-o-academic-cap class="ac-icon-md" />
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-base leading-tight" style="color: var(--ac-text)"
                                x-text="selectedEvent.title"></p>
                            <p class="text-xs mt-0.5 font-medium"
                                :style="'color:' + selectedEvent.dot_color"
                                x-text="selectedEvent.category_label"></p>
                        </div>
                    </div>
                    <button @click="closeModal()" class="ac-modal-close" aria-label="Fechar">
                        <x-heroicon-o-x-mark class="ac-icon-sm" />
                    </button>
                </div>

                {{-- Details --}}
                <div class="space-y-0 rounded-lg overflow-hidden" style="border: 1px solid var(--ac-border)">

                    {{-- Description --}}
                    <div class="ac-detail-row px-4" x-show="selectedEvent.description">
                        <x-heroicon-o-information-circle class="ac-icon-sm flex-shrink-0" style="color: var(--ac-faint)" />
                        <span class="ac-detail-label">Descrição</span>
                        <span x-text="selectedEvent.description" style="color: var(--ac-text)"></span>
                    </div>

                    {{-- Date --}}
                    <div class="ac-detail-row px-4">
                        <x-heroicon-o-calendar class="ac-icon-sm flex-shrink-0" style="color: var(--ac-faint)" />
                        <span class="ac-detail-label">Data</span>
                        <span style="color: var(--ac-text)" x-text="
                            selectedEvent.date_end && selectedEvent.date_end !== selectedEvent.date
                                ? selectedEvent.date + ' a ' + selectedEvent.date_end
                                : selectedEvent.date
                        "></span>
                    </div>

                    {{-- Time --}}
                    <div class="ac-detail-row px-4" x-show="selectedEvent.time">
                        <x-heroicon-o-clock class="ac-icon-sm flex-shrink-0" style="color: var(--ac-faint)" />
                        <span class="ac-detail-label">Horário</span>
                        <span x-text="selectedEvent.time" style="color: var(--ac-text)"></span>
                    </div>

                    {{-- Subject --}}
                    <div class="ac-detail-row px-4" x-show="selectedEvent.subject">
                        <x-heroicon-o-book-open class="ac-icon-sm flex-shrink-0" style="color: var(--ac-faint)" />
                        <span class="ac-detail-label">Disciplina</span>
                        <span x-text="selectedEvent.subject" style="color: var(--ac-text)"></span>
                    </div>

                    {{-- Weight --}}
                    <div class="ac-detail-row px-4" x-show="selectedEvent.weight">
                        <x-heroicon-o-scale class="ac-icon-sm flex-shrink-0" style="color: var(--ac-faint)" />
                        <span class="ac-detail-label">Peso</span>
                        <span x-text="selectedEvent.weight" style="color: var(--ac-text)"></span>
                    </div>

                </div>

                {{-- Close button --}}
                <div class="mt-4">
                    <button
                        @click="closeModal()"
                        class="w-full py-2.5 rounded-xl text-sm font-semibold transition-all"
                        style="background: var(--ac-surface); border: 1px solid var(--ac-border); color: var(--ac-muted)"
                    >
                        Fechar
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

</div>{{-- /x-data --}}
</x-filament-panels::page>
