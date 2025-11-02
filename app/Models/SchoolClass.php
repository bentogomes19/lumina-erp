<?php

namespace App\Models;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\ClassType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
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

    protected $casts = [
        'shift' => ClassShift::class,
        'status' => ClassStatus::class,
        'type' => ClassType::class,
    ];

    // Scopes úteis (RM-style)
    public function scopeActive($q)
    {
        return $q->where('status', ClassStatus::OPEN);
    }

    public function scopeByYear($q, int $yearId)
    {
        return $q->where('school_year_id', $yearId);
    }

    public function scopeByShift($q, ClassShift $shift)
    {
        return $q->where('shift', $shift->value);
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'enrollments', 'class_id', 'student_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subject_teacher');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

}
