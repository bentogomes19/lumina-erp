<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; background: #fff; }

        .page { padding: 24px 28px; }

        /* ── Header ── */
        .header { border-bottom: 2px solid #f59e0b; padding-bottom: 12px; margin-bottom: 16px; }
        .header-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .school-name { font-size: 15px; font-weight: bold; color: #1e293b; }
        .school-sub  { font-size: 9px; color: #64748b; margin-top: 2px; }
        .doc-title   { font-size: 13px; font-weight: bold; color: #f59e0b; text-align: right; }
        .doc-year    { font-size: 9px; color: #64748b; text-align: right; margin-top: 2px; }

        /* ── Student info ── */
        .student-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 12px; margin-bottom: 16px; }
        .info-grid { display: flex; gap: 24px; flex-wrap: wrap; }
        .info-item label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; display: block; }
        .info-item span  { font-size: 10px; font-weight: 600; color: #1e293b; }

        /* ── Summary ── */
        .summary { display: flex; gap: 10px; margin-bottom: 16px; }
        .summary-item { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center; }
        .summary-item .val { font-size: 18px; font-weight: 800; }
        .summary-item .lbl { font-size: 7.5px; text-transform: uppercase; color: #64748b; margin-top: 2px; }

        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th {
            background: #1e293b; color: #fff; font-size: 8px; text-transform: uppercase;
            letter-spacing: 0.05em; padding: 6px 8px; text-align: center;
        }
        thead th:first-child { text-align: left; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 6px 8px; font-size: 9.5px; border-bottom: 1px solid #e2e8f0; text-align: center; }
        tbody td:first-child { text-align: left; font-weight: 600; }

        /* ── Grade colors ── */
        .g-ok  { color: #16a34a; font-weight: 700; }
        .g-rec { color: #b45309; font-weight: 700; }
        .g-nok { color: #dc2626; font-weight: 700; }
        .g-nil { color: #94a3b8; }

        /* ── Status badge ── */
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 600; }
        .b-approved { background: #dcfce7; color: #166534; }
        .b-recovery { background: #fef9c3; color: #854d0e; }
        .b-failed   { background: #fee2e2; color: #991b1b; }
        .b-ongoing  { background: #f1f5f9; color: #475569; }

        /* ── Signatures & footer ── */
        .signatures { display: flex; gap: 60px; justify-content: center; margin-top: 36px; margin-bottom: 20px; }
        .sig-line .line  { border-top: 1px solid #334155; width: 150px; margin: 0 auto 4px; }
        .sig-line .label { font-size: 8px; color: #64748b; text-align: center; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 8px; display: flex; justify-content: space-between; font-size: 8px; color: #94a3b8; }
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
