<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'student_id',
        'class_id',
        'subject_id',
        'lesson_id',
        'date',
        'time',
        'status',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',
        'status' => AttendanceStatus::class,
    ];

    // ========== RELATIONSHIPS ==========

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    // ========== SCOPES ==========

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForLesson($query, int $lessonId)
    {
        return $query->where('lesson_id', $lessonId);
    }

    public function scopeMonth($query, int $month)
    {
        return $query->whereMonth('date', $month);
    }

    public function scopeYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopePresent($query)
    {
        return $query->whereIn('status', ['present', 'late']);
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    // ========== VALIDATION METHODS ==========

    /**
     * Verificar se o lançamento está dentro do prazo permitido
     * 
     * @param int $maxDaysAfter Dias máximos após a aula
     * @return bool
     */
    public static function canRecordForDate(Carbon $date, int $maxDaysAfter = 3): bool
    {
        $daysSince = now()->diffInDays($date, false);
        
        return $daysSince <= $maxDaysAfter;
    }

    /**
     * Validar se horário está dentro do horário da aula
     */
    public function isTimeValid(): bool
    {
        if (!$this->lesson || !$this->time) {
            return true; // Não validar se não houver aula vinculada
        }

        $recordTime = Carbon::parse($this->time);
        $lessonStart = Carbon::parse($this->lesson->start_time);
        $lessonEnd = Carbon::parse($this->lesson->end_time);

        // Permitir lançamento até 30 min após o fim da aula
        $lessonEnd->addMinutes(30);

        return $recordTime->between($lessonStart, $lessonEnd);
    }

    // ========== STATIC CALCULATION METHODS ==========

    /**
     * Calcular frequência de um aluno em uma turma/disciplina
     * Retorna: ['frequency' => float, 'present' => int, 'total' => int, 'alert' => bool]
     */
    public static function calculateFrequency(
        int $studentId,
        ?int $classId = null,
        ?int $subjectId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $query = static::where('student_id', $studentId);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($startDate || $endDate) {
            $query->whereBetween('date', [
                $startDate ?? now()->startOfYear(),
                $endDate ?? now()->endOfYear()
            ]);
        }

        $total = $query->count();

        if ($total === 0) {
            return [
                'frequency' => 0.0,
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'total' => 0,
                'alert' => false,
            ];
        }

        $present = (clone $query)->where('status', 'present')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        
        // Frequência = (Presenças + Atrasos) / Total * 100
        $frequency = (($present + $late) / $total) * 100;
        
        // Alerta se frequência < 75%
        $alert = $frequency < 75.0;

        return [
            'frequency' => round($frequency, 2),
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'total' => $total,
            'alert' => $alert,
        ];
    }

    /**
     * Obter relatório de frequência de todos os alunos de uma turma
     */
    public static function getClassFrequencyReport(
        int $classId,
        ?int $subjectId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $students = Student::whereHas('classes', function ($q) use ($classId) {
            $q->where('classes.id', $classId);
        })->get();

        $report = [];

        foreach ($students as $student) {
            $report[] = array_merge(
                [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                ],
                static::calculateFrequency(
                    $student->id,
                    $classId,
                    $subjectId,
                    $startDate,
                    $endDate
                )
            );
        }

        return $report;
    }

    /**
     * Obter alunos em risco de reprovação por falta (frequência < 75%)
     */
    public static function getStudentsAtRisk(
        int $classId,
        ?int $subjectId = null,
        float $thresholdPercentage = 75.0
    ): array {
        $report = static::getClassFrequencyReport($classId, $subjectId);

        return array_filter($report, function ($item) use ($thresholdPercentage) {
            return $item['frequency'] < $thresholdPercentage;
        });
    }
}

