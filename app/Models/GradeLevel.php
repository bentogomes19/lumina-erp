<?php

namespace App\Models;

use App\Enums\EducationStage;

class GradeLevel extends BaseModel
{
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
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Retorna os anos letivos associados ao nível/série.
     */
    public function schoolYears()
    {
        return $this->hasMany(SchoolYear::class);
    }

    /**
     * Ordena os níveis/séries pela ordem de exibição.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    /**
     * Retorna as disciplinas vinculadas ao nível/série.
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class)
            ->withPivot('hours_weekly')
            ->withTimestamps();
    }
}
