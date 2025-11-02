<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SchoolClassSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Ano letivo alvo
        $year = (int) date('Y');

        $schoolYearId = SchoolYear::where('is_active', true)->value('id')
            ?? SchoolYear::firstOrCreate(
                ['year' => $year],
                ['is_active' => true, 'starts_at' => "$year-02-01", 'ends_at' => "$year-12-20"]
            )->id;

        // 2) Mapeamento de turmas por Série/Etapa
        //    Ajuste os nomes conforme estão em grade_levels.name no seu banco.
        $map = [
            // FUND I
            '1º Ano' => [
                ['name' => '1º A', 'shift' => 'morning',   'type' => 'regular', 'capacity' => 35],
                ['name' => '1º B', 'shift' => 'afternoon', 'type' => 'regular', 'capacity' => 35],
            ],
            '2º Ano' => [
                ['name' => '2º A', 'shift' => 'morning', 'type' => 'regular', 'capacity' => 35],
            ],
            '5º Ano' => [
                ['name' => '5º A', 'shift' => 'afternoon', 'type' => 'regular', 'capacity' => 35],
            ],

            // FUND II
            '6º Ano' => [
                ['name' => '6º A', 'shift' => 'morning', 'type' => 'regular', 'capacity' => 38],
                ['name' => '6º B', 'shift' => 'evening', 'type' => 'regular', 'capacity' => 38],
            ],

            // MÉDIO
            '1ª Série' => [
                ['name' => '1ª Série A', 'shift' => 'morning', 'type' => 'regular', 'capacity' => 40],
            ],
            '2ª Série' => [
                ['name' => '2ª Série A', 'shift' => 'afternoon', 'type' => 'regular', 'capacity' => 40],
            ],
        ];

        // 3) Abreviações de turno (código RM-like)
        $shiftAbbr = ['morning' => 'MAN', 'afternoon' => 'TAR', 'evening' => 'NOI'];

        foreach ($map as $gradeName => $classes) {
            $grade = GradeLevel::where('name', $gradeName)->first();

            if (! $grade) {
                $this->command?->warn("⚠ Série não encontrada: $gradeName — pulando…");
                continue;
            }

            foreach ($classes as $cfg) {
                $name     = $cfg['name'];
                $shift    = Arr::get($cfg, 'shift', 'morning');
                $type     = Arr::get($cfg, 'type', 'regular');
                $capacity = Arr::get($cfg, 'capacity', 35);
                $status   = 'open';

                $serieToken = strtoupper(
                    preg_replace('/[^0-9A-ZÀ-ÿ]+/u', '', Str::ascii($gradeName))
                );
                $nameToken  = strtoupper(
                    preg_replace('/[^0-9A-ZÀ-ÿ]+/u', '', Str::ascii($name))
                );
                $abbr       = $shiftAbbr[$shift] ?? 'MAN';
                $code       = "{$nameToken}-{$abbr}-{$year}";

                $class = SchoolClass::updateOrCreate(
                    [
                        'school_year_id' => $schoolYearId,
                        'grade_level_id' => $grade->id,
                        'name'           => $name,
                        'shift'          => $shift,
                    ],
                    [
                        'uuid'                => (string) Str::uuid(),
                        'code'                => $code,
                        'type'                => $type,
                        'capacity'            => $capacity,
                        'status'              => $status,
                        'homeroom_teacher_id' => null, // ajuste se quiser vincular um professor-responsável
                    ]
                );

                if (Schema::hasTable('grade_level_subject') && Schema::hasTable('class_subject_teacher')) {
                    $subjects = DB::table('grade_level_subject')
                        ->where('grade_level_id', $grade->id)
                        ->select('subject_id', 'hours_weekly')
                        ->get();

                    foreach ($subjects as $row) {
                        DB::table('class_subject_teacher')->updateOrInsert(
                            [
                                'class_id'   => $class->id,
                                'subject_id' => $row->subject_id,
                                'teacher_id' => null,
                            ],
                            [
                                'hours_weekly' => $row->hours_weekly ?? 1,
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]
                        );
                    }
                }

                $this->command?->info("✓ Turma gerada: {$class->name} ({$code}) [{$gradeName}]");
            }
        }
    }
}
