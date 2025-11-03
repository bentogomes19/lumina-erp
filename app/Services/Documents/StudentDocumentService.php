<?php

namespace App\Services\Documents;

use App\Models\Student;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentDocumentService
{
    public function downloadEnrollmentDeclaration(Student $student): StreamedResponse
    {
        // Gere o PDF (dompdf/snappy/etc). Abaixo, stub streaming simples:
        $content = "Declaração de Matrícula\nAluno: {$student->name}\nMatrícula: {$student->registration_number}";
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'declaracao_matricula.pdf');
    }

    public function downloadPartialTranscript(Student $student): StreamedResponse
    {
        // Gere o PDF real. Stub:
        $content = "Histórico Parcial\nAluno: {$student->name}\n...";
        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, 'historico_parcial.pdf');
    }
}
