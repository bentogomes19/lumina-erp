<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Services\GradeCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Gera os documentos PDF do módulo de matrículas.
 * Todas as rotas exigem autenticação (middleware auth).
 */
class EnrollmentPdfController extends Controller {

    /* Helpers compartilhados. */

    /**
     * Carrega a matrícula com todas as relações necessárias para os documentos.
     *
     * @param Enrollment $enrollment
     *
     * @return Enrollment
     */
    private function load(Enrollment $enrollment): Enrollment {
        return $enrollment->load([
            'student',
            'class.gradeLevel',
            'class.schoolYear',
            'schoolYear',
            'operatedBy',
            'previousEnrollment.class.gradeLevel',
            'grades.subject',
            'documents',
        ]);
    }

    /**
     * Gera e exibe um documento PDF diretamente no navegador.
     *
     * @param string $view
     * @param array $data
     * @param string $filename
     *
     * @return Response
     */
    private function pdf(string $view, array $data, string $filename): Response {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('a4', 'portrait');

        return $pdf->stream($filename);
    }

    /* Documentos. */

    /**
     * Comprovante de Matrícula ou Rematrícula.
     * Visível para matrículas com qualquer status.
     *
     * @param Enrollment $enrollment
     *
     * @return Response
     */
    public function comprovante(Enrollment $enrollment): Response {
        Gate::authorize('viewDocument', $enrollment);

        $enr = $this->load($enrollment);

        $isRematricula = (bool) $enr->previous_enrollment_id;

        /* Código de controle estável por matrícula: identifica o comprovante sem expor dados internos. */
        $controlCode = substr(hash_hmac('sha256', implode('|', [
            $enr->id,
            $enr->registration_number,
            $enr->created_at?->toIso8601String(),
        ]), (string) config('app.key')), 0, 32);

        return $this->pdf(
            view: 'pdf.enrollment.comprovante',
            data: [
                'enrollment'    => $enr,
                'isRematricula' => $isRematricula,
                'generatedAt'   => now(),
                'operator'      => auth()->user(),
                'controlCode'   => $controlCode,
            ],
            filename: "comprovante-matricula-{$enr->registration_number}.pdf",
        );
    }

    /**
     * Comprovante de Transferência entre Turmas (interna).
     * Disponível somente para matrículas com status 'Transferida Interna'.
     *
     * @param Enrollment $enrollment
     *
     * @return Response
     */
    public function transferenciaInterna(Enrollment $enrollment): Response {
        Gate::authorize('viewDocument', $enrollment);

        $enr = $this->load($enrollment);

        /* Carrega a nova matrícula gerada pela transferência (filha) */
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
     *
     * @param Enrollment $enrollment
     *
     * @return Response
     */
    public function transferenciaExterna(Enrollment $enrollment): Response {
        Gate::authorize('viewDocument', $enrollment);

        $enr = $this->load($enrollment);

        /*
         * Agrupa as notas publicadas por disciplina. A média segue o mesmo cálculo do
         * boletim (peso das avaliações, média por bimestre e recuperação); rascunhos do
         * professor não entram no documento.
         */
        $gradeCalculation = app(GradeCalculationService::class);
        $historicoNotas   = $enr->grades
            ->filter(fn (Grade $grade): bool => $grade->locked_at !== null)
            ->groupBy(fn (Grade $grade) => $grade->subject?->name ?? 'Sem disciplina')
            ->map(fn ($grades) => [
                'subject' => $grades->first()->subject?->name ?? '—',
                'grades'  => $grades,
                'media'   => $gradeCalculation->subjectReport($grades->values())['overall_average'],
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
     *
     * @param Enrollment $enrollment
     *
     * @return Response
     */
    public function trancamento(Enrollment $enrollment): Response {
        Gate::authorize('viewDocument', $enrollment);

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
     *
     * @param Enrollment $enrollment
     *
     * @return Response
     */
    public function cancelamento(Enrollment $enrollment): Response {
        Gate::authorize('viewDocument', $enrollment);

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
