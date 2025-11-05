<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    protected static function booted()
    {
        // Quando criar um vínculo professor+turma+disciplina,
        // garante que a disciplina esteja anexada à turma (class_subjects)
        static::created(function (TeacherAssignment $assignment) {
            $class = $assignment->schoolClass;
            if (! $class) {
                return;
            }

            // se a disciplina ainda não está na turma, anexa
            $already = $class->subjects()
                ->where('subjects.id', $assignment->subject_id)
                ->exists();

            if (! $already) {
                $class->subjects()->attach($assignment->subject_id);
            }
        });

        // Quando apagar um vínculo, remove a disciplina da turma
        // se não houver mais nenhum professor lecionando essa disciplina na turma
        static::deleted(function (TeacherAssignment $assignment) {
            $class = $assignment->schoolClass;
            if (! $class) {
                return;
            }

            $stillUsed = $class->teacherAssignments()
                ->where('subject_id', $assignment->subject_id)
                ->exists();

            if (! $stillUsed) {
                $class->subjects()->detach($assignment->subject_id);
            }
        });
    }
}
