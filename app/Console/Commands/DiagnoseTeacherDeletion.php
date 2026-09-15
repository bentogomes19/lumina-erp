<?php

namespace App\Console\Commands;

use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DiagnoseTeacherDeletion extends Command
{
    protected $signature = 'teachers:diagnose-deletion {teacher_id : ID do professor a diagnosticar}';

    protected $description = 'Diagnostica, somente por leitura, o estado de exclusão e os vínculos físicos de um professor.';

    public function handle(): int
    {
        $teacherId = filter_var($this->argument('teacher_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($teacherId === false) {
            $this->error('O argumento teacher_id deve ser um inteiro positivo.');

            return self::INVALID;
        }

        try {
            $this->line($this->report($teacherId));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Falha no diagnóstico somente leitura: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function report(int $teacherId): string
    {
        $teacher = Teacher::withTrashed()->find($teacherId);
        $assignmentsCount = DB::table('teacher_assignments')->where('teacher_id', $teacherId)->count();
        $directLessonsCount = DB::table('lessons')->where('teacher_id', $teacherId)->count();
        $lessonsViaAssignmentsCount = DB::table('lessons as lesson')
            ->join('teacher_assignments as assignment', function ($join): void {
                $join->on('assignment.teacher_id', '=', 'lesson.teacher_id')
                    ->on('assignment.class_id', '=', 'lesson.class_id')
                    ->on('assignment.subject_id', '=', 'lesson.subject_id');
            })
            ->where('assignment.teacher_id', $teacherId)
            ->distinct()
            ->count('lesson.id');
        $directLessonsWithoutAssignment = DB::table('lessons as lesson')
            ->where('lesson.teacher_id', $teacherId)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('teacher_assignments as assignment')
                    ->whereColumn('assignment.teacher_id', 'lesson.teacher_id')
                    ->whereColumn('assignment.class_id', 'lesson.class_id')
                    ->whereColumn('assignment.subject_id', 'lesson.subject_id');
            })
            ->count();
        $screenQuery = $this->screenQuery($teacherId);

        $state = match (true) {
            $teacher === null => 'A linha não existe em `teachers`; ocorreu exclusão física ou o ID nunca existiu.',
            $teacher->trashed() => 'A linha existe fisicamente e está soft-deleted (`deleted_at` preenchido).',
            default => 'A linha existe fisicamente e está ativa (`deleted_at` nulo).',
        };

        $lines = [
            '# Diagnóstico de exclusão de professor',
            '',
            '- Executado em: '.now()->toIso8601String(),
            '- Conexão: `'.DB::connection()->getName().'`',
            '- Driver: `'.DB::connection()->getDriverName().'`',
            '- Banco: `'.DB::connection()->getDatabaseName().'`',
            "- Professor consultado: `{$teacherId}`",
            '- Operações no banco: somente consultas de leitura',
            '',
            '## 1. Estado físico da linha em `teachers`',
            '',
            '- Resultado: '.$state,
        ];

        if ($teacher !== null) {
            $lines[] = '- Nome: `'.$teacher->name.'`';
            $lines[] = '- `deleted_at`: '.($teacher->deleted_at?->toIso8601String() ?? 'NULL');
        }

        $lines = [
            ...$lines,
            '',
            '## 2. Linhas físicas em `teacher_assignments`',
            '',
            "- Linhas com `teacher_id = {$teacherId}`: **{$assignmentsCount}**.",
            '- Essas linhas '.($assignmentsCount === 0 ? '**não existem mais** fisicamente.' : '**ainda existem** fisicamente.'),
            '',
            '## 3. Linhas físicas em `lessons`',
            '',
            "- Linhas com `lessons.teacher_id = {$teacherId}`: **{$directLessonsCount}**.",
            '- Linhas que também possuem atribuição correspondente por professor + turma + disciplina: **'.$lessonsViaAssignmentsCount.'**.',
            '- Linhas diretas sem atribuição correspondente atualmente: **'.$directLessonsWithoutAssignment.'**.',
            '',
            '## 4. Consulta usada pela tela de turma/disciplina',
            '',
            '- Consulta reproduzida: para cada par turma/disciplina, `teacherAssignments()` filtrado pela disciplina e carregado com `with(\'teacher\')`.',
            '- Atribuições carregadas para o professor: **'.$screenQuery['assignments'].'**.',
            '- Atribuições cujo relacionamento `teacher` retornou um Model: **'.$screenQuery['visible_teachers'].'**.',
            '- Atribuições cujo relacionamento `teacher` retornou vazio: **'.$screenQuery['hidden_teachers'].'**.',
            '- Consulta equivalente aplicada ao Model `Teacher`:',
            '',
            '```sql',
            $screenQuery['sql'],
            '```',
            '',
            '- Bindings: `'.$screenQuery['bindings'].'`',
            '- Resultado direto sem `withTrashed()`: **'.($screenQuery['direct_result'] ? 'encontrado' : 'vazio').'**.',
            '- Confirmação do `SoftDeletingScope`: **'.$screenQuery['scope_conclusion'].'**.',
        ];

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return array{assignments: int, visible_teachers: int, hidden_teachers: int, sql: string, bindings: string, direct_result: bool, scope_conclusion: string}
     */
    private function screenQuery(int $teacherId): array
    {
        $assignmentPairs = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->get(['class_id', 'subject_id'])
            ->unique(fn (TeacherAssignment $assignment): string => $assignment->class_id.':'.$assignment->subject_id);
        $assignmentsCount = 0;
        $visibleTeachers = 0;

        foreach ($assignmentPairs as $pair) {
            $screenAssignments = TeacherAssignment::query()
                ->where('class_id', $pair->class_id)
                ->where('subject_id', $pair->subject_id)
                ->with('teacher')
                ->get()
                ->where('teacher_id', $teacherId);

            $assignmentsCount += $screenAssignments->count();
            $visibleTeachers += $screenAssignments
                ->filter(fn (TeacherAssignment $assignment): bool => $assignment->teacher !== null)
                ->count();
        }

        $teacherQuery = Teacher::query()->whereKey($teacherId);
        $sql = $teacherQuery->toSql();
        $bindings = json_encode($teacherQuery->getBindings(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $directResult = $teacherQuery->first();
        $hiddenTeachers = $assignmentsCount - $visibleTeachers;
        $physicalTeacher = Teacher::withTrashed()->find($teacherId);

        $scopeConclusion = match (true) {
            $physicalTeacher?->trashed() && $directResult === null => 'SIM — a linha existe, mas `teachers.deleted_at IS NULL` a remove do resultado',
            $physicalTeacher?->trashed() => 'NÃO — resultado inesperado para uma linha soft-deleted',
            $physicalTeacher === null => 'NÃO APLICÁVEL — não existe linha física para esse ID',
            default => 'NÃO APLICÁVEL — o professor não está soft-deleted',
        };

        return [
            'assignments' => $assignmentsCount,
            'visible_teachers' => $visibleTeachers,
            'hidden_teachers' => $hiddenTeachers,
            'sql' => $sql,
            'bindings' => $bindings,
            'direct_result' => $directResult !== null,
            'scope_conclusion' => $scopeConclusion,
        ];
    }
}
