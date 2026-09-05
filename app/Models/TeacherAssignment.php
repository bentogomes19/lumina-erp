<?php

namespace App\Models;

use App\Support\TeacherAssignmentCurriculum;

class TeacherAssignment extends BaseModel {

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'curriculum_exception',
        'curriculum_exception_justification',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'curriculum_exception' => 'boolean',
    ];

    /**
     * Retorna o professor vinculado à atribuição.
     *
     * @return mixed
     */
    public function teacher() {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Retorna a disciplina vinculada à atribuição.
     *
     * @return mixed
     */
    public function subject() {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Retorna a turma vinculada à atribuição.
     *
     * @return mixed
     */
    public function schoolClass() {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Sincroniza a disciplina da turma ao criar ou remover atribuições.
     *
     * @return void
     */
    protected static function booted() {

        /* Quando criar um vínculo professor + turma+ disciplina, garante que a disciplina esteja anexada à turma. */
        static::created(function (TeacherAssignment $assignment) {
            $class = $assignment->schoolClass;
            if (!$class) {
                return;
            }

            /* se a disciplina ainda não está na turma, anexa. */
            $already = $class->subjects()
                ->where('subjects.id', $assignment->subject_id)
                ->exists();

            if (!$already) {
                $class->subjects()->attach($assignment->subject_id);
            }
        });

        /* Quando apagar um vínculo, remove apenas disciplinas adicionadas por exceção operacional. */
        static::deleted(function (TeacherAssignment $assignment) {
            $class = $assignment->schoolClass;
            if (!$class) {
                return;
            }

            if (TeacherAssignmentCurriculum::isCurricular((int) $class->id, (int) $assignment->subject_id)) {
                return;
            }

            $stillUsed = $class->teacherAssignments()
                ->where('subject_id', $assignment->subject_id)
                ->exists();

            if (!$stillUsed) {
                $class->subjects()->detach($assignment->subject_id);
            }
        });
    }
}
