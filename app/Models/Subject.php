<?php

namespace App\Models;

use App\Enums\SubjectCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'normalized_code', 'name', 'category', 'description',
        'status', 'bncc_code', 'bncc_reference_url', 'tags',
    ];

    protected $casts = [
        'category' => SubjectCategory::class,
        'tags' => 'array',
    ];

    // Relationships
    public function gradeLevels()
    {
        return $this->belongsToMany(GradeLevel::class)
            ->withPivot('hours_weekly')
            ->withTimestamps();
    }

    public function teachers()
    {
        // Se quiser manter um many-to-many, aponte para teacher_assignments:
        return $this->belongsToMany(\App\Models\Teacher::class, 'teacher_assignments', 'subject_id', 'teacher_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    public function classesByAssignments()
    {
        return $this->belongsToMany(
            \App\Models\SchoolClass::class,
            'teacher_assignments',
            'subject_id',
            'class_id'
        )->withPivot('teacher_id')->withTimestamps();
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'subject_id');
    }

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopeInactive($q)
    {
        return $q->where('status', 'inactive');
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return $this->category?->label() ?? '—';
    }

    // Mutators — assegura código e índice normalizado
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = $value ? strtoupper(trim($value)) : null;
        $this->attributes['normalized_code'] = $value
            ? preg_replace('/[^A-Z0-9]/', '', strtoupper($value))
            : null;
    }

    public function classes()
    {
        return $this->belongsToMany(\App\Models\SchoolClass::class, 'class_subjects', 'subject_id', 'class_id')
            ->withTimestamps();
    }
}
