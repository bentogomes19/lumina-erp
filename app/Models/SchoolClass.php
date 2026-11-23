<?php

namespace App\Models;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\ClassType;
use App\Services\SchoolClassCodeGenerator;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends BaseModel {

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
    /**
     * Coluna virtual do índice único de turmas ativas; não faz parte dos dados da turma.
     *
     * @var array<int, string>
     */
    protected $hidden = ['active_name_key'];

    protected $casts = [
        'shift'  => ClassShift::class,
        'status' => ClassStatus::class,
        'type'   => ClassType::class,
    ];

    /**
     * Mensagem exibida quando alguém tenta excluir uma turma aberta.
     */
    public const DELETE_OPEN_MESSAGE = 'Turma aberta não pode ser excluída. Altere o status para Fechada ou Arquivada antes de excluir.';

    protected static function booted(): void {
        /*
         * Turma aberta não pode ser excluída: primeiro ela precisa ser fechada ou arquivada.
         * Uma turma já excluída (na lixeira) continua podendo ser excluída definitivamente.
         */
        static::deleting(function (self $schoolClass): bool {
            return !($schoolClass->isOpen() && !$schoolClass->trashed());
        });

        static::creating(function (self $schoolClass): void {
            $schoolClass->fillUuidIfMissing();

            /* Gera o código pelo parâmetro "class.number_format" quando não for informado. */
            if (blank($schoolClass->code)
                && ($code = app(SchoolClassCodeGenerator::class)->generate($schoolClass)) !== null) {
                $schoolClass->code = $code;
            }
        });
    }

    /**
     * Indica se a turma está com status Aberta.
     *
     * @return bool
     */
    public function isOpen(): bool {
        $status = $this->status instanceof ClassStatus ? $this->status : ClassStatus::tryFrom((string) $this->status);

        return $status === ClassStatus::OPEN;
    }

    /**
     * Indica se já existe outra turma ativa (não excluída) com o mesmo nome, série, ano letivo e turno.
     *
     * É a mesma regra do índice uniq_active_class_year_grade_name_shift; usada antes de restaurar uma turma.
     *
     * @return bool
     */
    public function hasActiveDuplicate(): bool {
        $shift = $this->shift instanceof ClassShift ? $this->shift->value : $this->shift;

        return static::query()
            ->whereKeyNot($this->getKey())
            ->where('school_year_id', $this->school_year_id)
            ->where('grade_level_id', $this->grade_level_id)
            ->where('name', $this->name)
            ->where('shift', $shift)
            ->exists();
    }

    /**
     * Filtra turmas abertas.
     *
     * @param mixed $q
     *
     * @return mixed
     */
    public function scopeActive($q) {
        return $q->where('status', ClassStatus::OPEN);
    }

    /**
     * Filtra turmas por ano letivo.
     *
     * @param mixed $q
     * @param int $yearId
     *
     * @return mixed
     */
    public function scopeByYear($q, int $yearId) {
        return $q->where('school_year_id', $yearId);
    }

    /**
     * Filtra turmas por turno.
     *
     * @param mixed $q
     * @param ClassShift $shift
     *
     * @return mixed
     */
    public function scopeByShift($q, ClassShift $shift) {
        return $q->where('shift', $shift->value);
    }

    /**
     * Retorna o professor responsável pela turma.
     *
     * @return mixed
     */
    public function homeroomTeacher() {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    /**
     * Retorna os alunos matriculados na turma.
     *
     * @return mixed
     */
    public function students() {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id')
            ->withPivot(['enrollment_date', 'roll_number', 'status'])
            ->withTimestamps();
    }

    /**
     * Retorna as matrículas vinculadas diretamente à turma.
     *
     * @return mixed
     */
    public function enrollments() {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /**
     * Retorna as disciplinas vinculadas por atribuições de professor.
     *
     * @return mixed
     */
    public function subjectsByAssignments() {
        return $this->belongsToMany(
            Subject::class,
            'teacher_assignments',
            'class_id',
            'subject_id'
        )->withPivot('teacher_id')->withTimestamps();
    }

    /**
     * Retorna o ano letivo da turma.
     *
     * @return mixed
     */
    public function schoolYear() {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Retorna o nível/série da turma.
     *
     * @return mixed
     */
    public function gradeLevel() {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Retorna as atribuições de professores da turma.
     *
     * @return mixed
     */
    public function teacherAssignments() {
        return $this->hasMany(TeacherAssignment::class, 'class_id');
    }

    /**
     * Retorna os professores atribuídos à turma.
     *
     * @return mixed
     */
    public function teachers() {
        return $this->belongsToMany(Teacher::class, 'teacher_assignments', 'class_id', 'teacher_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    /**
     * Retorna as disciplinas vinculadas diretamente à turma.
     *
     * @return mixed
     */
    public function subjects() {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_id', 'subject_id')
            ->withTimestamps();
    }
}
