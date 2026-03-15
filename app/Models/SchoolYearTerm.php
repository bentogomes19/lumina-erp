<?php

namespace App\Models;

use App\Enums\TermType;
use Illuminate\Database\Eloquent\Model;

class SchoolYearTerm extends Model
{
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

    protected $casts = [
        'type'                  => TermType::class,
        'starts_at'             => 'date',
        'ends_at'               => 'date',
        'grade_entry_starts_at' => 'date',
        'grade_entry_ends_at'   => 'date',
        'grades_published'      => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /** Verifica se hoje está dentro do período de lançamento de notas */
    public function isGradeEntryOpen(): bool
    {
        $today = now()->toDateString();
        return $this->grade_entry_starts_at?->lte(now())
            && $this->grade_entry_ends_at?->gte(now());
    }
}
