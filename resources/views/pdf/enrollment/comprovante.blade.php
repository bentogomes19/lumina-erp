<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    @include('pdf.enrollment.partials.styles')
</head>
<body>
<div class="page">

    {{-- ── Cabeçalho ── --}}
    <div class="header">
        <div class="header-top">
            <div>
                <div class="school-name">{{ config('app.name', 'Lumina ERP') }}</div>
                <div class="school-sub">Sistema de Gestão Escolar</div>
            </div>
            <div>
                <div class="doc-title">{{ $isRematricula ? 'COMPROVANTE DE REMATRÍCULA' : 'COMPROVANTE DE MATRÍCULA' }}</div>
                <div class="doc-sub">Ano Letivo {{ $enrollment->schoolYear?->year ?? now()->year }}</div>
            </div>
        </div>
    </div>

    {{-- ── Número de matrícula ── --}}
    <div style="text-align:center; margin-bottom:16px;">
        <div style="font-size:8px; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:4px;">Número de Matrícula</div>
        <div class="reg-number">{{ $enrollment->registration_number }}</div>
        @if($isRematricula && $enrollment->previousEnrollment)
            <div style="font-size:8px; color:#64748b; margin-top:4px;">
                Renovação da matrícula {{ $enrollment->previousEnrollment->registration_number }}
            </div>
        @endif
    </div>

    {{-- ── Dados do aluno ── --}}
    <div class="info-box">
        <div class="info-box-title">Dados do Aluno</div>
        <div class="info-grid">
            <div class="info-item" style="flex:2">
                <label>Nome Completo</label>
                <span>{{ $enrollment->student?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>CPF</label>
                <span>{{ $enrollment->student?->cpf ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Data de Nascimento</label>
                <span>{{ $enrollment->student?->birth_date?->format('d/m/Y') ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Dados acadêmicos ── --}}
    <div class="info-box">
        <div class="info-box-title">Dados Acadêmicos</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Turma</label>
                <span>{{ $enrollment->class?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Série / Etapa</label>
                <span>{{ $enrollment->class?->gradeLevel?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Turno</label>
                <span>{{ $enrollment->class?->shift?->label() ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Ano Letivo</label>
                <span>{{ $enrollment->schoolYear?->year ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Nº de Chamada</label>
                <span>{{ $enrollment->roll_number ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Data da Matrícula</label>
                <span>{{ $enrollment->enrollment_date?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Status</label>
                <span>{{ $enrollment->status?->label() ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Checklist de documentos ── --}}
    @if($enrollment->documents->isNotEmpty())
    <div class="doc-list">
        <div class="doc-list-title">Checklist de Documentos</div>
        @foreach($enrollment->documents as $doc)
            <div class="doc-item">
                <span>{{ \App\Models\EnrollmentDocument::TIPOS[$doc->tipo] ?? $doc->tipo }}</span>
                <span class="badge b-{{ $doc->status }}">
                    {{ \App\Models\EnrollmentDocument::STATUS_OPTIONS[$doc->status] ?? $doc->status }}
                </span>
            </div>
        @endforeach
        @php $pendentes = $enrollment->documents->where('status', 'pendente')->count(); @endphp
        @if($pendentes > 0)
            <div style="margin-top:6px; font-size:8.5px; color:#b45309;">
                ⚠ {{ $pendentes }} documento(s) pendente(s). A matrícula está ativa, mas os documentos devem ser regularizados.
            </div>
        @endif
    </div>
    @endif

    {{-- ── Declaração ── --}}
    <div class="decl-box">
        Declaramos, para os devidos fins, que o(a) aluno(a) identificado(a) acima está
        {{ $isRematricula ? 'regularmente rematriculado(a)' : 'regularmente matriculado(a)' }}
        nesta instituição de ensino para o ano letivo indicado, conforme dados registrados no
        Sistema de Gestão Escolar.
    </div>

    {{-- ── Assinaturas ── --}}
    <div class="signatures">
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Secretaria Escolar</div>
        </div>
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Responsável pelo Aluno</div>
        </div>
    </div>

    {{-- ── Rodapé ── --}}
    <div class="seal">
        Documento gerado eletronicamente pelo Sistema de Gestão Escolar.<br>
        Operador: <strong>{{ $operator?->name ?? 'Sistema' }}</strong>
    </div>
    <div class="footer">
        <span>Emitido em: {{ $generatedAt->format('d/m/Y \à\s H:i:s') }}</span>
        <span>Matrícula: {{ $enrollment->registration_number }}</span>
        <span>{{ config('app.name') }}</span>
    </div>

</div>
</body>
</html>
