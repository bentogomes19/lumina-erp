<x-filament-panels::page>
    <style>
        /* ── CSS Variables ── */
        :root {
            --ac-card-bg:        #ffffff;
            --ac-card-border:    #e2e8f0;
            --ac-cell-bg:        #f8fafc;
            --ac-cell-border:    #e2e8f0;
            --ac-bar-bg:         #e2e8f0;
            --ac-text-primary:   #1e293b;
            --ac-text-secondary: #64748b;
            --ac-text-muted:     #94a3b8;
            --ac-hover-bg:       #f1f5f9;
            --ac-today-ring:     #f59e0b;
            --ac-weekend-bg:     #f8fafc;
        }
        .dark {
            --ac-card-bg:        #080a0c;
            --ac-card-border:    #334155;
            --ac-cell-bg:        #0c1019;
            --ac-cell-border:    #1e293b;
            --ac-bar-bg:         #10141d;
            --ac-text-primary:   #f1f5f9;
            --ac-text-secondary: #94a3b8;
            --ac-text-muted:     #64748b;
            --ac-hover-bg:       #0a0d11;
            --ac-today-ring:     #f59e0b;
            --ac-weekend-bg:     #0a0d11;
        }

        .ac-card {
            background: var(--ac-card-bg);
            border: 1px solid var(--ac-card-border);
            border-radius: 0.75rem;
        }

        /* ── View mode tabs ── */
        .ac-tab {
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            color: var(--ac-text-secondary);
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            gap: 0.375rem;
            transition: all 0.15s;
        }
        .ac-tab:hover   { background: var(--ac-hover-bg); color: var(--ac-text-primary); }
        .ac-tab.active  { background: #f59e0b; color: #fff; }

        /* ── Navigation button ── */
        .ac-nav-btn {
            width: 2rem; height: 2rem;
            border-radius: 0.5rem;
            border: 1px solid var(--ac-card-border);
            background: var(--ac-card-bg);
            color: var(--ac-text-secondary);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
        }
        .ac-nav-btn:hover { background: var(--ac-hover-bg); color: var(--ac-text-primary); }

        /* ── Category filter pill ── */
        .ac-filter-pill {
            display: flex; align-items: center; gap: 0.375rem;
            padding: 0.3125rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            border: 1.5px solid var(--ac-card-border);
            background: var(--ac-card-bg);
            color: var(--ac-text-secondary);
            transition: all 0.15s;
        }
        .ac-filter-pill:hover { background: var(--ac-hover-bg); }
        .ac-filter-pill.active { border-color: currentColor; }

        /* ── Month grid ── */
        .ac-month-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        .ac-dow-header {
            text-align: center;
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--ac-text-muted);
            padding: 0.375rem 0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .ac-cell {
            min-height: 5.5rem;
            background: var(--ac-card-bg);
            border: 1px solid var(--ac-cell-border);
            border-radius: 0.375rem;
            padding: 0.375rem;
            display: flex;
            flex-direction: column;
            gap: 2px;
            position: relative;
            transition: background 0.1s;
        }
        .ac-cell:hover { background: var(--ac-hover-bg); }
        .ac-cell.empty { background: var(--ac-cell-bg); opacity: 0.4; border-color: transparent; }
        .ac-cell.weekend { background: var(--ac-weekend-bg); }
        .ac-cell.today {
            outline: 2px solid var(--ac-today-ring);
            outline-offset: -2px;
            background: var(--ac-card-bg);
        }
        .ac-day-num {
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--ac-text-secondary);
            line-height: 1.4;
            width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .ac-cell.today .ac-day-num {
            background: #f59e0b;
            color: #fff;
        }
        .ac-event-chip {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 0.625rem;
            font-weight: 500;
            cursor: pointer;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            max-width: 100%;
            line-height: 1.6;
            transition: opacity 0.15s;
        }
        .ac-event-chip:hover { opacity: 0.85; }
        .ac-event-more {
            font-size: 0.5625rem;
            color: var(--ac-text-muted);
            padding: 1px 5px;
            cursor: pointer;
        }

        /* ── Week grid ── */
        .ac-week-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.5rem;
        }
        .ac-week-col {
            min-height: 14rem;
            background: var(--ac-card-bg);
            border: 1px solid var(--ac-cell-border);
            border-radius: 0.5rem;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .ac-week-col.today { outline: 2px solid var(--ac-today-ring); outline-offset: -2px; }
        .ac-week-col.weekend { background: var(--ac-weekend-bg); }
        .ac-week-day-header {
            text-align: center;
            padding-bottom: 0.375rem;
            border-bottom: 1px solid var(--ac-cell-border);
            margin-bottom: 0.25rem;
        }
        .ac-week-event {
            padding: 0.25rem 0.5rem;
            border-radius: 0.3rem;
            font-size: 0.6875rem;
            font-weight: 500;
            cursor: pointer;
            line-height: 1.5;
            transition: opacity 0.15s;
        }
        .ac-week-event:hover { opacity: 0.8; }

        /* ── List view ── */
        .ac-list-date-group { margin-bottom: 1.25rem; }
        .ac-list-date-label {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--ac-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .ac-list-event {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background 0.1s;
            margin-bottom: 0.375rem;
            border: 1px solid var(--ac-card-border);
            background: var(--ac-card-bg);
        }
        .ac-list-event:hover { background: var(--ac-hover-bg); }

        /* ── Event modal ── */
        .ac-modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .ac-modal {
            background: var(--ac-card-bg);
            border: 1px solid var(--ac-card-border);
            border-radius: 1rem;
            padding: 1.5rem;
            width: 100%;
            max-width: 28rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            position: relative;
        }

        /* ── Upcoming sidebar ── */
        .ac-upcoming-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--ac-cell-border);
        }
        .ac-upcoming-item:last-child { border-bottom: none; }

        /* ── Subject select ── */
        .ac-select {
            padding: 0.375rem 0.625rem;
            border-radius: 0.5rem;
            border: 1px solid var(--ac-card-border);
            background: var(--ac-card-bg);
            color: var(--ac-text-primary);
            font-size: 0.8125rem;
            cursor: pointer;
            outline: none;
        }
        .ac-select:focus { border-color: #f59e0b; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .ac-layout       { flex-direction: column !important; }
            .ac-sidebar      { width: 100% !important; }
            .ac-cell         { min-height: 3.5rem; }
            .ac-week-col     { min-height: 8rem; }
        }
        @media (max-width: 640px) {
            .ac-controls-row { flex-wrap: wrap; }
            .ac-month-grid   { gap: 1px; }
            .ac-cell         { min-height: 2.75rem; padding: 0.25rem; }
            .ac-day-num      { font-size: 0.6875rem; width: 1.25rem; height: 1.25rem; }
            .ac-event-chip   { display: none; }
            .ac-cell-dot     { display: flex !important; }
            .ac-week-grid    { grid-template-columns: 1fr; }
        }
    </style>

    @php
        $data         = $this->getPageData();
        $student      = $data['student'];
        $currentClass = $data['currentClass'];
        $schoolYear   = $data['schoolYear'];
        $monthStart   = $data['monthStart'];
        $grid         = $data['grid'];
        $weekGrid     = $data['weekGrid'];
        $listEvents   = $data['listEvents'];
        $upcoming     = $data['upcoming'];
        $subjects     = $data['subjects'];
        $today        = now()->format('Y-m-d');

        $monthName = ucfirst($monthStart->locale('pt_BR')->translatedFormat('F Y'));

        $weekStartCrb = $data['weekStart'];
        $weekEndCrb   = $weekStartCrb->copy()->addDays(6);
        $weekLabel    = $weekStartCrb->locale('pt_BR')->translatedFormat('d M') . ' – ' . $weekEndCrb->locale('pt_BR')->translatedFormat('d M Y');

        $dowNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

        $categories = [
            'assessment'  => ['label' => 'Avaliações',    'color' => '#3b82f6', 'icon' => 'heroicon-o-pencil-square'],
            'holiday'     => ['label' => 'Feriados',      'color' => '#ef4444', 'icon' => 'heroicon-o-flag'],
            'recess'      => ['label' => 'Recessos',      'color' => '#8b5cf6', 'icon' => 'heroicon-o-sun'],
            'school_event'=> ['label' => 'Eventos',       'color' => '#10b981', 'icon' => 'heroicon-o-star'],
            'period'      => ['label' => 'Período Letivo','color' => '#10b981', 'icon' => 'heroicon-o-academic-cap'],
        ];

        // Group list events by date
        $groupedList = [];
        foreach ($listEvents as $ev) {
            $groupedList[$ev['date']][] = $ev;
        }
        ksort($groupedList);
    @endphp

    {{-- ── No class state ── --}}
    @if(!$student || !$currentClass)
        <div class="ac-card" style="padding:3rem;text-align:center">
            <div style="width:4rem;height:4rem;border-radius:50%;background:rgba(245,158,11,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
                @svg('heroicon-o-calendar-days', '', ['style' => 'width:2rem;height:2rem;color:#f59e0b'])
            </div>
            <h3 style="font-size:1.125rem;font-weight:600;color:var(--ac-text-primary);margin:0 0 0.5rem">
                Nenhuma turma ativa encontrada
            </h3>
            <p style="font-size:0.875rem;color:var(--ac-text-secondary);margin:0">
                Você não está matriculado em nenhuma turma no ano letivo vigente.
            </p>
        </div>
    @else

    {{-- ── Main layout ── --}}
    <div
        x-data="{
            selectedEvent: null,
            openEvent(ev) { this.selectedEvent = ev; },
            closeEvent() { this.selectedEvent = null; }
        }"
        style="display:flex;flex-direction:column;gap:1.25rem"
    >

        {{-- ▸ Header bar --}}
        <div class="ac-card" style="padding:1rem 1.25rem">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem">
                {{-- Class info --}}
                <div style="display:flex;align-items:center;gap:0.875rem">
                    <div style="width:2.5rem;height:2.5rem;border-radius:0.625rem;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        @svg('heroicon-o-calendar-days', '', ['style' => 'width:1.35rem;height:1.35rem;color:#f59e0b'])
                    </div>
                    <div>
                        <h2 style="font-size:1rem;font-weight:700;color:var(--ac-text-primary);margin:0">
                            Calendário Acadêmico
                        </h2>
                        <p style="font-size:0.8125rem;color:var(--ac-text-secondary);margin:0.0625rem 0 0">
                            {{ $currentClass->name }}
                            @if($schoolYear)
                                &middot; Ano Letivo {{ $schoolYear->year }}
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Export buttons --}}
                <div style="display:flex;align-items:center;gap:0.5rem">
                    <button
                        wire:click="exportPdf"
                        title="Exportar PDF"
                        style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;border:1px solid var(--ac-card-border);background:var(--ac-card-bg);color:var(--ac-text-secondary);font-size:0.75rem;font-weight:500;cursor:pointer;transition:all 0.15s"
                        onmouseover="this.style.background='var(--ac-hover-bg)'" onmouseout="this.style.background='var(--ac-card-bg)'"
                    >
                        @svg('heroicon-o-document-arrow-down', '', ['style' => 'width:0.875rem;height:0.875rem'])
                        PDF
                    </button>
                    <button
                        wire:click="exportIcal"
                        title="Exportar iCal"
                        style="display:flex;align-items:center;gap:0.375rem;padding:0.375rem 0.75rem;border-radius:0.5rem;border:1px solid var(--ac-card-border);background:var(--ac-card-bg);color:var(--ac-text-secondary);font-size:0.75rem;font-weight:500;cursor:pointer;transition:all 0.15s"
                        onmouseover="this.style.background='var(--ac-hover-bg)'" onmouseout="this.style.background='var(--ac-card-bg)'"
                    >
                        @svg('heroicon-o-arrow-down-tray', '', ['style' => 'width:0.875rem;height:0.875rem'])
                        iCal
                    </button>
                </div>
            </div>
        </div>

        {{-- ▸ Controls row: view tabs + nav + subject filter --}}
        <div class="ac-card" style="padding:0.75rem 1.25rem">
            <div class="ac-controls-row" style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap">

                {{-- View mode tabs --}}
                <div style="display:flex;align-items:center;gap:0.25rem;background:var(--ac-cell-bg);border-radius:0.625rem;padding:0.25rem">
                    <button wire:click="setViewMode('month')" class="ac-tab {{ $this->viewMode === 'month' ? 'active' : '' }}">
                        @svg('heroicon-o-squares-2x2', '', ['style' => 'width:0.875rem;height:0.875rem'])
                        Mês
                    </button>
                    <button wire:click="setViewMode('week')" class="ac-tab {{ $this->viewMode === 'week' ? 'active' : '' }}">
                        @svg('heroicon-o-view-columns', '', ['style' => 'width:0.875rem;height:0.875rem'])
                        Semana
                    </button>
                    <button wire:click="setViewMode('list')" class="ac-tab {{ $this->viewMode === 'list' ? 'active' : '' }}">
                        @svg('heroicon-o-list-bullet', '', ['style' => 'width:0.875rem;height:0.875rem'])
                        Lista
                    </button>
                </div>

                {{-- Navigation --}}
                <div style="display:flex;align-items:center;gap:0.5rem">
                    @if($this->viewMode === 'week')
                        <button wire:click="previousWeek" class="ac-nav-btn" title="Semana anterior">
                            @svg('heroicon-o-chevron-left', '', ['style' => 'width:1rem;height:1rem'])
                        </button>
                        <span style="font-size:0.875rem;font-weight:600;color:var(--ac-text-primary);min-width:12rem;text-align:center">
                            {{ $weekLabel }}
                        </span>
                        <button wire:click="nextWeek" class="ac-nav-btn" title="Próxima semana">
                            @svg('heroicon-o-chevron-right', '', ['style' => 'width:1rem;height:1rem'])
                        </button>
                    @else
                        <button wire:click="previousMonth" class="ac-nav-btn" title="Mês anterior">
                            @svg('heroicon-o-chevron-left', '', ['style' => 'width:1rem;height:1rem'])
                        </button>
                        <span style="font-size:0.9375rem;font-weight:700;color:var(--ac-text-primary);min-width:10rem;text-align:center;text-transform:capitalize">
                            {{ $monthName }}
                        </span>
                        <button wire:click="nextMonth" class="ac-nav-btn" title="Próximo mês">
                            @svg('heroicon-o-chevron-right', '', ['style' => 'width:1rem;height:1rem'])
                        </button>
                    @endif
                    <button wire:click="goToToday" class="ac-nav-btn" style="width:auto;padding:0 0.75rem;font-size:0.75rem;font-weight:500" title="Ir para hoje">
                        Hoje
                    </button>
                </div>

                {{-- Subject filter --}}
                @if($subjects->isNotEmpty())
                    <select
                        wire:change="setSubjectFilter($event.target.value === '' ? null : parseInt($event.target.value))"
                        class="ac-select"
                    >
                        <option value="">Todas as disciplinas</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $this->filterSubjectId == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            {{-- Category filters --}}
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--ac-cell-border)">
                <span style="font-size:0.6875rem;font-weight:600;color:var(--ac-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-right:0.25rem">
                    Filtrar:
                </span>
                @foreach($categories as $key => $cat)
                    @php $isActive = in_array($key, $this->activeCategories); @endphp
                    <button
                        wire:click="toggleCategory('{{ $key }}')"
                        class="ac-filter-pill {{ $isActive ? 'active' : '' }}"
                        style="{{ $isActive ? 'color:'.$cat['color'].';border-color:'.$cat['color'].';background:'.($cat['color']).'18' : '' }}"
                    >
                        <span style="width:0.5rem;height:0.5rem;border-radius:50%;background:{{ $cat['color'] }};display:inline-block;flex-shrink:0"></span>
                        {{ $cat['label'] }}
                        @if(!$isActive)
                            <span style="font-size:0.6rem;opacity:0.5">(oculto)</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ▸ Main area + sidebar --}}
        <div class="ac-layout" style="display:flex;gap:1.25rem;align-items:flex-start">

            {{-- ── Calendar panel ── --}}
            <div style="flex:1;min-width:0">

                {{-- MONTH VIEW --}}
                @if($this->viewMode === 'month')
                    <div class="ac-card" style="padding:1rem;overflow:hidden">
                        {{-- Day-of-week headers --}}
                        <div class="ac-month-grid" style="margin-bottom:4px">
                            @foreach($dowNames as $dow)
                                <div class="ac-dow-header">{{ $dow }}</div>
                            @endforeach
                        </div>

                        {{-- Calendar cells --}}
                        <div class="ac-month-grid">
                            @foreach($grid as $cell)
                                @if($cell['type'] === 'empty')
                                    <div class="ac-cell empty"></div>
                                @else
                                    @php
                                        $cellClasses = 'ac-cell';
                                        if ($cell['is_today'])    $cellClasses .= ' today';
                                        if ($cell['is_weekend'])  $cellClasses .= ' weekend';
                                        $visibleEvents = array_slice($cell['events'], 0, 3);
                                        $hiddenCount   = count($cell['events']) - count($visibleEvents);
                                    @endphp
                                    <div class="{{ $cellClasses }}">
                                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:2px">
                                            <span class="ac-day-num">{{ $cell['day'] }}</span>
                                            {{-- Dot indicators for mobile (hidden by default, shown via CSS) --}}
                                            @if(count($cell['events']) > 0)
                                                <div class="ac-cell-dot" style="display:none;gap:1px;flex-wrap:wrap;max-width:1.5rem">
                                                    @foreach(array_slice($cell['events'], 0, 3) as $ev)
                                                        <span style="width:4px;height:4px;border-radius:50%;background:{{ $ev['dot_color'] }};display:inline-block"></span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        @foreach($visibleEvents as $ev)
                                            @php
                                                $isCont = $ev['is_continuation'] ?? false;
                                            @endphp
                                            <div
                                                class="ac-event-chip"
                                                style="background:{{ $ev['bg_color'] }};color:{{ $ev['text_color'] }};{{ $isCont ? 'opacity:0.7;' : '' }}"
                                                title="{{ $ev['title'] }}"
                                                x-on:click.stop="openEvent({{ json_encode($ev) }})"
                                            >
                                                <span style="width:5px;height:5px;border-radius:50%;background:{{ $ev['dot_color'] }};flex-shrink:0;display:inline-block"></span>
                                                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                                    {{ $isCont ? '↳ ' : '' }}{{ $ev['title'] }}
                                                </span>
                                            </div>
                                        @endforeach

                                        @if($hiddenCount > 0)
                                            <div class="ac-event-more">+{{ $hiddenCount }} mais</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                {{-- WEEK VIEW --}}
                @elseif($this->viewMode === 'week')
                    <div class="ac-card" style="padding:1rem;overflow:hidden">
                        <div class="ac-week-grid">
                            @foreach($weekGrid as $col)
                                @php
                                    $colClasses = 'ac-week-col';
                                    if ($col['is_today'])   $colClasses .= ' today';
                                    if ($col['is_weekend']) $colClasses .= ' weekend';
                                @endphp
                                <div class="{{ $colClasses }}">
                                    {{-- Day header --}}
                                    <div class="ac-week-day-header">
                                        <div style="font-size:0.625rem;font-weight:700;color:var(--ac-text-muted);text-transform:uppercase;letter-spacing:0.05em">
                                            {{ $dowNames[$col['dow']] }}
                                        </div>
                                        <div style="font-size:1.25rem;font-weight:{{ $col['is_today'] ? '800' : '600' }};color:{{ $col['is_today'] ? '#f59e0b' : 'var(--ac-text-primary)' }};line-height:1.2">
                                            {{ $col['day'] }}
                                        </div>
                                        <div style="font-size:0.6rem;color:var(--ac-text-muted)">
                                            {{ $col['month_name'] }}
                                        </div>
                                    </div>

                                    {{-- Events --}}
                                    @forelse($col['events'] as $ev)
                                        <div
                                            class="ac-week-event"
                                            style="background:{{ $ev['bg_color'] }};color:{{ $ev['text_color'] }}"
                                            x-on:click.stop="openEvent({{ json_encode($ev) }})"
                                        >
                                            @if($ev['time'])
                                                <div style="font-size:0.5625rem;opacity:0.7;margin-bottom:1px">{{ $ev['time'] }}</div>
                                            @endif
                                            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $ev['title'] }}</div>
                                        </div>
                                    @empty
                                        <div style="flex:1;display:flex;align-items:center;justify-content:center;opacity:0.25">
                                            @svg('heroicon-o-minus', '', ['style' => 'width:1.25rem;height:1.25rem;color:var(--ac-text-muted)'])
                                        </div>
                                    @endforelse
                                </div>
                            @endforeach
                        </div>
                    </div>

                {{-- LIST VIEW --}}
                @else
                    <div class="ac-card" style="padding:1.25rem">
                        @if(empty($groupedList))
                            <div style="text-align:center;padding:3rem 1rem;color:var(--ac-text-muted)">
                                @svg('heroicon-o-calendar', '', ['style' => 'width:2.5rem;height:2.5rem;margin:0 auto 0.75rem;display:block'])
                                <p style="margin:0;font-size:0.9375rem">Nenhum evento neste período</p>
                            </div>
                        @else
                            @foreach($groupedList as $dateStr => $evList)
                                @php
                                    $dateCrb   = \Carbon\Carbon::parse($dateStr);
                                    $isToday   = $dateStr === $today;
                                    $dateLabel = ucfirst($dateCrb->locale('pt_BR')->translatedFormat('l, d \d\e F'));
                                @endphp
                                <div class="ac-list-date-group">
                                    <div class="ac-list-date-label">
                                        <span style="display:inline-block;width:3px;height:0.875rem;border-radius:2px;background:{{ $isToday ? '#f59e0b' : 'var(--ac-text-muted)' }}"></span>
                                        {{ $dateLabel }}
                                        @if($isToday)
                                            <span style="font-size:0.625rem;font-weight:700;color:#fff;background:#f59e0b;padding:1px 6px;border-radius:999px">HOJE</span>
                                        @endif
                                    </div>
                                    @foreach($evList as $ev)
                                        <div
                                            class="ac-list-event"
                                            x-on:click="openEvent({{ json_encode($ev) }})"
                                        >
                                            {{-- Icon badge --}}
                                            <div style="width:2.25rem;height:2.25rem;border-radius:0.5rem;background:{{ $ev['bg_color'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0">
                                                <span style="width:0.625rem;height:0.625rem;border-radius:50%;background:{{ $ev['dot_color'] }};display:inline-block"></span>
                                            </div>
                                            <div style="flex:1;min-width:0">
                                                <div style="display:flex;align-items:baseline;justify-content:space-between;gap:0.5rem;flex-wrap:wrap">
                                                    <span style="font-size:0.9375rem;font-weight:600;color:var(--ac-text-primary)">
                                                        {{ $ev['title'] }}
                                                    </span>
                                                    @if($ev['time'])
                                                        <span style="font-size:0.75rem;color:var(--ac-text-muted);white-space:nowrap">
                                                            @svg('heroicon-o-clock', '', ['style' => 'width:0.75rem;height:0.75rem;display:inline'])
                                                            {{ $ev['time'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.25rem;flex-wrap:wrap">
                                                    <span style="font-size:0.6875rem;font-weight:500;padding:1px 6px;border-radius:999px;background:{{ $ev['bg_color'] }};color:{{ $ev['text_color'] }}">
                                                        {{ $ev['category_label'] }}
                                                    </span>
                                                    @if($ev['subject'])
                                                        <span style="font-size:0.6875rem;color:var(--ac-text-muted)">
                                                            {{ $ev['subject'] }}
                                                        </span>
                                                    @endif
                                                    @if($ev['impacts_grade'])
                                                        <span style="font-size:0.6rem;color:#3b82f6;display:flex;align-items:center;gap:2px">
                                                            @svg('heroicon-o-academic-cap', '', ['style' => 'width:0.625rem;height:0.625rem'])
                                                            Impacta nota
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                {{-- ▸ Legend --}}
                <div class="ac-card" style="padding:0.875rem 1.25rem;margin-top:1rem">
                    <div style="display:flex;align-items:center;gap:1.25rem;flex-wrap:wrap">
                        <span style="font-size:0.6875rem;font-weight:700;color:var(--ac-text-muted);text-transform:uppercase;letter-spacing:0.05em">
                            Legenda
                        </span>
                        @foreach($categories as $key => $cat)
                            <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:var(--ac-text-secondary)">
                                <span style="width:0.625rem;height:0.625rem;border-radius:50%;background:{{ $cat['color'] }};display:inline-block;flex-shrink:0"></span>
                                {{ $cat['label'] }}
                            </div>
                        @endforeach
                        <div style="display:flex;align-items:center;gap:0.375rem;font-size:0.75rem;color:var(--ac-text-secondary)">
                            <span style="width:0.875rem;height:0.875rem;border-radius:3px;outline:2px solid #f59e0b;display:inline-block;flex-shrink:0"></span>
                            Hoje
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Upcoming events sidebar ── --}}
            <div class="ac-sidebar ac-card" style="width:17rem;flex-shrink:0;padding:1.125rem">
                <h3 style="font-size:0.8125rem;font-weight:700;color:var(--ac-text-muted);text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.875rem;display:flex;align-items:center;gap:0.5rem">
                    @svg('heroicon-o-bell', '', ['style' => 'width:0.875rem;height:0.875rem'])
                    Próximos Eventos
                </h3>

                @forelse($upcoming as $ev)
                    @php
                        $daysUntil = $ev['days_until'];
                        $daysLabel = match(true) {
                            $daysUntil === 0 => 'Hoje',
                            $daysUntil === 1 => 'Amanhã',
                            default          => 'Em ' . $daysUntil . ' dias',
                        };
                        $urgencyColor = match(true) {
                            $daysUntil === 0 => '#ef4444',
                            $daysUntil <= 2  => '#f97316',
                            $daysUntil <= 5  => '#eab308',
                            default          => 'var(--ac-text-muted)',
                        };
                    @endphp
                    <div class="ac-upcoming-item">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $ev['dot_color'] }};flex-shrink:0;margin-top:5px"></span>
                        <div style="flex:1;min-width:0">
                            <p style="font-size:0.8125rem;font-weight:600;color:var(--ac-text-primary);margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                {{ $ev['title'] }}
                            </p>
                            @if($ev['subject'])
                                <p style="font-size:0.6875rem;color:var(--ac-text-muted);margin:0.125rem 0 0">
                                    {{ $ev['subject'] }}
                                </p>
                            @endif
                            <p style="font-size:0.6875rem;color:var(--ac-text-secondary);margin:0.25rem 0 0">
                                {{ ucfirst(\Carbon\Carbon::parse($ev['date'])->locale('pt_BR')->translatedFormat('d \d\e M')) }}
                                &middot;
                                <span style="font-weight:600;color:{{ $urgencyColor }}">{{ $daysLabel }}</span>
                            </p>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center;padding:1.5rem 0;color:var(--ac-text-muted)">
                        @svg('heroicon-o-check-circle', '', ['style' => 'width:2rem;height:2rem;margin:0 auto 0.5rem;display:block;color:#22c55e'])
                        <p style="font-size:0.8125rem;margin:0">Nenhum evento nos próximos 14 dias</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ── Event detail modal (Alpine.js) ── --}}
        <template x-if="selectedEvent !== null">
            <div
                class="ac-modal-backdrop"
                x-on:click.self="closeEvent()"
                x-on:keydown.escape.window="closeEvent()"
            >
                <div class="ac-modal" x-on:click.stop>
                    {{-- Header --}}
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;margin-bottom:1.125rem">
                        <div style="display:flex;align-items:flex-start;gap:0.875rem">
                            <div
                                style="width:2.5rem;height:2.5rem;border-radius:0.625rem;display:flex;align-items:center;justify-content:center;flex-shrink:0"
                                :style="`background:${selectedEvent.bg_color}`"
                            >
                                <span style="width:0.75rem;height:0.75rem;border-radius:50%;display:inline-block"
                                    :style="`background:${selectedEvent.dot_color}`"></span>
                            </div>
                            <div>
                                <h3 style="font-size:1rem;font-weight:700;color:var(--ac-text-primary);margin:0;line-height:1.3" x-text="selectedEvent.title"></h3>
                                <span
                                    style="font-size:0.6875rem;font-weight:500;padding:1px 7px;border-radius:999px;display:inline-block;margin-top:0.25rem"
                                    :style="`background:${selectedEvent.bg_color};color:${selectedEvent.text_color}`"
                                    x-text="selectedEvent.category_label"
                                ></span>
                            </div>
                        </div>
                        <button x-on:click="closeEvent()" style="width:1.75rem;height:1.75rem;border-radius:0.375rem;border:1px solid var(--ac-card-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--ac-text-muted)">
                            @svg('heroicon-o-x-mark', '', ['style' => 'width:1rem;height:1rem'])
                        </button>
                    </div>

                    {{-- Body --}}
                    <div style="display:flex;flex-direction:column;gap:0.625rem;font-size:0.8125rem">

                        {{-- Date --}}
                        <div style="display:flex;align-items:center;gap:0.625rem;color:var(--ac-text-secondary)">
                            @svg('heroicon-o-calendar', '', ['style' => 'width:1rem;height:1rem;flex-shrink:0;color:var(--ac-text-muted)'])
                            <span x-text="selectedEvent.date"></span>
                            <template x-if="selectedEvent.date_end && selectedEvent.date_end !== selectedEvent.date">
                                <span x-text="'→ ' + selectedEvent.date_end" style="color:var(--ac-text-muted)"></span>
                            </template>
                        </div>

                        {{-- Time --}}
                        <template x-if="selectedEvent.time">
                            <div style="display:flex;align-items:center;gap:0.625rem;color:var(--ac-text-secondary)">
                                @svg('heroicon-o-clock', '', ['style' => 'width:1rem;height:1rem;flex-shrink:0;color:var(--ac-text-muted)'])
                                <span x-text="selectedEvent.time"></span>
                            </div>
                        </template>

                        {{-- Subject --}}
                        <template x-if="selectedEvent.subject">
                            <div style="display:flex;align-items:center;gap:0.625rem;color:var(--ac-text-secondary)">
                                @svg('heroicon-o-book-open', '', ['style' => 'width:1rem;height:1rem;flex-shrink:0;color:var(--ac-text-muted)'])
                                <span x-text="selectedEvent.subject"></span>
                            </div>
                        </template>

                        {{-- Teacher --}}
                        <template x-if="selectedEvent.teacher">
                            <div style="display:flex;align-items:center;gap:0.625rem;color:var(--ac-text-secondary)">
                                @svg('heroicon-o-user', '', ['style' => 'width:1rem;height:1rem;flex-shrink:0;color:var(--ac-text-muted)'])
                                <span x-text="selectedEvent.teacher"></span>
                            </div>
                        </template>

                        {{-- Location --}}
                        <template x-if="selectedEvent.location">
                            <div style="display:flex;align-items:center;gap:0.625rem;color:var(--ac-text-secondary)">
                                @svg('heroicon-o-map-pin', '', ['style' => 'width:1rem;height:1rem;flex-shrink:0;color:var(--ac-text-muted)'])
                                <span x-text="selectedEvent.location"></span>
                            </div>
                        </template>

                        {{-- Weight (assessments) --}}
                        <template x-if="selectedEvent.weight !== null && selectedEvent.weight !== undefined">
                            <div style="display:flex;align-items:center;gap:0.625rem;color:var(--ac-text-secondary)">
                                @svg('heroicon-o-scale', '', ['style' => 'width:1rem;height:1rem;flex-shrink:0;color:var(--ac-text-muted)'])
                                <span>Peso: <strong x-text="selectedEvent.weight"></strong></span>
                            </div>
                        </template>

                        {{-- Description --}}
                        <template x-if="selectedEvent.description">
                            <div style="padding:0.75rem;background:var(--ac-cell-bg);border-radius:0.5rem;color:var(--ac-text-secondary);margin-top:0.25rem;border:1px solid var(--ac-cell-border)">
                                <span x-text="selectedEvent.description"></span>
                            </div>
                        </template>

                        {{-- Impact badges --}}
                        <div style="display:flex;align-items:center;gap:0.5rem;margin-top:0.25rem;flex-wrap:wrap">
                            <template x-if="selectedEvent.impacts_grade">
                                <span style="font-size:0.6875rem;font-weight:500;padding:3px 8px;border-radius:999px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;gap:4px">
                                    @svg('heroicon-o-academic-cap', '', ['style' => 'width:0.75rem;height:0.75rem'])
                                    Impacta nota
                                </span>
                            </template>
                            <template x-if="selectedEvent.impacts_freq">
                                <span style="font-size:0.6875rem;font-weight:500;padding:3px 8px;border-radius:999px;background:rgba(234,179,8,0.12);color:#ca8a04;display:flex;align-items:center;gap:4px">
                                    @svg('heroicon-o-user-group', '', ['style' => 'width:0.75rem;height:0.75rem'])
                                    Impacta frequência
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div style="margin-top:1.25rem;padding-top:0.875rem;border-top:1px solid var(--ac-cell-border);display:flex;justify-content:flex-end">
                        <button
                            x-on:click="closeEvent()"
                            style="padding:0.4375rem 1.125rem;border-radius:0.5rem;border:1px solid var(--ac-card-border);background:var(--ac-card-bg);color:var(--ac-text-secondary);font-size:0.8125rem;font-weight:500;cursor:pointer"
                        >
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </template>

    </div>
    @endif

</x-filament-panels::page>
