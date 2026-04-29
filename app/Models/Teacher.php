<?php

namespace App\Models;

use App\Enums\AcademicTitle;
use App\Enums\TeacherRegime;
use App\Enums\TeacherStatus;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends BaseModel
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
        'employee_number',
        'name',
        'qualification',
        'academic_title',
        'hire_date',
        'admission_date',
        'termination_date',
        'regime',
        'weekly_workload',
        'max_classes',
        'email',
        'phone',
        'mobile',
        'cpf',
        'birth_date',
        'gender',
        'address_street',
        'address_number',
        'address_district',
        'address_city',
        'address_state',
        'address_zip',
        'lattes_url',
        'bio',
        'status',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'academic_title'   => AcademicTitle::class,
        'regime'           => TeacherRegime::class,
        'status'           => TeacherStatus::class,
        'hire_date'        => 'date',
        'admission_date'   => 'date',
        'termination_date' => 'date',
        'birth_date'       => 'date',
    ];

    /**
     * Define UUID e status padrão antes da criação do professor.
     */
    protected static function booted(): void
    {
        static::creating(function (Teacher $model) {
            $model->fillUuidIfMissing();
            if (empty($model->status)) {
                $model->status = TeacherStatus::ACTIVE;
            }
        });
    }

    /**
     * Retorna o usuário vinculado ao professor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna as turmas atribuídas ao professor.
     */
    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'teacher_assignments', 'teacher_id', 'class_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    /**
     * Retorna as disciplinas atribuídas ao professor.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments', 'teacher_id', 'subject_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    /**
     * Retorna a carga horária semanal restante do professor.
     */
    public function remainingWeeklyWorkload(): ?int
    {
        return $this->weekly_workload;
    }

    /**
     * Retorna as atribuições do professor.
     */
    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /**
     * Retorna as atribuições do professor.
     */
    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
