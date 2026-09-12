@php
    $periods = \App\Filament\Pages\Student\MyGrades::PERIODS;
    unset($periods['all']);
    if ($selectedPeriod !== 'all') {
        $periods = array_intersect_key($periods, [$selectedPeriod => true]);
    }
    $format = fn ($value) => $value === null ? '—' : number_format($value, 1, ',', '');
@endphp
<div class="sg-details">
    <p class="sg-detail-intro">A média considera o peso de cada avaliação e a recuperação, quando registrada. Nota ainda não lançada aparece como —.</p>
    @foreach($periods as $key => $label)
        @php $term = $item['terms'][$key]; @endphp
        <x-filament::section :collapsible="$selectedPeriod === 'all'" :collapsed="$selectedPeriod === 'all'">
            <x-slot name="heading">{{ $label }} · Média {{ $format($term['final_average']) }}</x-slot>
            @if($term['grades']->isEmpty() && $term['recovery'] === null)
                <p>Nenhuma avaliação lançada neste bimestre.</p>
            @else
                <div class="sg-assessments-wrap">
                    <table class="sg-assessments">
                        <caption class="sr-only">Avaliações de {{ $item['subject_name'] }} — {{ $label }}</caption>
                        <thead><tr><th scope="col">Avaliação</th><th scope="col">Data</th><th scope="col">Peso</th><th scope="col">Nota</th></tr></thead>
                        <tbody>
                            @foreach($term['grades'] as $grade)
                                <tr>
                                    <th scope="row">{{ $grade->assessment?->title ?? $grade->assessment_type?->label() ?? 'Avaliação' }}
                                        @if(!$grade->assessment && $grade->sequence > 1) {{ $grade->sequence }} @endif
                                        @if($grade->comment)<small>{{ $grade->comment }}</small>@endif
                                    </th>
                                    <td>{{ $grade->date_recorded?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $format($grade->weight ?? 1) }}</td>
                                    <td><strong>{{ $format($grade->score) }}</strong> / {{ $format($grade->max_score ?? 10) }}</td>
                                </tr>
                            @endforeach
                            @if($term['recovery'])
                                <tr>
                                    <th scope="row">Recuperação</th>
                                    <td>{{ $term['recovery']->date_recorded?->format('d/m/Y') ?? '—' }}</td>
                                    <td>—</td>
                                    <td><strong>{{ $format($term['recovery']->score) }}</strong> / {{ $format($term['recovery']->max_score ?? 10) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                <p class="sg-detail-average">Média antes da recuperação: {{ $format($term['average']) }} · <strong>Média apurada: {{ $format($term['final_average']) }}</strong></p>
            @endif
        </x-filament::section>
    @endforeach
</div>
