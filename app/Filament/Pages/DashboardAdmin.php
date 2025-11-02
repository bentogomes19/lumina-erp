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

class DashboardAdmin extends Page
{
    protected static ?string $navigationLabel = 'Painel do Administrador';
    protected static ?string $title = 'Painel do Administrador';
    protected static ?string $slug = 'dashboard-admin';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';


    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('admin');
    }
}
