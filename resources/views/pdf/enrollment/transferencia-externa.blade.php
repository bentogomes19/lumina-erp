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
                <div class="doc-title">DECLARAÇÃO DE TRANSFERÊNCIA</div>
                <div class="doc-sub">Transferência para Outra Instituição</div>
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
                <label>CPF</label>
                <span>{{ $enrollment->student?->cpf ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Data de Nascimento</label>
                <span>{{ $enrollment->student?->birth_date?->format('d/m/Y') ?? '—' }}</span>
            </div>
            <div class="info-item">
                <label>Número de Matrícula</label>
                <span>{{ $enrollment->registration_number }}</span>
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

    {{-- ── Dados da transferência ── --}}
    <div class="op-box">
        <div class="op-box-title">Dados da Transferência</div>
        <div class="info-grid">
            <div class="info-item">
                <label>Instituição de Destino</label>
                <span>{{ $enrollment->transfer_destination ?? 'Não informado' }}</span>
            </div>
            <div class="info-item">
                <label>Data da Transferência</label>
                <span>{{ $enrollment->updated_at?->format('d/m/Y') ?? now()->format('d/m/Y') }}</span>
            </div>
            <div class="info-item info-item-wide">
                <label>Motivo</label>
                <span>{{ $enrollment->transfer_reason ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── Histórico de notas ── --}}
    @if($historicoNotas->isNotEmpty())
    <div>
        <div class="doc-list-title" style="margin-bottom:6px;">Histórico de Desempenho — Ano Letivo {{ $enrollment->schoolYear?->year ?? now()->year }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width:45%; text-align:left;">Disciplina</th>
                    <th>Média</th>
                </tr>
            </thead>
            <tbody>
                @foreach($historicoNotas as $item)
                    <tr>
                        <td>{{ $item['subject'] }}</td>
                        <td>{{ $item['media'] !== null ? number_format($item['media'], 1, ',', '') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- ── Declaração formal ── --}}
    <div class="decl-box">
        Declaramos, para os devidos fins, que o(a) aluno(a) identificado(a) acima esteve
        regularmente matriculado(a) nesta instituição de ensino e se encontra em dia com
        suas obrigações acadêmicas até a data de transferência indicada.
        O histórico acadêmico completo está preservado e disponível para consulta mediante
        solicitação formal da instituição de destino.
    </div>

    {{-- ── Assinaturas ── --}}
    <div class="signatures">
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Secretaria Escolar</div>
        </div>
        <div class="sig-line">
            <div class="line"></div>
            <div class="label">Direção</div>
        </div>
    </div>

    {{-- ── Rodapé ── --}}
    <div class="seal">
        Documento gerado eletronicamente pelo Sistema de Gestão Escolar. Válido com assinatura e carimbo da instituição.<br>
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
