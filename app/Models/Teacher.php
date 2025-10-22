<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'employee_number',
        'name',
        'qualification',
        'hire_date',
        'email',
        'phone',
        'bio',
        'status',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function subjects() {
        return $this->belongsToMany(Subject::class, 'class_subject_teacher');
    }

    public function classes() {
        return $this->hasMany(SchoolClass::class, 'homeroom_teacher_id');
    }
}
