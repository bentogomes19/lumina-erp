<?php

namespace App\Models;

use App\Enums\SchoolYearStatus;
use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = [
        'year',
        'starts_at',
        'ends_at',
        'is_active',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at'   => 'date',
        'is_active' => 'boolean',
        'status'    => SchoolYearStatus::class,
    ];

    /** Ao ativar um ano letivo, garante que nenhum outro fique ativo */
    protected static function booted(): void
    {
        static::saving(function (self $year) {
            if ($year->status === SchoolYearStatus::ACTIVE) {
                // Sincroniza is_active com status
                $year->is_active = true;

                // Desativa todos os outros
                static::where('id', '!=', $year->id)
                    ->where('status', SchoolYearStatus::ACTIVE->value)
                    ->update([
                        'status'    => SchoolYearStatus::PLANNING->value,
                        'is_active' => false,
                    ]);
            } else {
                $year->is_active = false;
            }
        });
    }

    public function terms()
    {
        return $this->hasMany(SchoolYearTerm::class)->orderBy('sequence');
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /** Retorna o ano letivo com status ativo */
    public static function current(): ?self
    {
        return static::where('status', SchoolYearStatus::ACTIVE->value)->first();
    }

    /** @deprecated Use current() */
    public static function active(): ?self
    {
        return static::current();
    }

    /** Retorna o período avaliativo aberto para lançamento de notas hoje */
    public function currentTerm(): ?SchoolYearTerm
    {
        return $this->terms()
            ->where('grade_entry_starts_at', '<=', now())
            ->where('grade_entry_ends_at', '>=', now())
            ->first();
    }
}
