<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletim Escolar - {{ $student->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 24pt;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 16pt;
            color: #64748b;
            font-weight: normal;
        }
        
        .student-info {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        
        .student-info table {
            width: 100%;
        }
        
        .student-info td {
            padding: 5px;
        }
        
        .student-info .label {
            font-weight: bold;
            color: #475569;
            width: 150px;
        }
        
        .grades-section {
            margin-bottom: 25px;
        }
        
        .subject-title {
            background: #2563eb;
            color: white;
            padding: 10px 15px;
            font-size: 13pt;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
        }
        
        .term-section {
            margin-bottom: 15px;
        }
        
        .term-header {
            background: #cbd5e1;
            padding: 8px 15px;
            font-weight: bold;
            color: #1e293b;
            border-left: 4px solid #2563eb;
        }
        
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        .grades-table th {
            background: #e2e8f0;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #cbd5e1;
            font-size: 10pt;
        }
        
        .grades-table td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            font-size: 10pt;
        }
        
        .grades-table tr:nth-child(even) {
            background: #f8fafc;
        }
        
        .average-row {
            background: #dbeafe !important;
            font-weight: bold;
        }
        
        .average-row td {
            color: #1e40af;
        }
        
        .overall-average {
            background: #10b981;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 9pt;
            color: #64748b;
            padding-top: 15px;
            border-top: 1px solid #cbd5e1;
        }
        
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9pt;
            font-weight: bold;
        }
        
        .status-approved {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-recovery {
            background: #fed7aa;
            color: #92400e;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BOLETIM ESCOLAR</h1>
        <h2>Relatório de Desempenho Acadêmico</h2>
    </div>
    
    <div class="student-info">
        <table>
            <tr>
                <td class="label">Nome do Aluno:</td>
                <td>{{ $student->name }}</td>
            </tr>
            <tr>
                <td class="label">Matrícula:</td>
                <td>{{ $student->registration_number }}</td>
            </tr>
            <tr>
                <td class="label">Data de Emissão:</td>
                <td>{{ $generatedAt->format('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>
    
    <div class="grades-section">
        @foreach($reportData as $subjectName => $terms)
            <div class="subject-title">{{ $subjectName }}</div>
            
            @foreach($terms as $term => $termData)
                <div class="term-section">
                    <div class="term-header">
                        {{ match($term) {
                            'b1' => '1º Bimestre',
                            'b2' => '2º Bimestre',
                            'b3' => '3º Bimestre',
                            'b4' => '4º Bimestre',
                            default => $term,
                        } }}
                    </div>
                    
                    <table class="grades-table">
                        <thead>
                            <tr>
                                <th>Tipo de Avaliação</th>
                                <th style="text-align: center;">Sequência</th>
                                <th style="text-align: center;">Nota</th>
                                <th style="text-align: center;">Nota Máxima</th>
                                <th style="text-align: center;">Peso</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($termData['grades'] as $grade)
                                <tr>
                                    <td>
                                        {{ match($grade->assessment_type?->value ?? $grade->assessment_type) {
                                            'test' => 'Prova',
                                            'quiz' => 'Quiz',
                                            'work' => 'Trabalho',
                                            'project' => 'Projeto',
                                            'participation' => 'Participação',
                                            'recovery' => 'Recuperação',
                                            default => $grade->assessment_type,
                                        } }}
                                    </td>
                                    <td style="text-align: center;">{{ $grade->sequence }}</td>
                                    <td style="text-align: center; font-weight: bold;">
                                        {{ number_format($grade->score, 2, ',', '.') }}
                                    </td>
                                    <td style="text-align: center;">
                                        {{ number_format($grade->max_score, 2, ',', '.') }}
                                    </td>
                                    <td style="text-align: center;">
                                        {{ $grade->weight ? number_format($grade->weight, 2, ',', '.') : '-' }}
                                    </td>
                                    <td>{{ $grade->date_recorded?->format('d/m/Y') ?? '-' }}</td>
                                </tr>
                            @endforeach
                            <tr class="average-row">
                                <td colspan="2"><strong>Média do Bimestre:</strong></td>
                                <td colspan="4" style="text-align: center; font-size: 12pt;">
                                    {{ number_format($termData['average'], 2, ',', '.') }}
                                    @if($termData['average'] >= 7.0)
                                        <span class="status status-approved">✓ Aprovado</span>
                                    @elseif($termData['average'] >= 5.0)
                                        <span class="status status-recovery">⚠ Recuperação</span>
                                    @else
                                        <span class="status status-failed">✗ Insuficiente</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endforeach
    </div>
    
    <div class="overall-average">
        MÉDIA GERAL: {{ number_format($overallAverage, 2, ',', '.') }}
        @if($overallAverage >= 7.0)
            - DESEMPENHO SATISFATÓRIO
        @elseif($overallAverage >= 5.0)
            - NECESSITA RECUPERAÇÃO
        @else
            - DESEMPENHO INSUFICIENTE
        @endif
    </div>
    
    <div class="footer">
        <p><strong>LEGENDA:</strong></p>
        <p>
            Aprovado: Nota ≥ 7,0 | 
            Recuperação: 5,0 ≤ Nota < 7,0 | 
            Insuficiente: Nota < 5,0
        </p>
        <p style="margin-top: 10px;">
            Este documento foi gerado eletronicamente em {{ $generatedAt->format('d/m/Y') }} às {{ $generatedAt->format('H:i') }}.
        </p>
    </div>
</body>
</html>
