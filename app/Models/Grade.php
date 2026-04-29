<?php

namespace App\Models;

use App\Enums\AssessmentType;
use App\Enums\Term;

class Grade extends BaseModel
{
    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'enrollment_id',
        'student_id',
        'class_id',
        'subject_id',
        'teacher_id',
        'term',
        'assessment_type',
        'sequence',
        'score',
        'max_score',
        'weight',
        'comment',
        'date_recorded',
        'posted_by',
        'locked_at',
        'origin',
        'recovery_of_id',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'term'            => Term::class,
        'assessment_type' => AssessmentType::class,
        'date_recorded'   => 'date',
        'locked_at'       => 'datetime',
    ];

    /**
     * Retorna a turma vinculada à nota.
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Retorna a disciplina vinculada à nota.
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Retorna a matrícula vinculada à nota.
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Retorna o aluno vinculado à nota.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Retorna a nota de origem quando esta nota é recuperação.
     */
    public function recoveryOf()
    {
        return $this->belongsTo(Grade::class, 'recovery_of_id');
    }

    /**
     * Retorna o percentual obtido em relação à nota máxima.
     */
    public function getPercentAttribute(): ?float
    {
        if (! $this->max_score || $this->max_score == 0) {
            return null;
        }

        return round(($this->score / $this->max_score) * 100, 2);
    }

    /**
     * Preenche automaticamente o aluno com base na matrícula informada.
     */
    protected static function booted()
    {
        static::creating(function (Grade $grade) {
            if ($grade->enrollment_id && ! $grade->student_id) {
                $enrollment = Enrollment::find($grade->enrollment_id);

                if ($enrollment) {
                    $grade->student_id = $enrollment->student_id;
                }
            }
        });

        static::updating(function (Grade $grade) {
            if ($grade->enrollment_id && ! $grade->student_id) {
                $enrollment = Enrollment::find($grade->enrollment_id);

                if ($enrollment) {
                    $grade->student_id = $enrollment->student_id;
                }
            }
        });
    }
}
