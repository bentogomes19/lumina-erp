<?php

namespace App\Models;

use App\Enums\HolidayType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class SchoolHoliday extends Model
{
    protected $fillable = [
        'school_year_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'type',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'type' => HolidayType::class,
        'is_active' => 'boolean',
    ];

    // ========== RELATIONSHIPS ==========

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForYear($query, int $yearId)
    {
        return $query->where('school_year_id', $yearId);
    }

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

    // ========== STATIC METHODS ==========

    /**
     * Verificar se uma data é dia letivo (não é feriado/recesso)
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
     * Obter lista de dias não letivos em um período
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
     * Obter próximos feriados
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
     * Contar dias letivos em um período
     */
    public static function countSchoolDaysInPeriod(Carbon $startDate, Carbon $endDate): int
    {
        $totalDays = (int) $startDate->diffInDays($endDate) + 1;
        $nonSchoolDays = count(static::getNonSchoolDaysInPeriod($startDate, $endDate));
        
        return $totalDays - $nonSchoolDays;
    }

    // ========== HELPERS ==========

    /**
     * Verificar se o feriado está ativo (datas no futuro ou presente)
     */
    public function isUpcoming(): bool
    {
        return $this->end_date->isFuture() || $this->end_date->isToday();
    }

    /**
     * Verificar se o feriado já passou
     */
    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * Obter duração em dias
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Formatar período
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
