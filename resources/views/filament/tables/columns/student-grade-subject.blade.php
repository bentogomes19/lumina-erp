@php
    $record = $getRecord();
    $average = $record['overall_average'];
@endphp
<div class="sg-subject-cell">
    <strong>{{ $record['subject_name'] }}</strong>
    <span class="sg-mobile-progress">{{ \App\Filament\Pages\Student\MyGrades::progressLabel($average) }}</span>
    @if($this->selectedPeriod === 'all')
        <dl class="sg-mobile-terms">
            @foreach(['b1', 'b2', 'b3', 'b4'] as $term)
                @php $value = $record['terms'][$term]['final_average'] ?? null; @endphp
                <div><dt>{{ substr($term, 1) }}º bim.</dt><dd>{{ $value === null ? '—' : number_format($value, 1, ',', '') }}</dd></div>
            @endforeach
        </dl>
    @endif
</div>
