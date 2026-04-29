<?php

namespace App\Models;

use App\Enums\TermType;

class SchoolYearTerm extends BaseModel
{
    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_year_id',
        'name',
        'type',
        'sequence',
        'starts_at',
        'ends_at',
        'grade_entry_starts_at',
        'grade_entry_ends_at',
        'grades_published',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type'                  => TermType::class,
        'starts_at'             => 'date',
        'ends_at'               => 'date',
        'grade_entry_starts_at' => 'date',
        'grade_entry_ends_at'   => 'date',
        'grades_published'      => 'boolean',
    ];

    /**
     * Retorna o ano letivo ao qual o período pertence.
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Verifica se a data atual está dentro do período de lançamento de notas.
     */
    public function isGradeEntryOpen(): bool
    {
        return $this->grade_entry_starts_at?->lte(now())
            && $this->grade_entry_ends_at?->gte(now());
    }
}
