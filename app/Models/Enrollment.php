<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends BaseModel {

    use SoftDeletes;

    /**
     * Máscara usada quando o parâmetro "enrollment.number_format" não está disponível
     * (mantém o formato anterior: ano + ID com seis dígitos).
     */
    public const DEFAULT_NUMBER_FORMAT = '{year}{sequence}';

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
        'submission_token',
        'enrollment_date',
        'roll_number',
        'status',

        /* Campos de trancamento. */
        'locked_reason',
        'lock_expires_at',

        /* Campos de transferência. */
        'transfer_type',
        'transfer_destination',
        'transfer_reason',

        /* Campos de cancelamento. */
        'cancel_reason',
        'cancel_observations',

        /* Rastreabilidade. */
        'previous_enrollment_id',
        'operated_by_user_id',
    ];

    /**
     * Campos omitidos nas serializações da matrícula.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'submission_token',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enrollment_date' => 'date',
        'lock_expires_at' => 'date',
        'status'          => EnrollmentStatus::class,
    ];

    /**
     * Retorna o aluno vinculado à matrícula.
     *
     * @return mixed
     */
    public function student() {
        return $this->belongsTo(Student::class);
    }

    /**
     * Retorna a turma vinculada à matrícula.
     *
     * @return mixed
     */
    public function class() {
        return $this->belongsTo(SchoolClass::class);
    }

    /**
     * Retorna a turma vinculada à matrícula pelo campo class_id.
     *
     * @return mixed
     */
    public function schoolClass() {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Retorna o ano letivo da matrícula.
     *
     * @return mixed
     */
    public function schoolYear() {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Retorna as notas vinculadas à matrícula.
     *
     * @return mixed
     */
    public function grades() {
        return $this->hasMany(Grade::class);
    }

    /**
     * Retorna os logs de auditoria da matrícula.
     *
     * @return mixed
     */
    public function logs() {
        return $this->hasMany(EnrollmentLog::class)->orderByDesc('created_at');
    }

    /**
     * Retorna os documentos da matrícula.
     *
     * @return mixed
     */
    public function documents() {
        return $this->hasMany(EnrollmentDocument::class)->orderBy('tipo');
    }

    /**
     * Retorna a matrícula anterior usada como origem da rematrícula ou transferência interna.
     *
     * @return mixed
     */
    public function previousEnrollment() {
        return $this->belongsTo(Enrollment::class, 'previous_enrollment_id');
    }

    /**
     * Retorna o operador responsável pela última ação na matrícula.
     *
     * @return mixed
     */
    public function operatedBy() {
        return $this->belongsTo(User::class, 'operated_by_user_id');
    }

    /**
     * Retorna o próximo número de chamada disponível dentro da turma.
     *
     * @param int $classId
     *
     * @return int
     */
    public static function nextRollNumberFor(int $classId): int {
        $max = static::where('class_id', $classId)->max('roll_number');

        return (int) $max + 1;
    }

    /**
     * Gera o número de matrícula pelo parâmetro "enrollment.number_format".
     *
     * {year} é o ano letivo e {sequence} é o ID da matrícula com seis dígitos,
     * o que garante números únicos. Ex.: MAT-{year}-{sequence} => MAT-2026-000123.
     *
     * @param self $enr
     *
     * @return string
     */
    public static function generateRegistrationNumber(self $enr): string {
        $year = $enr->schoolYear?->year
            ?? SchoolYear::where('id', $enr->school_year_id)->value('year')
            ?? now()->year;

        $format = SystemParameter::read('enrollment.number_format', self::DEFAULT_NUMBER_FORMAT);

        if (!is_string($format) || !str_contains($format, '{sequence}')) {
            $format = self::DEFAULT_NUMBER_FORMAT;
        }

        return mb_substr(strtr(trim($format), [
            '{year}'     => (string) $year,
            '{sequence}' => str_pad((string) $enr->id, 6, '0', STR_PAD_LEFT),
        ]), 0, 20);
    }

    /**
     * Define valores padrão e registra auditoria durante o ciclo de vida da matrícula.
     *
     * @return void
     */
    protected static function booted() {
        static::creating(function (self $enr) {
            if (empty($enr->enrollment_date)) {
                $enr->enrollment_date = now();
            }
            if (empty($enr->roll_number)) {
                $enr->roll_number = self::nextRollNumberFor((int) $enr->class_id);
            }

            /* Herda school_year_id da turma se não informado. */
            if (empty($enr->school_year_id) && $enr->class_id) {
                $enr->school_year_id = SchoolClass::find($enr->class_id)?->school_year_id;
            }
        });

        /* registration_number só pode ser gerado após ter o ID (no created) */
        static::created(function (self $enr) {
            if (empty($enr->registration_number)) {
                $enr->updateQuietly([
                    'registration_number' => self::generateRegistrationNumber($enr),
                ]);
            }

            /* Registra log de criação automaticamente. */
            EnrollmentLog::registrar(
                enrollment: $enr,
                acao: 'criacao',
                statusNovo: $enr->status?->value,
            );
        });

        /* Impede alteração do registration_number após criação. */
        static::updating(function (self $enr) {
            if ($enr->isDirty('registration_number') && $enr->getOriginal('registration_number')) {
                $enr->registration_number = $enr->getOriginal('registration_number');
            }
        });
    }
}
