<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Gera os documentos PDF do módulo de matrículas.
 * Todas as rotas exigem autenticação (middleware auth).
 */
class EnrollmentPdfController extends Controller
{
    // ── Helpers compartilhados ────────────────────────────────────────────────

    /** Carrega a matrícula com todas as relações necessárias para os documentos */
    private function load(int $id): Enrollment
    {
        return Enrollment::with([
            'student',
            'class.gradeLevel',
            'class.schoolYear',
            'schoolYear',
            'operatedBy',
            'previousEnrollment.class.gradeLevel',
            'grades.subject',
            'documents',
        ])->findOrFail($id);
    }

    /** Gera e retorna um PDF como download inline (abre no browser) */
    private function pdf(string $view, array $data, string $filename): Response
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    // ── Documentos ────────────────────────────────────────────────────────────

    /**
     * Comprovante de Matrícula ou Rematrícula.
     * Visível para matrículas com qualquer status.
     */
    public function comprovante(int $enrollment): Response
    {
        $enr = $this->load($enrollment);

        $isRematricula = (bool) $enr->previous_enrollment_id;

        return $this->pdf(
            view: 'pdf.enrollment.comprovante',
            data: [
                'enrollment'    => $enr,
                'isRematricula' => $isRematricula,
                'generatedAt'   => now(),
                'operator'      => auth()->user(),
            ],
            filename: "comprovante-matricula-{$enr->registration_number}.pdf",
        );
    }

    /**
     * Comprovante de Transferência entre Turmas (interna).
     * Disponível somente para matrículas com status 'Transferida Interna'.
     */
    public function transferenciaInterna(int $enrollment): Response
    {
        $enr = $this->load($enrollment);

        // Carrega a nova matrícula gerada pela transferência (filha)
        $novaMatricula = Enrollment::with(['class.gradeLevel', 'class.schoolYear'])
            ->where('previous_enrollment_id', $enr->id)
            ->first();

        return $this->pdf(
            view: 'pdf.enrollment.transferencia-interna',
            data: [
                'enrollment'    => $enr,
                'novaMatricula' => $novaMatricula,
                'generatedAt'   => now(),
                'operator'      => auth()->user(),
            ],
            filename: "transferencia-interna-{$enr->registration_number}.pdf",
        );
    }

    /**
     * Declaração de Transferência Externa.
     * Inclui histórico de notas do aluno.
     */
    public function transferenciaExterna(int $enrollment): Response
    {
        $enr = $this->load($enrollment);

        // Agrupa notas por disciplina para o histórico
        $historicoNotas = $enr->grades
            ->groupBy(fn ($g) => $g->subject?->name ?? 'Sem disciplina')
            ->map(fn ($grades) => [
                'subject' => $grades->first()->subject?->name ?? '—',
                'grades'  => $grades,
                'media'   => round($grades->avg('score'), 1),
            ]);

        return $this->pdf(
            view: 'pdf.enrollment.transferencia-externa',
            data: [
                'enrollment'     => $enr,
                'historicoNotas' => $historicoNotas,
                'generatedAt'    => now(),
                'operator'       => auth()->user(),
            ],
            filename: "declaracao-transferencia-{$enr->registration_number}.pdf",
        );
    }

    /**
     * Comprovante de Trancamento de Matrícula.
     */
    public function trancamento(int $enrollment): Response
    {
        $enr = $this->load($enrollment);

        return $this->pdf(
            view: 'pdf.enrollment.trancamento',
            data: [
                'enrollment'  => $enr,
                'generatedAt' => now(),
                'operator'    => auth()->user(),
            ],
            filename: "trancamento-{$enr->registration_number}.pdf",
        );
    }

    /**
     * Termo de Cancelamento de Matrícula.
     */
    public function cancelamento(int $enrollment): Response
    {
        $enr = $this->load($enrollment);

        return $this->pdf(
            view: 'pdf.enrollment.cancelamento',
            data: [
                'enrollment'  => $enr,
                'generatedAt' => now(),
                'operator'    => auth()->user(),
            ],
            filename: "cancelamento-{$enr->registration_number}.pdf",
        );
    }
}
