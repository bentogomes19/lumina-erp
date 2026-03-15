<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'registration_number',
        'enrollment_date',
        'roll_number',
        'status',
        // Campos de trancamento
        'locked_reason',
        'lock_expires_at',
        // Campos de transferência
        'transfer_type',
        'transfer_destination',
        'transfer_reason',
        // Campos de cancelamento
        'cancel_reason',
        'cancel_observations',
        // Rastreabilidade
        'previous_enrollment_id',
        'operated_by_user_id',
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'lock_expires_at' => 'date',
        'status'          => EnrollmentStatus::class,
    ];

    // ── Relações ──────────────────────────────────────────────────────────────

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function logs()
    {
        return $this->hasMany(EnrollmentLog::class)->orderByDesc('created_at');
    }

    public function documents()
    {
        return $this->hasMany(EnrollmentDocument::class)->orderBy('tipo');
    }

    /** Matrícula anterior (origem da rematrícula ou transferência interna) */
    public function previousEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'previous_enrollment_id');
    }

    /** Operador responsável pela última ação */
    public function operatedBy()
    {
        return $this->belongsTo(User::class, 'operated_by_user_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Próximo número de chamada disponível dentro da turma */
    public static function nextRollNumberFor(int $classId): int
    {
        $max = static::where('class_id', $classId)->max('roll_number');
        return (int) $max + 1;
    }

    /** Gera registration_number no formato ANO + ID com 6 dígitos (ex: 2025000042) */
    public static function generateRegistrationNumber(self $enr): string
    {
        $year = $enr->schoolYear?->year
            ?? SchoolYear::where('id', $enr->school_year_id)->value('year')
            ?? now()->year;

        return $year . str_pad($enr->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica se há vagas disponíveis na turma informada.
     * TI pode ultrapassar o limite mediante confirmação explícita.
     */
    public static function classHasSlot(int $classId): bool
    {
        $class = SchoolClass::find($classId);
        if (! $class || ! $class->capacity) {
            return true; // Sem limite configurado
        }

        $ocupadas = static::where('class_id', $classId)
            ->whereIn('status', [
                EnrollmentStatus::ACTIVE->value,
                EnrollmentStatus::SUSPENDED->value,
                EnrollmentStatus::LOCKED->value,
            ])
            ->count();

        return $ocupadas < $class->capacity;
    }

    /**
     * Retorna o percentual de ocupação da turma (0-100).
     * Útil para alertas visuais (80% / 100%).
     */
    public static function classOccupancyPercent(int $classId): ?int
    {
        $class = SchoolClass::find($classId);
        if (! $class || ! $class->capacity) {
            return null;
        }

        $ocupadas = static::where('class_id', $classId)
            ->whereIn('status', [
                EnrollmentStatus::ACTIVE->value,
                EnrollmentStatus::SUSPENDED->value,
                EnrollmentStatus::LOCKED->value,
            ])
            ->count();

        return (int) round(($ocupadas / $class->capacity) * 100);
    }

    // ── Booted hooks ─────────────────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function (self $enr) {
            if (empty($enr->enrollment_date)) {
                $enr->enrollment_date = now();
            }
            if (empty($enr->roll_number)) {
                $enr->roll_number = self::nextRollNumberFor((int) $enr->class_id);
            }
            // Herda school_year_id da turma se não informado
            if (empty($enr->school_year_id) && $enr->class_id) {
                $enr->school_year_id = SchoolClass::find($enr->class_id)?->school_year_id;
            }
        });

        // registration_number só pode ser gerado após ter o ID (no created)
        static::created(function (self $enr) {
            if (empty($enr->registration_number)) {
                $enr->updateQuietly([
                    'registration_number' => self::generateRegistrationNumber($enr),
                ]);
            }
            // Registra log de criação automaticamente
            EnrollmentLog::registrar(
                enrollment: $enr,
                acao: 'criacao',
                statusNovo: $enr->status?->value,
            );
        });

        // Impede alteração do registration_number após criação
        static::updating(function (self $enr) {
            if ($enr->isDirty('registration_number') && $enr->getOriginal('registration_number')) {
                $enr->registration_number = $enr->getOriginal('registration_number');
            }
        });
    }
}
