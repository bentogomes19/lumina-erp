<?php

namespace App\Models;

use App\Enums\EducationStage;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeLevel extends BaseModel {

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'stage',
        'display_order',
        'description',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stage' => EducationStage::class,
    ];

    /**
     * Retorna as turmas associadas ao nível/série.
     *
     * @return HasMany
     */
    public function classes(): HasMany {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Retorna os anos letivos associados ao nível/série.
     *
     * @return HasMany
     */
    public function schoolYears(): HasMany {
        return $this->hasMany(SchoolYear::class);
    }

    /**
     * Ordena os níveis/séries pela ordem de exibição.
     *
     * @return self
     */
    public function scopeOrdered($query): self {
        return $query->orderBy('display_order');
    }

    /**
     * Retorna as disciplinas vinculadas ao nível/série.
     *
     * @return BelongsToMany
     */
    public function subjects(): BelongsToMany {
        return $this->belongsToMany(Subject::class)
            ->withPivot(['hours_weekly', 'syllabus', 'objectives', 'program_content'])
            ->withTimestamps();
    }
}
