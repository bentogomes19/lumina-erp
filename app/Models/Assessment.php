<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends BaseModel
{
    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'class_id',
        'subject_id',
        'school_year_id',
        'title',
        'description',
        'assessment_type',
        'date',
        'scheduled_at',
        'max_score',
        'weight',
        'status',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'scheduled_at' => 'datetime',
        'max_score'    => 'decimal:2',
        'weight'       => 'decimal:2',
    ];

    /**
     * Retorna a turma vinculada à avaliação.
     *
     * @return BelongsTo
     */
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    /**
     * Retorna a disciplina vinculada à avaliação.
     *
     * @return BelongsTo
     */
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Retorna o ano letivo da avaliação.
     */
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Retorna o professor responsável pela avaliação.
     *
     * @return BelongsTo
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Indica se a avaliação está fechada.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Limita a consulta ao professor informado.
     */
    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->where('teacher_id', $teacherId);
    }
}
