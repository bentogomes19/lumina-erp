<?php

namespace App\Models;

use App\Enums\SubjectCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'code', 'category'];

    protected $casts = [
        'category' => SubjectCategory::class,
    ];

    public function gradeLevels()
    {
        return $this->belongsToMany(GradeLevel::class)
            ->withPivot('hours_weekly')
            ->withTimestamps();
    }
    public function teachers() {
        return $this->belongsToMany(Teacher::class, 'class_subject_teacher');
    }

    public function classes() {
        return $this->belongsToMany(SchoolClass::class, 'class_subject_teacher');
    }
}
