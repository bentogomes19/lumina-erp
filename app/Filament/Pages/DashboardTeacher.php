<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MyClassesTable;
use App\Filament\Widgets\RecentAttendanceTeacher;
use App\Filament\Widgets\TeacherStats;
use App\Filament\Widgets\UpcomingAssessments;
use Filament\Pages\Page;

class DashboardTeacher extends Page
{
    protected static ?string $navigationLabel = 'Painel do Professor';
    protected static ?string $title = 'Painel do Professor';
    protected static ?string $slug = 'dashboard-teacher';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('teacher');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('teacher') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.dashboard-teacher';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherStats::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            MyClassesTable::class,
            UpcomingAssessments::class,
            RecentAttendanceTeacher::class,
        ];
    }
}
