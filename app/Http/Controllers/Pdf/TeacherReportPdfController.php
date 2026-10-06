<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\TeacherRosterService;
use App\Support\PermissionAccess;
use Barryvdh\DomPDF\Facade\Pdf;

class TeacherReportPdfController extends Controller
{
    public function grades(Assessment $assessment)
    {
        $teacher = $this->authorizedTeacher('teacher.grades.view');
        abort_unless((int) $assessment->teacher_id === (int) $teacher->id, 403);
        $this->authorizePair($teacher, $assessment->class_id, $assessment->subject_id);

        $assessment->load(['schoolClass.schoolYear', 'subject']);
        $grades = Grade::query()->where('assessment_id', $assessment->id)->get()->keyBy('student_id');
        $rows = app(TeacherRosterService::class)->forAssessment($assessment)->map(function ($enrollment) use ($grades): array {
            $grade = $grades->get($enrollment->student_id);

            return [
                'number' => $enrollment->roll_number ?? '—',
                'name' => $enrollment->student?->name ?? '—',
                'value' => $grade ? number_format((float) $grade->score, 2, ',', '.') : '—',
                'note' => $grade?->comment ?? '',
            ];
        })->all();

        return Pdf::loadHTML($this->document(
            'Relatório de notas',
            $teacher,
            [
                'Ano letivo' => $assessment->schoolClass?->schoolYear?->year ?? '—',
                'Turma' => $assessment->schoolClass?->name ?? '—',
                'Disciplina' => $assessment->subject?->name ?? '—',
                'Avaliação' => $assessment->title,
                'Data' => $assessment->date?->format('d/m/Y') ?? '—',
            ],
            ['Nº', 'Aluno', 'Nota', 'Observação'],
            $rows,
            ['number', 'name', 'value', 'note'],
        ))->setPaper('a4')->stream('notas-avaliacao-'.$assessment->id.'.pdf');
    }

    public function attendance(Lesson $lesson)
    {
        $teacher = $this->authorizedTeacher('teacher.attendance.view');
        abort_unless((int) $lesson->teacher_id === (int) $teacher->id, 403);
        $this->authorizePair($teacher, $lesson->class_id, $lesson->subject_id);

        $lesson->load(['schoolClass.schoolYear', 'subject']);
        $records = Attendance::query()->where('lesson_id', $lesson->id)->get()->keyBy('student_id');
        $rows = app(TeacherRosterService::class)->forLesson($lesson)->map(function ($enrollment) use ($records): array {
            return [
                'number' => $enrollment->roll_number ?? '—',
                'name' => $enrollment->student?->name ?? '—',
                'value' => $records->get($enrollment->student_id)?->status?->label() ?? 'Não registrado',
            ];
        })->all();

        return Pdf::loadHTML($this->document(
            'Relatório de frequência',
            $teacher,
            [
                'Ano letivo' => $lesson->schoolClass?->schoolYear?->year ?? '—',
                'Turma' => $lesson->schoolClass?->name ?? '—',
                'Disciplina' => $lesson->subject?->name ?? '—',
                'Aula' => $lesson->date?->format('d/m/Y').' · '.$lesson->start_time?->format('H:i').'–'.$lesson->end_time?->format('H:i'),
            ],
            ['Nº', 'Aluno', 'Situação'],
            $rows,
            ['number', 'name', 'value'],
        ))->setPaper('a4')->stream('frequencia-aula-'.$lesson->id.'.pdf');
    }

    private function authorizedTeacher(string $permission): Teacher
    {
        $user = auth('teacher')->user();
        $teacher = $user?->teacher;
        abort_unless($user && $teacher && $teacher->canAccessOperationally()
            && PermissionAccess::for($user, $permission), 403);

        return $teacher;
    }

    private function authorizePair(Teacher $teacher, int $classId, int $subjectId): void
    {
        abort_unless(TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->exists(), 403);
    }

    private function document(string $title, Teacher $teacher, array $details, array $headings, array $rows, array $keys): string
    {
        $detailsHtml = collect($details)->map(fn ($value, $label) => '<div><strong>'.e($label).':</strong> '.e((string) $value).'</div>')->implode('');
        $headingsHtml = collect($headings)->map(fn ($label) => '<th>'.e($label).'</th>')->implode('');
        $rowsHtml = collect($rows)->map(fn (array $row) => '<tr>'.collect($keys)
            ->map(fn (string $key) => '<td>'.e((string) ($row[$key] ?? '')).'</td>')->implode('')
            .'</tr>')->implode('');

        return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><style>
            @page{margin:20mm 16mm}body{font-family:DejaVu Sans,sans-serif;color:#1f2937;font-size:10px}
            h1{font-size:18px;color:#24574e;margin:0 0 5mm}.meta{display:grid;grid-template-columns:1fr 1fr;gap:2mm;margin:0 0 7mm}
            .teacher{margin:0 0 5mm;color:#475569}table{width:100%;border-collapse:collapse}th{background:#32665d;color:white;text-align:left}
            th,td{border:1px solid #cbd5e1;padding:6px 7px;vertical-align:top}tr:nth-child(even){background:#f1f5f9}
            footer{margin-top:8mm;border-top:1px solid #cbd5e1;padding-top:3mm;color:#64748b;font-size:8px}
            </style></head><body><h1>'.e($title).'</h1><p class="teacher">Professor: '.e($teacher->name).'</p>
            <div class="meta">'.$detailsHtml.'</div><table><thead><tr>'.$headingsHtml.'</tr></thead><tbody>'.$rowsHtml.'</tbody></table>
            <footer>Emitido em '.e(now()->format('d/m/Y H:i')).' · Portal do Professor - Lumina ERP</footer></body></html>';
    }
}
