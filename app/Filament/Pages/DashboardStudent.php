<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StudentProfileWidget;
use App\Filament\Widgets\StudentGradesStatsWidget;
use App\Filament\Widgets\StudentAttendanceStatsWidget;
use App\Filament\Widgets\UpcomingAssessments;
use Filament\Pages\Page;

class DashboardStudent extends Page
{
    protected static ?string $navigationLabel = 'Painel do Aluno';
    protected static ?string $title = 'Portal do Aluno';
    protected static ?string $slug = 'dashboard-student';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('student');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.dashboard-student';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StudentProfileWidget::class,
            StudentGradesStatsWidget::class,
            StudentAttendanceStatsWidget::class,
            UpcomingAssessments::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}
