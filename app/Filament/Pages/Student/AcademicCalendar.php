<?php

namespace App\Filament\Pages\Student;

use App\Models\SchoolYear;
use Filament\Pages\Page;

class AcademicCalendar extends Page
{
    protected static ?string $navigationLabel = 'Calendário';
    protected static ?string $title = 'Calendário Acadêmico';
    protected static ?string $slug = 'academic-calendar';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.student.academic-calendar';
    }

    public function getCalendarEvents(): array
    {
        $currentYear = SchoolYear::current();
        
        if (!$currentYear) {
            return [];
        }

        // Aqui você pode adicionar eventos do calendário
        return [
            [
                'title' => 'Início do Ano Letivo',
                'date' => $currentYear->starts_at,
                'type' => 'start',
                'description' => 'Primeiro dia de aula do ano letivo ' . $currentYear->year,
            ],
            [
                'title' => 'Fim do 1º Bimestre',
                'date' => $currentYear->starts_at?->addMonths(2)?->endOfMonth(),
                'type' => 'term-end',
                'description' => 'Encerramento do primeiro bimestre',
            ],
            [
                'title' => 'Fim do 2º Bimestre',
                'date' => $currentYear->starts_at?->addMonths(4)?->endOfMonth(),
                'type' => 'term-end',
                'description' => 'Encerramento do segundo bimestre',
            ],
            [
                'title' => 'Recesso Escolar',
                'date' => $currentYear->starts_at?->addMonths(5)?->startOfMonth(),
                'type' => 'holiday',
                'description' => 'Período de recesso escolar',
            ],
            [
                'title' => 'Fim do 3º Bimestre',
                'date' => $currentYear->starts_at?->addMonths(7)?->endOfMonth(),
                'type' => 'term-end',
                'description' => 'Encerramento do terceiro bimestre',
            ],
            [
                'title' => 'Fim do 4º Bimestre',
                'date' => $currentYear->ends_at,
                'type' => 'term-end',
                'description' => 'Encerramento do quarto bimestre',
            ],
            [
                'title' => 'Encerramento do Ano Letivo',
                'date' => $currentYear->ends_at,
                'type' => 'end',
                'description' => 'Último dia de aula do ano letivo ' . $currentYear->year,
            ],
        ];
    }

    public function getCurrentSchoolYear()
    {
        return SchoolYear::current();
    }
}
