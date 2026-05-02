<?php

namespace App\Filament\Pages\Teacher;

use App\Filament\Widgets\TeacherAttendanceWidget;
use App\Support\PermissionAccess;
use Filament\Pages\Page;

class TeacherAttendance extends Page
{
    protected static ?string $navigationLabel = 'Lançar Faltas';
    protected static ?string $title = 'Lançar Faltas';
    protected static ?string $slug  = 'lancar-faltas';
    protected static string|null|\BackedEnum $navigationIcon  = 'fas-clipboard-check';

    public static function shouldRegisterNavigation(): bool
    {
        return PermissionAccess::can('teacher.attendance.create');
    }

    public static function canAccess(): bool
    {
        return PermissionAccess::can('teacher.attendance.create');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherAttendanceWidget::class,
        ];
    }
}
