<x-filament-widgets::widget>
    <x-filament::section
        :heading="$this->getWidgetHeading()"
        :collapsible="true"
        :persist-collapsed="true"
    >
        {{ $this->table }}
    </x-filament::section>
</x-filament-widgets::widget>
