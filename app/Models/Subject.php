<?php

namespace App\Models;

use App\Enums\SubjectCategory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends BaseModel
{
    use SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'normalized_code',
        'name',
        'category',
        'description',
        'status',
        'bncc_code',
        'bncc_reference_url',
        'tags',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category' => SubjectCategory::class,
        'tags'     => 'array',
    ];

    /**
     * Retorna os níveis/séries que utilizam a disciplina.
     */
    public function gradeLevels()
    {
        return $this->belongsToMany(GradeLevel::class)
            ->withPivot('hours_weekly')
            ->withTimestamps();
    }

    /**
     * Retorna as turmas que possuem a disciplina na grade.
     */
    public function schoolClasses()
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'class_subjects',  // mesma pivot usada em SchoolClass::subjects()
            'subject_id',      // FK de Subject na pivot
            'class_id',        // FK de SchoolClass na pivot
        )->withTimestamps();
    }

    /**
     * Retorna os professores vinculados à disciplina por atribuição.
     */
    public function teachers()
    {
        // Se quiser manter um many-to-many, aponte para teacher_assignments:
        return $this->belongsToMany(Teacher::class, 'teacher_assignments', 'subject_id', 'teacher_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    /**
     * Retorna as turmas vinculadas por atribuições de professor.
     */
    public function classesByAssignments()
    {
        return $this->belongsToMany(
            SchoolClass::class,
            'teacher_assignments',
            'subject_id',
            'class_id'
        )->withPivot('teacher_id')->withTimestamps();
    }

    /**
     * Retorna as atribuições de professores da disciplina.
     */
    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'subject_id');
    }

    /**
     * Filtra disciplinas ativas.
     */
    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    /**
     * Filtra disciplinas inativas.
     */
    public function scopeInactive($q)
    {
        return $q->where('status', 'inactive');
    }

    /**
     * Retorna o rótulo legível da categoria.
     */
    public function getCategoryLabelAttribute(): string
    {
        return $this->category?->label() ?? '—';
    }

    /**
     * Normaliza o código da disciplina e seu índice de busca.
     */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code']            = $value ? strtoupper(trim($value)) : null;
        $this->attributes['normalized_code'] = $value
            ? preg_replace('/[^A-Z0-9]/', '', strtoupper($value))
            : null;
    }

    /**
     * Retorna as turmas vinculadas diretamente pela tabela de disciplinas da turma.
     */
    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subjects', 'subject_id', 'class_id')
            ->withTimestamps();
    }
}
