<?php

namespace App\Models;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\ClassType;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends BaseModel
{
    use SoftDeletes;

    protected $table = 'classes';

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'grade_level_id',
        'school_year_id',
        'shift',
        'type',
        'homeroom_teacher_id',
        'capacity',
        'status',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shift'  => ClassShift::class,
        'status' => ClassStatus::class,
        'type'   => ClassType::class,
    ];

    /**
     * Filtra turmas abertas.
     */
    public function scopeActive($q)
    {
        return $q->where('status', ClassStatus::OPEN);
    }

    /**
     * Filtra turmas por ano letivo.
     */
    public function scopeByYear($q, int $yearId)
    {
        return $q->where('school_year_id', $yearId);
    }

    /**
     * Filtra turmas por turno.
     */
    public function scopeByShift($q, ClassShift $shift)
    {
        return $q->where('shift', $shift->value);
    }

    /**
     * Retorna o professor responsável pela turma.
     */
    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    /**
     * Retorna os alunos matriculados na turma.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
            ->withPivot(['enrollment_date', 'roll_number', 'status'])
            ->withTimestamps();
    }

    /**
     * Retorna as disciplinas vinculadas por atribuições de professor.
     */
    public function subjectsByAssignments()
    {
        return $this->belongsToMany(
            Subject::class,
            'teacher_assignments',
            'class_id',
            'subject_id'
        )->withPivot('teacher_id')->withTimestamps();
    }

    /**
     * Retorna o ano letivo da turma.
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Retorna o nível/série da turma.
     */
    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Retorna as atribuições de professores da turma.
     */
    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'class_id');
    }

    /**
     * Retorna os professores atribuídos à turma.
     */
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_assignments', 'class_id', 'teacher_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    /**
     * Retorna as disciplinas vinculadas diretamente à turma.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withTimestamps();
    }
}
