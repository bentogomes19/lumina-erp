<?php

namespace App\Console\Commands;

use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DiagnoseSchemaIntegrity extends Command
{
    protected $signature = 'schema:diagnose-integrity';

    protected $description = 'Inspeciona, em modo somente leitura, vínculos e inconsistências do schema acadêmico real.';

    public function handle(): int
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            $this->error("Driver não suportado pelo diagnóstico: {$driver}.");

            return self::FAILURE;
        }

        try {
            $report = $this->buildReport($connection, $driver);
            $path = storage_path('app/diagnostico-schema.md');

            File::put($path, $report.PHP_EOL);

            $this->line($report);
            $this->newLine();
            $this->info("Relatório salvo em: {$path}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Falha ao executar o diagnóstico somente leitura: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function buildReport(ConnectionInterface $connection, string $driver): string
    {
        $foreignKeys = $this->attendanceForeignKeys($driver);
        $orphans = $this->attendanceOrphans();
        $studentRelation = $this->diagnoseStudentRelation();
        $database = (string) $connection->getDatabaseName();
        $connectionName = (string) $connection->getName();
        $gradeLevelExists = Schema::connection($connectionName)
            ->hasColumn('school_years', 'grade_level_id');

        $lines = [
            '# Diagnóstico de integridade do schema real',
            '',
            '- Executado em: '.now()->toIso8601String(),
            "- Conexão Laravel: `{$connectionName}`",
            "- Driver: `{$driver}`",
            "- Banco conectado: `{$database}`",
            '- Operações no banco: somente consultas de leitura',
            '',
            '## 1. Foreign keys físicas de `attendances`',
            '',
            '| Coluna | Constraint | Tabela referenciada | Coluna referenciada | ON DELETE |',
            '|---|---|---|---|---|',
        ];

        foreach (['student_id', 'class_id', 'subject_id'] as $column) {
            $constraints = $foreignKeys[$column] ?? [];

            if ($constraints === []) {
                $lines[] = "| `{$column}` | nenhuma | — | — | NENHUMA |";

                continue;
            }

            foreach ($constraints as $constraint) {
                $lines[] = sprintf(
                    '| `%s` | `%s` | `%s` | `%s` | %s |',
                    $column,
                    $constraint['constraint_name'],
                    $constraint['referenced_table'],
                    $constraint['referenced_column'],
                    $constraint['delete_rule'],
                );
            }
        }

        $lines = [
            ...$lines,
            '',
            '## 2. Coluna `school_years.grade_level_id`',
            '',
            $gradeLevelExists
                ? '- Existe fisicamente no banco conectado: **SIM**.'
                : '- Existe fisicamente no banco conectado: **NÃO**.',
            '',
            '## 3. Referências órfãs em `attendances`',
            '',
            '| Coluna | Tabela esperada | Linhas órfãs |',
            '|---|---|---:|',
            "| `student_id` | `students` | {$orphans['student_id']} |",
            "| `class_id` | `classes` | {$orphans['class_id']} |",
            "| `subject_id` | `subjects` | {$orphans['subject_id']} |",
            '',
            '> Valores nulos não são considerados órfãos.',
            '',
            '## 4. Diagnóstico de `Student::students()`',
            '',
            '- Status da execução: **'.$studentRelation['status'].'**.',
            '- Estudantes usados como origem: '.$studentRelation['students_examined'].'.',
            '- Registros que o relacionamento retornaria incorretamente: **'.$studentRelation['incorrect_results'].'**.',
            '- Matrículas envolvidas na colisão `enrollments.class_id = students.id`: '.$studentRelation['colliding_enrollments'].'.',
            '- SQL gerado pelo Eloquent:',
            '',
            '```sql',
            $studentRelation['sql'],
            '```',
            '',
            '- Bindings do exemplo: `'.$studentRelation['bindings'].'`',
            '- Resultado do exemplo: '.$studentRelation['example_result'].'.',
        ];

        if ($studentRelation['error'] !== null) {
            $lines[] = '- Erro SQL: `'.$studentRelation['error'].'`';
        }

        $lines[] = '';
        $lines[] = '### Interpretação';
        $lines[] = '';
        $lines[] = $studentRelation['interpretation'];

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return array<string, list<array{constraint_name: string, referenced_table: string, referenced_column: string, delete_rule: string}>>
     */
    private function attendanceForeignKeys(string $driver): array
    {
        $rows = in_array($driver, ['mysql', 'mariadb'], true)
            ? DB::select(<<<'SQL'
                SELECT
                    kcu.COLUMN_NAME AS column_name,
                    kcu.CONSTRAINT_NAME AS constraint_name,
                    kcu.REFERENCED_TABLE_NAME AS referenced_table,
                    kcu.REFERENCED_COLUMN_NAME AS referenced_column,
                    rc.DELETE_RULE AS delete_rule
                FROM information_schema.KEY_COLUMN_USAGE kcu
                INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                    ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
                    AND rc.TABLE_NAME = kcu.TABLE_NAME
                    AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
                WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
                    AND kcu.TABLE_NAME = 'attendances'
                    AND kcu.COLUMN_NAME IN ('student_id', 'class_id', 'subject_id')
                    AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
                ORDER BY kcu.COLUMN_NAME, kcu.CONSTRAINT_NAME
                SQL)
            : DB::select(<<<'SQL'
                SELECT
                    source_column.attname AS column_name,
                    constraint_row.conname AS constraint_name,
                    referenced_table.relname AS referenced_table,
                    referenced_column.attname AS referenced_column,
                    CASE constraint_row.confdeltype
                        WHEN 'c' THEN 'CASCADE'
                        WHEN 'n' THEN 'SET NULL'
                        WHEN 'r' THEN 'RESTRICT'
                        WHEN 'd' THEN 'SET DEFAULT'
                        ELSE 'NO ACTION'
                    END AS delete_rule
                FROM pg_constraint constraint_row
                INNER JOIN pg_class source_table
                    ON source_table.oid = constraint_row.conrelid
                INNER JOIN pg_namespace source_namespace
                    ON source_namespace.oid = source_table.relnamespace
                INNER JOIN pg_class referenced_table
                    ON referenced_table.oid = constraint_row.confrelid
                CROSS JOIN LATERAL unnest(constraint_row.conkey) WITH ORDINALITY source_key(attnum, position)
                INNER JOIN LATERAL unnest(constraint_row.confkey) WITH ORDINALITY referenced_key(attnum, position)
                    ON referenced_key.position = source_key.position
                INNER JOIN pg_attribute source_column
                    ON source_column.attrelid = source_table.oid
                    AND source_column.attnum = source_key.attnum
                INNER JOIN pg_attribute referenced_column
                    ON referenced_column.attrelid = referenced_table.oid
                    AND referenced_column.attnum = referenced_key.attnum
                WHERE constraint_row.contype = 'f'
                    AND source_namespace.nspname = current_schema()
                    AND source_table.relname = 'attendances'
                    AND source_column.attname IN ('student_id', 'class_id', 'subject_id')
                ORDER BY source_column.attname, constraint_row.conname
                SQL);

        $foreignKeys = [];

        foreach ($rows as $row) {
            $foreignKeys[$row->column_name][] = [
                'constraint_name' => (string) $row->constraint_name,
                'referenced_table' => (string) $row->referenced_table,
                'referenced_column' => (string) $row->referenced_column,
                'delete_rule' => strtoupper((string) $row->delete_rule),
            ];
        }

        return $foreignKeys;
    }

    /** @return array{student_id: int, class_id: int, subject_id: int} */
    private function attendanceOrphans(): array
    {
        return [
            'student_id' => DB::table('attendances as attendance')
                ->leftJoin('students as target', 'target.id', '=', 'attendance.student_id')
                ->whereNotNull('attendance.student_id')
                ->whereNull('target.id')
                ->count(),
            'class_id' => DB::table('attendances as attendance')
                ->leftJoin('classes as target', 'target.id', '=', 'attendance.class_id')
                ->whereNotNull('attendance.class_id')
                ->whereNull('target.id')
                ->count(),
            'subject_id' => DB::table('attendances as attendance')
                ->leftJoin('subjects as target', 'target.id', '=', 'attendance.subject_id')
                ->whereNotNull('attendance.subject_id')
                ->whereNull('target.id')
                ->count(),
        ];
    }

    /**
     * @return array{status: string, students_examined: int, incorrect_results: int, colliding_enrollments: int, sql: string, bindings: string, example_result: string, error: ?string, interpretation: string}
     */
    private function diagnoseStudentRelation(): array
    {
        $studentsExamined = 0;
        $incorrectResults = 0;
        $sql = 'indisponível (nenhum estudante encontrado)';
        $bindings = '[]';
        $exampleResult = 'não houve estudante para exercitar o relacionamento';

        try {
            Student::query()->orderBy('id')->each(function (Student $student) use (
                &$studentsExamined,
                &$incorrectResults,
                &$sql,
                &$bindings,
                &$exampleResult,
            ): void {
                $relation = $student->students();
                $count = $relation->count();

                if ($studentsExamined === 0) {
                    $sql = $relation->toSql();
                    $bindings = json_encode($relation->getBindings(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    $exampleResult = "Student #{$student->getKey()} retornou {$count} registro(s)";
                }

                $studentsExamined++;
                $incorrectResults += $count;
            });

            if ($studentsExamined === 0) {
                $probe = new Student;
                $probe->setAttribute($probe->getKeyName(), 0);
                $relation = $probe->students();
                $count = $relation->count();
                $sql = $relation->toSql();
                $bindings = json_encode($relation->getBindings(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                $exampleResult = "Model-sonda Student #0 retornou {$count} registro(s), sem persistir dados";
            }

            $collidingEnrollments = DB::table('enrollments')
                ->join('students as erroneous_owner', 'erroneous_owner.id', '=', 'enrollments.class_id')
                ->count();

            return [
                'status' => 'consulta executada sem erro SQL',
                'students_examined' => $studentsExamined,
                'incorrect_results' => $incorrectResults,
                'colliding_enrollments' => $collidingEnrollments,
                'sql' => $sql,
                'bindings' => $bindings,
                'example_result' => $exampleResult,
                'error' => null,
                'interpretation' => $incorrectResults > 0
                    ? 'O relacionamento retorna alunos associados por coincidência numérica entre o ID do aluno de origem e o ID da turma. Esses resultados não representam uma relação aluno-aluno válida.'
                    : 'A consulta é estruturalmente incorreta, mas não retornou linhas no estado atual porque não houve colisão numérica entre `students.id` e `enrollments.class_id`.',
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'erro SQL',
                'students_examined' => $studentsExamined,
                'incorrect_results' => $incorrectResults,
                'colliding_enrollments' => 0,
                'sql' => $sql,
                'bindings' => $bindings,
                'example_result' => $exampleResult,
                'error' => $exception->getMessage(),
                'interpretation' => 'O relacionamento não pôde ser exercitado no schema real; o erro acima é o resultado do diagnóstico.',
            ];
        }
    }
}
