<?php

namespace App\Models;

use App\Enums\AssessmentType;
use App\Enums\Term;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id', 'student_id', 'class_id', 'subject_id', 'teacher_id',
        'term', 'assessment_type', 'sequence',
        'score', 'max_score', 'weight',
        'comment', 'date_recorded', 'posted_by', 'locked_at', 'origin', 'recovery_of_id',
    ];

    protected $casts = [
        'term' => Term::class,
        'assessment_type' => AssessmentType::class,
        'date_recorded' => 'date',
        'locked_at' => 'datetime',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    public function recoveryOf()
    {
        return $this->belongsTo(Grade::class, 'recovery_of_id');
    }

    // helpers
    public function getPercentAttribute(): ?float {
        if (!$this->max_score || $this->max_score == 0) return null;
        return round(($this->score / $this->max_score) * 100, 2);
    }

    public function scopeOfTeacher($q, int $teacherId){
        return $q->where('teacher_id',$teacherId);
    }
}
