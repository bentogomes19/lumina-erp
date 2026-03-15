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
                <div class="doc-title">TERMO DE CANCELAMENTO</div>
                <div class="doc-sub">Cancelamento de Matrícula</div>
            </div>
        </div>
    </div>

    {{-- ── Número de matrícula ── --}}
    <div style="text-align:center; margin-bottom:16px;">
        <div style="font-size:8px; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:4px;">Matrícula Cancelada</div>
        <div class="reg-number" style="color:#dc2626;">{{ $enrollment->registration_number }}</div>
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
            <div class="info-item">
                <label>Turma</label>
                <span>{{ $enrollment->class?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Série / Etapa</label>
                <span>{{ $enrollment->class?->gradeLevel?->name ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Ano Letivo</label>
                <span>{{ $enrollment->schoolYear?->year ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Dados do cancelamento ── --}}
    <div class="danger-box">
        <div class="danger-box-title">Dados do Cancelamento</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Data do Cancelamento</label>
                <span>{{ $enrollment->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <label>Registrado por</label>
                <span>{{ $enrollment->operatedBy?->name ?? $operator?->name ?? '—' }}</span>
            </div>
            <div class="info-item info-item-wide">
                <label>Motivo</label>
                <span>{{ $enrollment->cancel_reason ?? '—' }}</span>
            </div>
            @if($enrollment->cancel_observations)
            <div class="info-item info-item-wide">
                <label>Observações Adicionais</label>
                <span>{{ $enrollment->cancel_observations }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- ── Declaração ── --}}
    <div class="decl-box">
        Por meio deste termo, declaramos que a matrícula do(a) aluno(a) identificado(a)
        acima foi formalmente cancelada nesta instituição de ensino, a partir da data
        indicada, pelos motivos registrados.
        O histórico acadêmico completo permanece arquivado e disponível para consulta
        somente pela equipe administrativa.
    </div>

    {{-- ── Aviso de caráter irreversível ── --}}
    <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:8px 12px; margin-bottom:14px; font-size:8.5px; color:#dc2626;">
        <strong>Importante:</strong> Este cancelamento é de caráter definitivo.
        A reversão somente poderá ser realizada pelo perfil TI mediante justificativa
        formalmente registrada no sistema. O acesso do aluno ao portal foi bloqueado
        imediatamente após o cancelamento.
    </div>

    {{-- ── Assinaturas ── --}}
    <div class="signatures">
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Secretaria Escolar</div>
        </div>
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Responsável pelo Aluno / Ciente</div>
        </div>
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Direção</div>
        </div>
    </div>

    {{-- ── Rodapé ── --}}
    <div class="seal">
        Documento gerado eletronicamente pelo Sistema de Gestão Escolar. Guarde uma cópia deste documento.<br>
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
