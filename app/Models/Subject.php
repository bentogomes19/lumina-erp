<?php

namespace App\Models;

use App\Enums\SubjectCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends BaseModel {

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
     *
     * @return BelongsToMany
     */
    public function gradeLevels(): BelongsToMany {
        return $this->belongsToMany(GradeLevel::class)
            ->withPivot(['hours_weekly', 'syllabus', 'objectives', 'program_content'])
            ->withTimestamps();
    }

    /**
     * Retorna as turmas que possuem a disciplina na grade.
     *
     * @return BelongsToMany
     */
    public function schoolClasses() : BelongsToMany {
        return $this->belongsToMany(
            SchoolClass::class,
            'class_subjects',  // mesma pivot usada em SchoolClass::subjects()
            'subject_id',      // FK de Subject na pivot
            'class_id',        // FK de SchoolClass na pivot
        )->withTimestamps();
    }

    /**
     * Retorna os professores vinculados à disciplina por atribuição.
     *
     * @return BelongsToMany
     */
    public function teachers(): BelongsToMany {
        return $this->belongsToMany(Teacher::class, 'teacher_assignments', 'subject_id', 'teacher_id')
            ->withPivot('class_id')
            ->withTimestamps();
    }

    /**
     * Retorna as turmas vinculadas por atribuições de professor.
     *
     * @return BelongsToMany
     */
    public function classesByAssignments(): BelongsToMany {
        return $this->belongsToMany(SchoolClass::class, 'teacher_assignments', 'subject_id', 'class_id')
            ->withPivot('teacher_id')
            ->withTimestamps();
    }

    /**
     * Retorna as atribuições de professores da disciplina.
     *
     * @return HasMany
     */
    public function teacherAssignments(): HasMany {
        return $this->hasMany(TeacherAssignment::class, 'subject_id');
    }

    /**
     * Filtra disciplinas ativas.
     *
     * @param  Builder  $q
     * @return Builder
     */
    public function scopeActive(Builder $q): Builder {
        return $q->where('status', 'active');
    }

    /**
     * Filtra disciplinas inativas.
     *
     * @return Builder
     */
    public function scopeInactive($q) : Builder {
        return $q->where('status', 'inactive');
    }

    /**
     * Retorna o rótulo legível da categoria.
     *
     * @return string
     */
    public function getCategoryLabelAttribute(): string {
        return $this->category?->label() ?? '—';
    }

    /**
     * Normaliza o código da disciplina e seu índice de busca.
     *
     * @param  string  $value
     * @return void
     */
    public function setCodeAttribute(string $value): void {
        $this->attributes['code']            = $value ? strtoupper(trim($value)) : null;
        $this->attributes['normalized_code'] = $value  ? preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) : null;
    }

    /**
     * Retorna as turmas vinculadas diretamente pela tabela de disciplinas da turma.
     *
     * @return BelongsToMany
     */
    public function classes(): BelongsToMany {
        return $this->belongsToMany(SchoolClass::class, 'class_subjects', 'subject_id', 'class_id')
            ->withTimestamps();
    }
}
