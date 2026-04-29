<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends BaseModel
{
    use SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'teacher_id',
        'class_id',
        'subject_id',
        'school_year_id',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'topic',
        'content',
        'objectives',
        'homework',
        'observations',
        'status',
        'attendance_taken',
        'attendance_taken_at',
        'attendance_taken_by',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date'                => 'date',
        'start_time'          => 'datetime:H:i',
        'end_time'            => 'datetime:H:i',
        'status'              => LessonStatus::class,
        'attendance_taken'    => 'boolean',
        'attendance_taken_at' => 'datetime',
    ];

    /**
     * Configura os eventos de criação e atualização da aula.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            $model->fillUuidIfMissing();
            
            // Calcular duração automaticamente
            if ($model->start_time && $model->end_time && !$model->duration_minutes) {
                $start = \Carbon\Carbon::parse($model->start_time);
                $end = \Carbon\Carbon::parse($model->end_time);
                $model->duration_minutes = $start->diffInMinutes($end);
            }
        });
        
        static::updating(function ($model) {
            // Recalcular duração se horários mudarem
            if ($model->isDirty(['start_time', 'end_time'])) {
                $start = \Carbon\Carbon::parse($model->start_time);
                $end = \Carbon\Carbon::parse($model->end_time);
                $model->duration_minutes = $start->diffInMinutes($end);
            }
        });
    }

    /**
     * Retorna o professor responsável pela aula.
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Retorna a turma da aula.
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Retorna a disciplina da aula.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Retorna o ano letivo da aula.
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Retorna as chamadas registradas para a aula.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Retorna o usuário que marcou a chamada como realizada.
     */
    public function attendanceTakenBy()
    {
        return $this->belongsTo(User::class, 'attendance_taken_by');
    }

    /**
     * Filtra aulas por professor.
     */
    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    /**
     * Filtra aulas por turma.
     */
    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Filtra aulas por disciplina.
     */
    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    /**
     * Filtra aulas por data exata.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Filtra aulas por intervalo de datas.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Filtra aulas concluídas.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Filtra aulas agendadas.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Filtra aulas com chamada realizada.
     */
    public function scopeAttendanceTaken($query)
    {
        return $query->where('attendance_taken', true);
    }

    /**
     * Filtra aulas com chamada pendente.
     */
    public function scopeAttendancePending($query)
    {
        return $query->where('attendance_taken', false)
            ->where('status', '!=', 'cancelled');
    }

    /**
     * Marca a aula como realizada e com chamada feita.
     */
    public function markAttendanceTaken(?int $userId = null): void
    {
        $this->update([
            'attendance_taken'    => true,
            'attendance_taken_at' => now(),
            'attendance_taken_by' => $userId ?? auth()->id(),
            'status'              => 'completed',
        ]);
    }

    /**
     * Verifica se a aula pode receber lançamento de chamada.
     */
    public function canTakeAttendance(int $maxDaysAfter = 3): bool
    {
        // Já foi feita a chamada
        if ($this->attendance_taken) {
            return false;
        }

        // Aula cancelada
        if ($this->status === 'cancelled') {
            return false;
        }

        // Aula no futuro
        if ($this->date->isFuture()) {
            return false;
        }

        // Verificar se não passou do limite de dias
        $daysSince = now()->diffInDays($this->date, false);
        
        return $daysSince <= $maxDaysAfter;
    }

    /**
     * Retorna o intervalo de horário formatado para exibição.
     */
    public function getTimeRangeAttribute(): string
    {
        return sprintf(
            '%s - %s',
            \Carbon\Carbon::parse($this->start_time)->format('H:i'),
            \Carbon\Carbon::parse($this->end_time)->format('H:i')
        );
    }

    /**
     * Indica se a aula está em andamento no horário atual.
     */
    public function isInProgress(): bool
    {
        if (!$this->date->isToday()) {
            return false;
        }

        $now = now();
        $start = \Carbon\Carbon::parse($this->start_time);
        $end   = \Carbon\Carbon::parse($this->end_time);

        return $now->between($start, $end);
    }

    /**
     * Calcula a porcentagem de presença da aula.
     */
    public function getAttendanceRate(): float
    {
        $total = $this->attendances()->count();
        
        if ($total === 0) {
            return 0.0;
        }

        $present = $this->attendances()
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($present / $total) * 100, 2);
    }
}
