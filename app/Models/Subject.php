<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'grade_level',
        'hours_period'
    ];

    public function teachers() {
        return $this->belongsToMany(Teacher::class, 'class_subject_teacher');
    }

    public function classes() {
        return $this->belongsToMany(SchoolClass::class, 'class_subject_teacher');
    }
}
