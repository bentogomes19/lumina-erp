<?php

namespace Database\Seeders\Academic;

use App\Models\Lesson;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\TeacherAssignment;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Horários padrão por turno
     */
    private array $schedules = [
        'morning' => [
            ['07:00', '07:50'],
            ['07:50', '08:40'],
            ['08:40', '09:30'],
            ['09:50', '10:40'], // após intervalo
            ['10:40', '11:30'],
            ['11:30', '12:20'],
        ],
        'afternoon' => [
            ['13:00', '13:50'],
            ['13:50', '14:40'],
            ['14:40', '15:30'],
            ['15:50', '16:40'], // após intervalo
            ['16:40', '17:30'],
            ['17:30', '18:20'],
        ],
        'evening' => [
            ['18:30', '19:20'],
            ['19:20', '20:10'],
            ['20:10', '21:00'],
            ['21:10', '22:00'], // após intervalo
            ['22:00', '22:50'],
        ],
    ];

    /**
     * Tópicos por disciplina
     */
    private array $topics = [
        'Matemática' => [
            'Números e operações',
            'Álgebra e funções',
            'Geometria plana',
            'Geometria espacial',
            'Trigonometria',
            'Estatística e probabilidade',
            'Equações e sistemas',
            'Análise de gráficos',
        ],
        'Português' => [
            'Interpretação de texto',
            'Gramática: classes de palavras',
            'Sintaxe: período simples',
            'Sintaxe: período composto',
            'Literatura brasileira',
            'Redação: texto dissertativo',
            'Ortografia e pontuação',
            'Literatura portuguesa',
        ],
        'História' => [
            'Brasil Colônia',
            'Brasil Império',
            'Revolução Industrial',
            'Primeira Guerra Mundial',
            'Segunda Guerra Mundial',
            'Guerra Fria',
            'Ditadura Militar no Brasil',
            'Redemocratização do Brasil',
        ],
        'Geografia' => [
            'Cartografia básica',
            'Relevo e hidrografia',
            'Clima e vegetação',
            'População brasileira',
            'Urbanização',
            'Globalização',
            'Geopolítica mundial',
            'Questões ambientais',
        ],
        'Ciências' => [
            'Células e tecidos',
            'Sistema digestório',
            'Sistema respiratório',
            'Sistema circulatório',
            'Genética básica',
            'Ecologia e meio ambiente',
            'Química: átomos e moléculas',
            'Física: movimento e energia',
        ],
        'Inglês' => [
            'Present Simple',
            'Past Simple',
            'Future: will and going to',
            'Present Perfect',
            'Modal verbs',
            'Reading comprehension',
            'Writing: emails and letters',
            'Vocabulary: daily life',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = SchoolYear::where('is_active', true)->first();
        
        if (!$activeYear) {
            $this->command?->warn('LessonSeeder: nenhum ano letivo ativo encontrado.');
            return;
        }

        // Pegar turmas do ano ativo
        $classes = SchoolClass::where('school_year_id', $activeYear->id)
            ->with(['teacherAssignments.teacher', 'teacherAssignments.subject'])
            ->get();

        if ($classes->isEmpty()) {
            $this->command?->warn('LessonSeeder: nenhuma turma encontrada para o ano letivo ativo.');
            return;
        }

        $this->command?->info("Gerando aulas para {$classes->count()} turmas...");

        $totalLessons = 0;

        foreach ($classes as $class) {
            $assignments = $class->teacherAssignments;
            
            if ($assignments->isEmpty()) {
                $this->command?->warn("Turma {$class->name} sem atribuições de professor.");
                continue;
            }

            // Gerar aulas dos últimos 60 dias até 30 dias no futuro
            $startDate = now()->subDays(60);
            $endDate = now()->addDays(30);
            
            $generatedForClass = $this->generateLessonsForClass(
                $class,
                $assignments,
                $startDate,
                $endDate
            );

            $totalLessons += $generatedForClass;
            $this->command?->info("  {$class->name}: {$generatedForClass} aulas geradas");
        }

        $this->command?->info("Total de {$totalLessons} aulas geradas com sucesso!");
    }

    /**
     * Gerar aulas para uma turma específica
     */
    private function generateLessonsForClass(
        SchoolClass $class,
        $assignments,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        $count = 0;
        $shift = $class->shift->value;
        
        if (!isset($this->schedules[$shift])) {
            $this->command?->warn("Turno '{$shift}' não configurado.");
            return 0;
        }

        $scheduleSlots = $this->schedules[$shift];
        
        // Distribuir disciplinas nos dias da semana (seg-sex)
        $weekDays = [1, 2, 3, 4, 5]; // Segunda a sexta
        $assignmentsArray = $assignments->shuffle()->toArray();
        $assignmentIndex = 0;
        $totalAssignments = count($assignmentsArray);

        if ($totalAssignments === 0) {
            return 0;
        }

        // Percorrer todos os dias do período
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            // Verificar se é dia letivo (não é fim de semana nem feriado)
            if (!\App\Models\SchoolHoliday::isSchoolDay($currentDate)) {
                $currentDate->addDay();
                continue;
            }

            // Definir quantas aulas terão neste dia (entre 4 e 6 aulas)
            $lessonsPerDay = rand(4, min(6, count($scheduleSlots)));
            
            for ($slotIndex = 0; $slotIndex < $lessonsPerDay; $slotIndex++) {
                $assignment = $assignmentsArray[$assignmentIndex % $totalAssignments];
                $assignmentIndex++;

                $schedule = $scheduleSlots[$slotIndex];
                
                // Definir status da aula
                $isPast = $currentDate->lt(now()->subDays(1));
                $isToday = $currentDate->isToday();
                $isFuture = $currentDate->isFuture();
                
                if ($isPast) {
                    $status = 'completed';
                    $attendanceTaken = rand(1, 10) <= 8; // 80% das aulas passadas têm chamada
                } elseif ($isToday) {
                    $status = 'scheduled';
                    $attendanceTaken = false;
                } else {
                    $status = 'scheduled';
                    $attendanceTaken = false;
                }

                // Obter tópicos
                $subjectName = $assignment['subject']['name'] ?? 'Disciplina';
                $topicsList = $this->topics[$subjectName] ?? ['Conteúdo programático'];
                $topic = $topicsList[array_rand($topicsList)];

                $lessonData = [
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'teacher_id' => $assignment['teacher_id'],
                    'class_id' => $class->id,
                    'subject_id' => $assignment['subject_id'],
                    'school_year_id' => $class->school_year_id,
                    'date' => $currentDate->format('Y-m-d'),
                    'start_time' => $schedule[0],
                    'end_time' => $schedule[1],
                    'topic' => $topic,
                    'status' => $status,
                    'attendance_taken' => $attendanceTaken,
                    'attendance_taken_at' => $attendanceTaken ? $currentDate->copy()->setTimeFromTimeString($schedule[1])->addMinutes(5) : null,
                ];

                Lesson::create($lessonData);
                $count++;
            }

            $currentDate->addDay();
        }

        return $count;
    }
}
