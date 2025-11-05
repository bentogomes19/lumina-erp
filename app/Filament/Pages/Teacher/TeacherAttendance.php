<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Widgets\TeacherAttendanceWidget;
use Filament\Pages\Page;

class TeacherAttendance extends Page
{
    protected static ?string $navigationLabel = 'Lançar Faltas';
    protected static ?string $title = 'Lançar Faltas';
    protected static ?string $slug  = 'lancar-faltas';
    protected static string|null|\BackedEnum $navigationIcon  = 'heroicon-o-clipboard-document-check';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('teacher');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherAttendanceWidget::class,
        ];
    }
}
