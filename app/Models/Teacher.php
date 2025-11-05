<?php

namespace App\Models;

use App\Enums\AcademicTitle;
use App\Enums\TeacherRegime;
use App\Enums\TeacherStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Teacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'employee_number',
        'name',
        'qualification',
        'academic_title',
        'hire_date',
        'admission_date',
        'termination_date',
        'regime',
        'weekly_workload',
        'max_classes',
        'email',
        'phone',
        'mobile',
        'cpf',
        'birth_date',
        'gender',
        'address_street', 'address_number', 'address_district', 'address_city', 'address_state', 'address_zip',
        'lattes_url',
        'bio',
        'status',
    ];

    protected $casts = [
        'academic_title' => AcademicTitle::class,
        'regime' => TeacherRegime::class,
        'status' => TeacherStatus::class,
        'hire_date' => 'date',
        'admission_date' => 'date',
        'termination_date' => 'date',
        'birth_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Teacher $model) {
            if (empty($model->uuid)) $model->uuid = (string)Str::uuid();
            if (empty($model->status)) $model->status = TeacherStatus::ACTIVE;
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'teacher_assignments', 'teacher_id', 'class_id')
            ->withPivot('subject_id')
            ->withTimestamps();
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'teacher_assignments', 'teacher_id', 'subject_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    public function remainingWeeklyWorkload(): ?int
    {
        return $this->weekly_workload;
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }

}
