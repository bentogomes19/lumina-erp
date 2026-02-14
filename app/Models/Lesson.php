<?php

namespace App\Models;

use App\Enums\LessonStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

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

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'status' => LessonStatus::class,
        'attendance_taken' => 'boolean',
        'attendance_taken_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            
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

    // ========== RELATIONSHIPS ==========

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendanceTakenBy()
    {
        return $this->belongsTo(User::class, 'attendance_taken_by');
    }

    // ========== SCOPES ==========

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeAttendanceTaken($query)
    {
        return $query->where('attendance_taken', true);
    }

    public function scopeAttendancePending($query)
    {
        return $query->where('attendance_taken', false)
            ->where('status', '!=', 'cancelled');
    }

    // ========== HELPER METHODS ==========

    /**
     * Marcar aula como realizada e chamada feita
     */
    public function markAttendanceTaken(?int $userId = null): void
    {
        $this->update([
            'attendance_taken' => true,
            'attendance_taken_at' => now(),
            'attendance_taken_by' => $userId ?? auth()->id(),
            'status' => 'completed',
        ]);
    }

    /**
     * Verificar se a aula pode ter chamada lançada
     * (não pode lançar chamada muito tempo depois)
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
     * Obter horário formatado
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
     * Verificar se está no horário da aula
     */
    public function isInProgress(): bool
    {
        if (!$this->date->isToday()) {
            return false;
        }

        $now = now();
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);

        return $now->between($start, $end);
    }

    /**
     * Calcular porcentagem de presença nesta aula
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
