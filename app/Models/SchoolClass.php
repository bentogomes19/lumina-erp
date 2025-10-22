<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'uuid', 'name', 'grade', 'shift', 'homeroom_teacher_id', 'capacity', 'status'
    ];

    public function homeroomTeacher() {
        return $this->belongsTo(Teacher::class, 'homeroom_teacher_id');
    }

    public function students() {
        return $this->belongsToMany(Student::class, 'enrollments');
    }

    public function subjects() {
        return $this->belongsToMany(Subject::class, 'class_subject_teacher');
    }
}
