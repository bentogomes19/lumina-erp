<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends BaseModel
{
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
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna alunos relacionados pela tabela de matrículas.
     */
    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'enrollments',
            'class_id',        // FK desta model na pivot
            'student_id'       // FK do model relacionado na pivot
        )
            ->withTimestamps();
    }

    /**
     * Retorna as matrículas do aluno.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Retorna as turmas nas quais o aluno está matriculado.
     */
    public function classes()
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'enrollments',     // tabela pivot
            'student_id',      // FK deste model na pivot
            'class_id'         // FK do model relacionado na pivot (não "school_class_id")
        )
            ->withTimestamps();
        // ->withPivot([...]) // se tiver colunas extras na pivot, liste aqui
    }

    /**
     * Retorna as notas do aluno.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * Define valores automáticos antes da criação do aluno.
     */
    protected static function booted()
    {
        static::creating(function ($s) {
            $s->fillUuidIfMissing();

            // Gera matrícula se não enviada
            if (empty($s->registration_number)) {
                $s->registration_number = self::generateRegistrationNumber();
            }
        });
    }

    /**
     * Gera um número de matrícula único para o aluno.
     */
    public static function generateRegistrationNumber(): string
    {
        // Formato: ALU-2025-000123 (evita colisão com loop)
        do {
            $code = 'ALU-' . now()->format('Y') . '-' . str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('registration_number', $code)->exists());

        return $code;
    }

    /**
     * Filtra alunos ativos.
     */
    public function scopeActive($q)
    {
        return $q->where('status', StudentStatus::ACTIVE->value);
    }

    /**
     * Filtra alunos pelo ano de matrícula.
     */
    public function scopeOfYear($q, int $year)
    {
        return $q->whereYear('enrollment_date', $year);
    }

    /**
     * Retorna a idade calculada pela data de nascimento.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }
}
