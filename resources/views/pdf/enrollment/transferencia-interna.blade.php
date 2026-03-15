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
                <div class="doc-title">COMPROVANTE DE TRANSFERÊNCIA</div>
                <div class="doc-sub">Transferência entre Turmas — Ano Letivo {{ $enrollment->schoolYear?->year ?? now()->year }}</div>
            </div>
        </div>
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
                <label>Matrícula</label>
                <span>{{ $enrollment->registration_number }}</span>
            </div>
            <div class="info-item">
                <label>CPF</label>
                <span>{{ $enrollment->student?->cpf ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Turma de origem ── --}}
    <div class="op-box">
        <div class="op-box-title">Dados da Transferência</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Turma de Origem</label>
                <span>{{ $enrollment->class?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Série / Etapa (Origem)</label>
                <span>{{ $enrollment->class?->gradeLevel?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Turno (Origem)</label>
                <span>{{ $enrollment->class?->shift?->label() ?? '—' }}</span>
            </div>

            @if($novaMatricula)
            <div class="info-item" style="margin-top:8px;">
                <label>Turma de Destino</label>
                <span>{{ $novaMatricula->class?->name ?? '—' }}</span>
            </div>
            <div class="info-item" style="margin-top:8px;">
                <label>Série / Etapa (Destino)</label>
                <span>{{ $novaMatricula->class?->gradeLevel?->name ?? '—' }}</span>
            </div>
            <div class="info-item" style="margin-top:8px;">
                <label>Turno (Destino)</label>
                <span>{{ $novaMatricula->class?->shift?->label() ?? '—' }}</span>
            </div>
            @endif

            <div class="info-item info-item-wide" style="margin-top:8px;">
                <label>Motivo da Transferência</label>
                <span>{{ $enrollment->transfer_reason ?? '—' }}</span>
            </div>

            @if($novaMatricula)
            <div class="info-item">
                <label>Novo Número de Matrícula</label>
                <span class="reg-number" style="font-size:12px;">{{ $novaMatricula->registration_number }}</span>
            </div>
            @endif

            <div class="info-item">
                <label>Data da Transferência</label>
                <span>{{ $enrollment->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    {{-- ── Informação sobre notas/frequência ── --}}
    <div class="decl-box">
        As notas e frequências já registradas para o(a) aluno(a) no período atual foram mantidas
        e migradas para o novo vínculo de turma. O histórico completo permanece disponível
        para consulta pela secretaria.
    </div>

    {{-- ── Declaração ── --}}
    <div class="decl-box">
        Declaramos que o(a) aluno(a) identificado(a) acima foi transferido(a) internamente
        conforme dados registrados no Sistema de Gestão Escolar.
        A matrícula original ({{ $enrollment->registration_number }}) foi encerrada e
        uma nova matrícula {{ $novaMatricula ? "({$novaMatricula->registration_number})" : '' }} foi gerada na turma de destino.
    </div>

    {{-- ── Assinaturas ── --}}
    <div class="signatures">
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Secretaria Escolar</div>
        </div>
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Coordenação Pedagógica</div>
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
        <span>Matrícula origem: {{ $enrollment->registration_number }}</span>
        <span>{{ config('app.name') }}</span>
    </div>

</div>
</body>
</html>
