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
    $documentLogo = $systemBranding->documentLogoDataUri();
    $schoolYear      = $currentClass->schoolYear?->year ?? $generatedAt->year;
    $termLabels      = ['b1' => '1º bimestre', 'b2' => '2º bimestre', 'b3' => '3º bimestre', 'b4' => '4º bimestre'];
    $formatGrade     = fn ($value) => $value === null ? '—' : number_format($value, 1, ',', '');
    $statusLabels    = ['approved' => 'Aprovado', 'recovery' => 'Recuperação', 'failed' => 'Reprovado', 'ongoing' => 'Cursando'];
    $orderedSubjects = $subjects;
@endphp

<footer class="page-footer">
    <table>
        <tr>
            <td>{{ $systemBranding->institutionName() }} · Registro de desempenho escolar</td>
            <td class="text-right">Emitido em {{ $generatedAt->format('d/m/Y H:i') }}</td>
        </tr>
    </table>
</footer>

<header class="institution">
    @if($documentLogo)
        <img src="{{ $documentLogo }}" alt="{{ $systemBranding->institutionName() }}" style="height:32px; max-width:150px; object-fit:contain; margin-bottom:6px;">
    @endif
    <div class="school-name">{{ $systemBranding->institutionName() }}</div>
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
        <td><span class="field-label">Período consultado</span>Ano letivo completo</td>
    </tr>
</table>

<div class="section-heading">Rendimento por componente curricular</div>
<table class="grades">
    <thead>
        <tr>
            <th class="subject-heading" style="width: 27%">Componente curricular</th>
            @foreach($termLabels as $label)
                <th style="width: 9%">{{ $label }}</th>
            @endforeach
            <th style="width: 10%">Média<br>final*</th>
            <th style="width: 15%">Recuperação</th>
            <th style="width: 12%">Situação*</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orderedSubjects as $item)
            <tr>
                <th scope="row" class="subject-name">{{ $item['subject']?->name ?? '—' }}</th>
                @foreach($termLabels as $key => $label)
                    @php
                        $termAverage = $item['terms'][$key]['final_average'] ?? null;
                    @endphp
                    <td class="{{ $termAverage === null ? '' : ($termAverage >= $minimumGrade ? 'grade-passing' : 'grade-attention') }}">{{ $formatGrade($termAverage) }}</td>
                @endforeach
                <td class="average {{ $item['overall_average'] === null ? '' : ($item['overall_average'] >= $minimumGrade ? 'grade-passing' : 'grade-attention') }}">{{ $formatGrade($item['overall_average']) }}</td>
                <td class="recovery-grade">{{ \App\Filament\Pages\Student\MyGrades::recoverySummary($item) }}</td>
                <td class="status {{ $item['overall_average'] === null ? '' : ($item['overall_average'] >= $minimumGrade ? 'grade-passing' : 'grade-attention') }}">{{ $statusLabels[$item['status']] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty-state">Não há disciplinas registradas para o ano letivo.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="table-notes">
    <p><strong>Legenda:</strong> Cada bimestre mostra a média apurada, considerando a recuperação quando houver. Recuperação mostra o bimestre e a nota obtida. — = sem lançamento.</p>
    <p><strong>Critério de referência:</strong> média para aprovação {{ $formatGrade($minimumGrade) }} · Escala de notas: 0 a {{ $formatGrade(\App\Services\GradeCalculationService::MAX_SCORE) }}.</p>
    <p>* A média final e a situação consideram as notas disponíveis. Antes do fechamento dos quatro bimestres, os resultados são parciais e não substituem o fechamento escolar.</p>
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
