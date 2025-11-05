<?php

namespace App\Filament\Pages\Student;

use App\Filament\Widgets\StudentGradesWidget;
use App\Models\Grade;
use Filament\Pages\Page;

class MyGrades extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Minhas Notas';
    protected static ?string $navigationLabel = 'Minhas Notas';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StudentGradesWidget::class, // 👈 aqui entra o widget de notas
        ];
    }
}
