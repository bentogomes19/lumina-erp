@php
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\View\ComponentAttributeBag as FilamentComponentAttributeBag;
    use Filament\Widgets\View\Components\ChartWidgetComponent;
    use Illuminate\Contracts\Support\Htmlable;

    $heading = $this->getHeading();
    $description = $this->getDescription();
    $type = $this->getType();
    $maxHeight = $this->getMaxHeight();
    $isEmpty = $this->isEmpty();
    $chartAccessibleLabel = trim(implode('. ', array_filter([
        $heading instanceof Htmlable ? strip_tags($heading->toHtml()) : $heading,
        $description instanceof Htmlable ? strip_tags($description->toHtml()) : $description,
    ], fn ($value): bool => filled($value))));
@endphp

<x-filament-widgets::widget class="fi-wi-chart">
    <x-filament::section
        :description="$description"
        :heading="$heading"
        :collapsible="$this->isCollapsible()"
        :collapsed="true"
    >
        <div
            @if ($pollingInterval = $this->getPollingInterval())
                wire:poll.{{ $pollingInterval }}="updateChartData"
            @endif
            @if ($isEmpty)
                style="display: none"
            @endif
        >
            <div
                x-load
                x-load-src="{{ FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                wire:ignore
                data-chart-type="{{ $type }}"
                x-data="chart({
                    cachedData: @js($this->getCachedData()),
                    options: @js($this->getOptions()),
                    type: @js($type),
                })"
                {{ (new FilamentComponentAttributeBag)
                    ->color(ChartWidgetComponent::class, $this->getColor())
                    ->class(['fi-wi-chart-frame', 'fi-wi-chart-canvas-ctn', 'fi-wi-chart-frame-no-aspect-ratio']) }}
            >
                <canvas
                    x-ref="canvas"
                    role="img"
                    aria-label="{{ $chartAccessibleLabel }}"
                    style="width: 100%; height: 100%; max-height: {{ e($maxHeight) }}"
                ></canvas>
                <span aria-hidden="true" x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span aria-hidden="true" x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span aria-hidden="true" x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span aria-hidden="true" x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
                <span aria-hidden="true" x-ref="tooltipBackgroundColorElement" class="fi-wi-chart-tooltip-bg-color"></span>
                <span aria-hidden="true" x-ref="tooltipTextColorElement" class="fi-wi-chart-tooltip-text-color"></span>
                <span aria-hidden="true" x-ref="tooltipBorderColorElement" class="fi-wi-chart-tooltip-border-color"></span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
