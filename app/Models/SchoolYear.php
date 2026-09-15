<?php

namespace App\Models;

use App\Enums\SchoolYearStatus;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class SchoolYear extends BaseModel
{
    use SoftDeletes;

    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'year',
        'starts_at',
        'ends_at',
        'is_active',
        'status',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'status' => SchoolYearStatus::class,
    ];

    /** Salva a ativação e a desativação anterior na mesma transação. */
    public function save(array $options = [])
    {
        if (! $this->status) {
            $this->status = $this->is_active ? SchoolYearStatus::ACTIVE : SchoolYearStatus::PLANNING;
        }
        if ($this->status === SchoolYearStatus::ACTIVE && (int) $this->year < now()->year) {
            throw ValidationException::withMessages([
                'year' => 'Não é permitido ativar um ano letivo anterior ao ano atual ('.now()->year.').',
            ]);
        }
        $this->is_active = $this->status === SchoolYearStatus::ACTIVE;

        return $this->getConnection()->transaction(function () use ($options) {
            // Uma ordem única de bloqueio também protege trocas entre anos existentes.
            $this->newQuery()->orderBy('id')->lockForUpdate()->get(['id']);
            if ($this->is_active) {
                $this->newQuery()->where('id', '!=', $this->id ?? 0)
                    ->where(fn ($q) => $q->where('status', SchoolYearStatus::ACTIVE->value)->orWhere('is_active', true))
                    ->update(['status' => SchoolYearStatus::CLOSED->value, 'is_active' => false]);
            }

            return parent::save($options);
        });
    }

    /**
     * Retorna os períodos avaliativos do ano letivo.
     *
     * @return mixed
     */
    public function terms()
    {
        return $this->hasMany(SchoolYearTerm::class)->orderBy('sequence');
    }

    /**
     * Retorna o nível/série associado ao ano letivo.
     *
     * @return mixed
     */
    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    /**
     * Retorna as turmas vinculadas ao ano letivo.
     *
     * @return mixed
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    /**
     * Retorna as matrículas vinculadas ao ano letivo.
     *
     * @return mixed
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Retorna o ano letivo com status ativo.
     */
    public static function current(): ?self
    {
        return static::where('status', SchoolYearStatus::ACTIVE->value)->first();
    }

    /**
     * Retorna o ano letivo ativo.
     *
     *
     * @deprecated Use current().
     */
    public static function active(): ?self
    {
        return static::current();
    }

    /**
     * Retorna o período avaliativo aberto para lançamento de notas hoje.
     */
    public function currentTerm(): ?SchoolYearTerm
    {
        return $this->terms()
            ->where('grade_entry_starts_at', '<=', now())
            ->where('grade_entry_ends_at', '>=', now())
            ->first();
    }
}
