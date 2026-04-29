<?php

namespace App\Models;

use App\Enums\HolidayType;
use Carbon\Carbon;

class SchoolHoliday extends BaseModel
{
    /**
     * Campos que podem ser preenchidos em massa pela aplicação.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_year_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'type',
        'is_active',
    ];

    /**
     * Conversões automáticas de tipos dos atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'type'       => HolidayType::class,
        'is_active'  => 'boolean',
    ];

    /**
     * Retorna o ano letivo vinculado ao feriado ou recesso.
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Filtra feriados e recessos ativos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra feriados e recessos por ano letivo.
     */
    public function scopeForYear($query, int $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

    /**
     * Filtra feriados e recessos que intersectam o período informado.
     */
    public function scopeInPeriod($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });
    }

    /**
     * Verifica se uma data é dia letivo.
     */
    public static function isSchoolDay(Carbon $date): bool
    {
        // Finais de semana não são dias letivos
        if ($date->isWeekend()) {
            return false;
        }

        // Verificar se existe feriado/recesso nesta data
        $holiday = static::active()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();

        return !$holiday;
    }

    /**
     * Retorna a lista de dias não letivos em um período.
     *
     * @return array<int, string>
     */
    public static function getNonSchoolDaysInPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $nonSchoolDays = [];

        // Dias de feriados/recessos
        $holidays = static::active()
            ->inPeriod($startDate, $endDate)
            ->get();

        foreach ($holidays as $holiday) {
            $current = $holiday->start_date->copy();
            while ($current->lte($holiday->end_date)) {
                $nonSchoolDays[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        // Adicionar finais de semana
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            if ($current->isWeekend()) {
                $nonSchoolDays[] = $current->format('Y-m-d');
            }
            $current->addDay();
        }

        return array_unique($nonSchoolDays);
    }

    /**
     * Retorna os próximos feriados ou recessos ativos.
     */
    public static function getUpcoming(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Conta os dias letivos em um período.
     */
    public static function countSchoolDaysInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        $totalDays = (int) $startDate->diffInDays($endDate) + 1;
        $nonSchoolDays = count(static::getNonSchoolDaysInPeriod($startDate, $endDate));
        
        return $totalDays - $nonSchoolDays;
    }

    /**
     * Indica se o feriado ou recesso ainda está vigente ou futuro.
     */
    public function isUpcoming(): bool
    {
        return $this->end_date->isFuture() || $this->end_date->isToday();
    }

    /**
     * Indica se o feriado ou recesso já passou.
     */
    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * Retorna a duração do feriado ou recesso em dias.
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Retorna o período formatado para exibição.
     */
    public function getFormattedPeriod(): string
    {
        if ($this->start_date->eq($this->end_date)) {
            return $this->start_date->format('d/m/Y');
        }

        return sprintf(
            '%s a %s',
            $this->start_date->format('d/m/Y'),
            $this->end_date->format('d/m/Y')
        );
    }
}
