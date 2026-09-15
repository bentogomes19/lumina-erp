<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Enums\SchoolYearStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends BaseModel {

    use SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'registration_number',
        'name',
        'birth_date',
        'gender',
        'cpf',
        'rg',
        'email',
        'phone_number',
        'address',
        'address_district',
        'city',
        'state',
        'postal_code',
        'birth_city',
        'birth_state',
        'nationality',
        'mother_name',
        'father_name',
        'guardian_main',
        'guardian_phone',
        'guardian_email',
        'transport_mode',
        'has_special_needs',
        'medical_notes',
        'allergies',
        'status',
        'status_changed_at',
        'enrollment_date',
        'exit_date',
        'photo_url',
        'meta',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date'        => 'date',
        'enrollment_date'   => 'date',
        'exit_date'         => 'date',
        'status_changed_at' => 'datetime',
        'meta'              => 'array',
        'status'            => StudentStatus::class,
        'gender'            => Gender::class,
        'has_special_needs' => 'bool',
    ];

    /**
     * Retorna o usuário vinculado ao aluno.
     *
     * @return mixed
     */
    public function user() {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna alunos relacionados pela tabela de matrículas.
     *
     * @return mixed
     */
    public function students() {
        return $this->belongsToMany(
            Student::class,
            'enrollments',
            'class_id',        /* FK desta model na pivot. */
            'student_id'       /* FK do model relacionado na pivot. */
        )
            ->withTimestamps();
    }

    /**
     * Retorna as matrículas do aluno.
     *
     * @return mixed
     */
    public function enrollments() {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Retorna somente a matrícula ativa do ano letivo vigente.
     *
     * O histórico continua disponível em enrollments(); esta relação é
     * específica para telas operacionais que precisam exibir a turma atual.
     */
    public function currentActiveEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)
            ->where('status', EnrollmentStatus::ACTIVE->value)
            ->whereHas('schoolYear', fn ($query) => $query->where('status', SchoolYearStatus::ACTIVE->value))
            ->latestOfMany();
    }

    /**
     * Retorna as turmas nas quais o aluno está matriculado.
     *
     * @return mixed
     */
    public function classes() {
        return $this->belongsToMany(
            SchoolClass::class,
            'enrollments',     /* Tabela intermediária. */
            'student_id',      /* Chave estrangeira deste modelo. */
            'class_id'         /* Chave estrangeira da turma relacionada. */
        )
            ->withTimestamps();
    }

    /**
     * Retorna as notas do aluno.
     *
     * @return mixed
     */
    public function grades() {
        return $this->hasMany(Grade::class);
    }

    /**
     * Define valores automáticos antes da criação do aluno.
     *
     * @return void
     */
    protected static function booted() {
        static::creating(function ($s) {
            $s->fillUuidIfMissing();

            /* Gera matrícula se não enviada. */
            if (empty($s->registration_number)) {
                $s->registration_number = self::generateRegistrationNumber();
            }
        });
    }

    /**
     * Gera um número de matrícula único para o aluno.
     *
     * @return string
     */
    public static function generateRegistrationNumber(): string {

        /* Formato: ALU-2025-000123 */
        do {
            $code = 'ALU-' . now()->format('Y') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('registration_number', $code)->exists());

        return $code;
    }

    /**
     * Filtra alunos ativos.
     *
     * @param mixed $q
     *
     * @return mixed
     */
    public function scopeActive($q) {
        return $q->where('status', StudentStatus::ACTIVE->value);
    }

    /**
     * Filtra alunos pelo ano de matrícula.
     *
     * @param mixed $q
     * @param int $year
     *
     * @return mixed
     */
    public function scopeOfYear($q, int $year) {
        return $q->whereYear('enrollment_date', $year);
    }

    /**
     * Retorna a idade calculada pela data de nascimento.
     *
     * @return int|null
     */
    public function getAgeAttribute(): ?int {
        if (!$this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }
}
