<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Boletim escolar — {{ $student->name }}</title>
    <style>
        {!! file_get_contents(resource_path('css/pdf/report-card.css')) !!}
    </style>
</head>
<body>
@php
    $schoolYear      = $currentClass->schoolYear?->year ?? $generatedAt->year;
    $selectedPeriod  = $selectedPeriod ?? 'all';
    $termLabels      = ['b1' => '1º bimestre', 'b2' => '2º bimestre', 'b3' => '3º bimestre', 'b4' => '4º bimestre'];
    $periodLabel     = $termLabels[$selectedPeriod] ?? 'Todos os bimestres';
    $formatGrade     = fn ($value) => $value === null ? '—' : number_format($value, 1, ',', '');
    $statusLabels    = ['approved' => 'Aprovado', 'recovery' => 'Recuperação', 'failed' => 'Reprovado', 'ongoing' => 'Cursando'];
    $orderedSubjects = collect($subjects)->sortBy(fn ($item) => mb_strtolower($item['subject']?->name ?? ''));
@endphp

<footer class="page-footer">
    <table>
        <tr>
            <td>{{ config('app.name', 'Lumina ERP') }} · Registro de desempenho escolar</td>
            <td class="text-right">Emitido em {{ $generatedAt->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
</footer>

<header class="institution">
    <div class="school-name">{{ config('app.name', 'Lumina ERP') }}</div>
    <div class="school-department">Secretaria escolar · Acompanhamento pedagógico</div>
</header>

<table class="document-heading">
    <tr>
        <td><h1>Boletim escolar</h1><div class="document-subtitle">Registro individual de rendimento</div></td>
        <td class="year-block"><span class="field-label">Ano letivo</span><strong>{{ $schoolYear }}</strong></td>
    </tr>
</table>

<table class="student-record">
    <tr>
        <td colspan="3" class="student-name"><span class="field-label">Aluno(a)</span>{{ $student->name }}</td>
        <td><span class="field-label">Matrícula</span>{{ $student->registration_number ?? '—' }}</td>
    </tr>
    <tr>
        <td><span class="field-label">Série / Ano</span>{{ $currentClass->gradeLevel?->name ?? '—' }}</td>
        <td><span class="field-label">Turma</span>{{ $currentClass->name }}</td>
        <td><span class="field-label">Turno</span>{{ $currentClass->shift?->label() ?? '—' }}</td>
        <td><span class="field-label">Período consultado</span>{{ $periodLabel }}</td>
    </tr>
</table>

<div class="section-heading">Rendimento por componente curricular</div>
<table class="grades">
    <thead>
        <tr>
            <th rowspan="2" class="subject-heading" style="width: 26%">Componente curricular</th>
            @foreach($termLabels as $label)
                <th colspan="2" style="width: 12%">{{ $label }}</th>
            @endforeach
            <th rowspan="2" style="width: 10%">Média<br>apurada</th>
            <th rowspan="2" style="width: 16%">Situação*</th>
        </tr>
        <tr>
            @foreach($termLabels as $label)
                <th>Média</th><th>Rec.</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($orderedSubjects as $item)
            <tr>
                <th scope="row" class="subject-name">{{ $item['subject']?->name ?? '—' }}</th>
                @foreach($termLabels as $key => $label)
                    <td>{{ $formatGrade($item['terms'][$key]['final_average'] ?? null) }}</td>
                    <td class="recovery-grade">{{ $formatGrade(data_get($item, "terms.$key.recovery.score")) }}</td>
                @endforeach
                <td class="average">{{ $formatGrade($item['overall_average']) }}</td>
                <td class="status">{{ $statusLabels[$item['status']] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="11" class="empty-state">Não há notas lançadas para o período consultado.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="table-notes">
    <p><strong>Legenda:</strong> Média = média bimestral, considerando a recuperação quando houver; Rec. = nota de recuperação; — = sem lançamento ou fora do período consultado.</p>
    <p><strong>Critério de referência:</strong> média para aprovação {{ $formatGrade(\App\Services\GradeCalculationService::MIN_APPROVAL) }} · Escala de notas: 0 a {{ $formatGrade(\App\Services\GradeCalculationService::MAX_SCORE) }}.</p>
    <p>* A média apurada e a situação consideram as notas disponíveis no período consultado. Durante o ano letivo, os resultados são parciais e não substituem o fechamento escolar.</p>
</div>

<div class="closing-block">
    <div class="section-heading">Observações pedagógicas</div>
    <div class="observations">
        <div class="writing-line"></div>
        <div class="writing-line"></div>
        <div class="writing-line"></div>
    </div>
    <table class="signatures">
        <tr>
            <td><div class="signature-line"></div><strong>Secretaria / Coordenação pedagógica</strong><br>Assinatura e carimbo</td>
            <td class="signature-gap"></td>
            <td><div class="signature-line"></div><strong>Responsável pelo(a) aluno(a)</strong><br>Ciência em ______ / ______ / __________</td>
        </tr>
    </table>
</div>
</body>
</html>
