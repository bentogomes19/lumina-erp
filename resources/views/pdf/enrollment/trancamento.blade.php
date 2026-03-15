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
                <div class="doc-title">COMPROVANTE DE TRANCAMENTO</div>
                <div class="doc-sub">Trancamento de Matrícula</div>
            </div>
        </div>
    </div>

    {{-- ── Número de matrícula ── --}}
    <div style="text-align:center; margin-bottom:16px;">
        <div style="font-size:8px; text-transform:uppercase; letter-spacing:0.1em; color:#94a3b8; margin-bottom:4px;">Número de Matrícula</div>
        <div class="reg-number">{{ $enrollment->registration_number }}</div>
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
        </div>
    </div>

    {{-- ── Dados do trancamento ── --}}
    <div class="op-box">
        <div class="op-box-title">Dados do Trancamento</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Motivo</label>
                <span>
                    @php
                        $motivos = \App\Enums\EnrollmentLockReason::options();
                        echo $motivos[$enrollment->locked_reason] ?? ($enrollment->locked_reason ?? '—');
                    @endphp
                </span>
            </div>
            <div class="info-item">
                <label>Data do Trancamento</label>
                <span>{{ $enrollment->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <label>Válido até</label>
                <span>{{ $enrollment->lock_expires_at?->format('d/m/Y') ?? 'Fim do ano letivo' }}</span>
            </div>
            <div class="info-item">
                <label>Registrado por</label>
                <span>{{ $enrollment->operatedBy?->name ?? $operator?->name ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Declaração ── --}}
    <div class="decl-box">
        Declaramos que a matrícula do(a) aluno(a) identificado(a) acima foi trancada
        a partir da data indicada, pelo motivo informado, permanecendo o(a) aluno(a)
        com vínculo ativo nesta instituição, podendo solicitar a reativação da matrícula
        dentro do prazo de vigência estabelecido.
        As notas e frequências já registradas no período são preservadas integralmente.
    </div>

    {{-- ── Aviso ── --}}
    <div style="background:#fef9c3; border:1px solid #fde68a; border-radius:6px; padding:8px 12px; margin-bottom:14px; font-size:8.5px; color:#854d0e;">
        <strong>Atenção:</strong> Durante o período de trancamento, o(a) aluno(a) não aparecerá
        nos lançamentos de notas e frequência das turmas. O acesso ao portal do aluno
        permanece bloqueado até a reativação da matrícula.
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
