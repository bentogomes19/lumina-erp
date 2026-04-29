<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        {!! file_get_contents(resource_path('css/pdf/report-card.css')) !!}
    </style>
</head>
<body>
<div class="page">

    {{-- ── Header ── --}}
    <div class="header">
        <div class="header-top">
            <div>
                <div class="school-name">{{ config('app.name', 'Lumina ERP') }}</div>
                <div class="school-sub">Sistema de Gestão Escolar</div>
            </div>
            <div>
                <div class="doc-title">BOLETIM ESCOLAR</div>
                <div class="doc-year">Ano Letivo {{ $currentClass->schoolYear?->year ?? now()->year }}</div>
            </div>
        </div>
    </div>

    {{-- ── Student info ── --}}
    <div class="student-info">
        <div class="info-grid">
            <div class="info-item"><label>Nome do Aluno</label><span>{{ $student->name }}</span></div>
            <div class="info-item"><label>Matrícula</label><span>{{ $student->registration_number }}</span></div>
            <div class="info-item"><label>Turma</label><span>{{ $currentClass->name }}</span></div>
            @if($currentClass->gradeLevel)
                <div class="info-item"><label>Série</label><span>{{ $currentClass->gradeLevel->name }}</span></div>
            @endif
            <div class="info-item"><label>Emitido em</label><span>{{ $generatedAt->format('d/m/Y H:i') }}</span></div>
        </div>
    </div>

    {{-- ── Summary ── --}}
    @php
        $col        = collect($subjects);
        $approved   = $col->where('status', 'approved')->count();
        $recovery   = $col->where('status', 'recovery')->count();
        $failed     = $col->where('status', 'failed')->count();
        $avgAll     = $col->pluck('overall_average')->filter(fn($v) => $v !== null);
        $overallAvg = $avgAll->isNotEmpty() ? round($avgAll->avg(), 1) : null;
        $statusLabels = ['approved' => 'Aprovado', 'recovery' => 'Recuperação', 'failed' => 'Reprovado', 'ongoing' => 'Cursando'];
        $gradeClass = fn($v) => $v === null ? 'g-nil' : ($v >= 6 ? 'g-ok' : ($v >= 4 ? 'g-rec' : 'g-nok'));
    @endphp

    <div class="summary">
        <div class="summary-item"><div class="val" style="color:#1e293b">{{ $col->count() }}</div><div class="lbl">Disciplinas</div></div>
        <div class="summary-item"><div class="val g-ok">{{ $approved }}</div><div class="lbl">Aprovadas</div></div>
        <div class="summary-item"><div class="val g-rec">{{ $recovery }}</div><div class="lbl">Recuperação</div></div>
        <div class="summary-item"><div class="val g-nok">{{ $failed }}</div><div class="lbl">Reprovadas</div></div>
        @if($overallAvg !== null)
            <div class="summary-item">
                <div class="val {{ $gradeClass($overallAvg) }}">{{ number_format($overallAvg, 1, ',', '') }}</div>
                <div class="lbl">Média Geral</div>
            </div>
        @endif
    </div>

    {{-- ── Grades table ── --}}
    <table>
        <thead>
            <tr>
                <th style="width:30%">Disciplina</th>
                <th>1º Bim</th>
                <th>2º Bim</th>
                <th>3º Bim</th>
                <th>4º Bim</th>
                <th>Média Final</th>
                <th>Situação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subjects as $item)
                @php
                    $s      = $item['subject'];
                    $terms  = $item['terms'];
                    $over   = $item['overall_average'];
                    $status = $item['status'];
                @endphp
                <tr>
                    <td>{{ $s?->name ?? '—' }}</td>
                    @foreach(['b1','b2','b3','b4'] as $tk)
                        @php $fa = $terms[$tk]['final_average'] ?? null; @endphp
                        <td class="{{ $gradeClass($fa) }}">{{ $fa !== null ? number_format($fa, 1, ',', '') : '—' }}</td>
                    @endforeach
                    <td class="{{ $gradeClass($over) }}">{{ $over !== null ? number_format($over, 1, ',', '') : '—' }}</td>
                    <td><span class="badge b-{{ $status }}">{{ $statusLabels[$status] ?? $status }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Signatures ── --}}
    <div class="signatures">
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Coordenação Pedagógica</div>
        </div>
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Responsável pelo Aluno</div>
        </div>
    </div>

    {{-- ── Footer ── --}}
    <div class="footer">
        <span>Gerado em {{ $generatedAt->format('d/m/Y \à\s H:i') }}</span>
        <span>{{ config('app.name', 'Lumina ERP') }} — Documento de uso exclusivo da instituição</span>
    </div>

</div>
</body>
</html>
