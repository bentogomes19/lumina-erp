<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends BaseModel
{
    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'class_id',
        'subject_id',
        'title',
        'scheduled_at',
        'weight',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
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
     * Retorna o professor responsável pela avaliação.
     *
     * @return BelongsTo
     */
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
