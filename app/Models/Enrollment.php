<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends BaseModel
{
    use SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
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

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enrollment_date' => 'date',
        'lock_expires_at'  => 'date',
        'status'          => EnrollmentStatus::class,
    ];

    /**
     * Retorna o aluno vinculado à matrícula.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Retorna a turma vinculada à matrícula.
     */
    public function class()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Retorna a turma vinculada à matrícula pelo campo class_id.
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Retorna o ano letivo da matrícula.
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Retorna as notas vinculadas à matrícula.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Retorna os logs de auditoria da matrícula.
     */
    public function logs()
    {
        return $this->hasMany(EnrollmentLog::class)->orderByDesc('created_at');
    }

    /**
     * Retorna os documentos da matrícula.
     */
    public function documents()
    {
        return $this->hasMany(EnrollmentDocument::class)->orderBy('tipo');
    }

    /**
     * Retorna a matrícula anterior usada como origem da rematrícula ou transferência interna.
     */
    public function previousEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'previous_enrollment_id');
    }

    /**
     * Retorna o operador responsável pela última ação na matrícula.
     */
    public function operatedBy()
    {
        return $this->belongsTo(User::class, 'operated_by_user_id');
    }

    /**
     * Retorna o próximo número de chamada disponível dentro da turma.
     */
    public static function nextRollNumberFor(int $classId): int
    {
        $max = static::where('class_id', $classId)->max('roll_number');

        return (int) $max + 1;
    }

    /**
     * Gera o número de matrícula no formato ano mais ID com seis dígitos.
     */
    public static function generateRegistrationNumber(self $enr): string
    {
        $year = $enr->schoolYear?->year
            ?? SchoolYear::where('id', $enr->school_year_id)->value('year')
            ?? now()->year;

        return $year . str_pad($enr->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verifica se há vagas disponíveis na turma informada.
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
     * Retorna o percentual de ocupação da turma.
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

    /**
     * Define valores padrão e registra auditoria durante o ciclo de vida da matrícula.
     */
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
