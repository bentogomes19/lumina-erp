<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStats;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class Dashboard extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Painel Lumina';
    protected static ?string $slug = 'dashboard';
    protected string $view = 'filament.pages.dashboard';


    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class,
        ];
    }
}
