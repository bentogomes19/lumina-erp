<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 48px 56px 40px 56px; }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #111;
            line-height: 1.45;
        }

        /* Cabeçalho institucional */
        .header { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .header .logo { width: 90px; }
        .header .logo img { max-width: 80px; max-height: 70px; }
        .header .institution { text-align: center; font-size: 12px; line-height: 1.5; }
        .header .institution .name { font-size: 13px; }
        .header .spacer { width: 90px; }

        .title { text-align: center; margin: 26px 0 0; font-weight: bold; font-size: 12.5px; line-height: 1.5; }

        .intro { margin-top: 40px; text-align: justify; }

        /* Dados em linhas "rótulo: valor" */
        .data { width: 100%; border-collapse: collapse; margin-top: 34px; }
        .data td { padding: 4px 0; vertical-align: top; }
        .data .label { font-weight: bold; white-space: nowrap; padding-right: 10px; }

        .section-title {
            margin-top: 26px;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #111;
            padding-bottom: 2px;
        }

        .docs { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10.5px; }
        .docs td { padding: 3px 0; border-bottom: 1px dotted #999; }
        .docs td.status { text-align: right; width: 120px; }
        .pending-note { margin-top: 6px; font-size: 10px; }

        .paragraph { margin-top: 22px; text-align: justify; }

        .place-date { margin-top: 34px; text-align: right; }

        .signatures { width: 100%; border-collapse: collapse; margin-top: 50px; }
        .signatures td { width: 50%; text-align: center; padding: 0 20px; font-size: 10.5px; }
        .signatures .line { border-top: 1px solid #111; padding-top: 4px; }

        .control {
            margin-top: 34px;
            border: 1.5px solid #111;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 0.04em;
        }
        .control-label { text-align: center; font-size: 9px; margin-top: 3px; color: #333; }

        .footer { margin-top: 26px; text-align: center; font-size: 10.5px; line-height: 1.6; }
        .footer .strong { font-weight: bold; margin-top: 8px; }
    </style>
</head>
<body>
@php
    $documentLogo = $systemBranding->documentLogoDataUri();
    $institution  = $systemBranding->institutionName();
    $systemName   = $systemBranding->systemName();
    $schoolYear   = $enrollment->schoolYear?->year ?? $enrollment->class?->schoolYear?->year ?? now()->year;
    $docTitle     = $isRematricula ? 'Comprovante de Rematrícula' : 'Comprovante de Matrícula';
    $registeredAt = $enrollment->created_at ?? $generatedAt;
    $student      = $enrollment->student;
@endphp

{{-- Cabeçalho institucional --}}
<table class="header">
    <tr>
        <td class="logo">
            @if($documentLogo)
                <img src="{{ $documentLogo }}" alt="{{ $institution }}">
            @endif
        </td>
        <td class="institution">
            <div class="name">{{ $institution }}</div>
            <div>Secretaria Escolar</div>
            <div>{{ $systemName }}</div>
        </td>
        <td class="spacer"></td>
    </tr>
</table>

<div class="title">
    {{ $docTitle }} - Ano Letivo: {{ $schoolYear }}<br>
    {{ $systemName }}
</div>

{{-- Registro --}}
<div class="intro">
    O {{ $systemName }} registrou em {{ $registeredAt->format('d/m/Y') }} às {{ $registeredAt->format('H:i:s') }}
    a {{ $isRematricula ? 'rematrícula' : 'matrícula' }} do(a) aluno(a) abaixo identificado(a), contendo as
    informações necessárias ao cadastro escolar desta instituição, sob a responsabilidade de:
</div>

{{-- Dados do aluno e da matrícula --}}
<table class="data">
    <tr>
        <td class="label">Aluno(a):</td>
        <td colspan="3">{{ $student?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Nº de Matrícula:</td>
        <td>{{ $enrollment->registration_number }}</td>
        <td class="label">CPF:</td>
        <td>{{ $student?->cpf ?: '—' }}</td>
    </tr>
    <tr>
        <td class="label">Data de Nascimento:</td>
        <td>{{ $student?->birth_date?->format('d/m/Y') ?? '—' }}</td>
        <td class="label">Data da Matrícula:</td>
        <td>{{ $enrollment->enrollment_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Série / Etapa:</td>
        <td>{{ $enrollment->class?->gradeLevel?->name ?? '—' }}</td>
        <td class="label">Turma:</td>
        <td>{{ $enrollment->class?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Turno:</td>
        <td>{{ $enrollment->class?->shift?->label() ?? '—' }}</td>
        <td class="label">Nº de Chamada:</td>
        <td>{{ $enrollment->roll_number ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Situação:</td>
        <td>{{ $enrollment->status?->label() ?? '—' }}</td>
        <td class="label">Ano Letivo:</td>
        <td>{{ $schoolYear }}</td>
    </tr>
    @if($isRematricula && $enrollment->previousEnrollment)
        <tr>
            <td class="label">Matrícula anterior:</td>
            <td colspan="3">
                {{ $enrollment->previousEnrollment->registration_number }}
                @if($enrollment->previousEnrollment->class)
                    ({{ $enrollment->previousEnrollment->class->name }})
                @endif
            </td>
        </tr>
    @endif
</table>

{{-- Documentos entregues --}}
@if($enrollment->documents->isNotEmpty())
    <div class="section-title">Documentação</div>
    <table class="docs">
        @foreach($enrollment->documents as $doc)
            <tr>
                <td>{{ \App\Models\EnrollmentDocument::TIPOS[$doc->tipo] ?? $doc->tipo }}</td>
                <td class="status">{{ \App\Models\EnrollmentDocument::STATUS_OPTIONS[$doc->status] ?? $doc->status }}</td>
            </tr>
        @endforeach
    </table>
    @php $pendentes = $enrollment->documents->where('status', 'pendente')->count(); @endphp
    @if($pendentes > 0)
        <div class="pending-note">
            Há {{ $pendentes }} documento(s) pendente(s), que devem ser regularizados junto à Secretaria Escolar.
        </div>
    @endif
@endif

{{-- Declarações --}}
<div class="paragraph">
    Declaramos, para os devidos fins, que o(a) aluno(a) acima identificado(a) encontra-se
    {{ $isRematricula ? 'regularmente rematriculado(a)' : 'regularmente matriculado(a)' }} nesta instituição de
    ensino para o ano letivo de {{ $schoolYear }}, conforme dados registrados no {{ $systemName }}.
</div>

<div class="paragraph">
    O responsável declara, sob as penas da lei, a veracidade das informações prestadas no ato da
    {{ $isRematricula ? 'rematrícula' : 'matrícula' }}, comprometendo-se a comunicar à Secretaria Escolar
    qualquer alteração cadastral.
</div>

<div class="place-date">
    {{ $institution }}, {{ $generatedAt->format('d/m/Y H:i:s') }}
</div>

{{-- Assinaturas --}}
<table class="signatures">
    <tr>
        <td><div class="line">Secretaria Escolar</div></td>
        <td><div class="line">Responsável pelo(a) Aluno(a)</div></td>
    </tr>
</table>

{{-- Código de controle --}}
<div class="control">{{ $controlCode }}</div>
<div class="control-label">Código de controle do comprovante</div>

{{-- Rodapé --}}
<div class="footer">
    {{ $institution }} · Secretaria Escolar<br>
    Documento gerado eletronicamente pelo {{ $systemName }} · Operador: {{ $operator?->name ?? 'Sistema' }}<br>
    Recomenda-se a impressão e a guarda deste comprovante.
</div>

</body>
</html>
