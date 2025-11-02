<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DashboardTeacher extends Page
{
    protected static ?string $navigationLabel = 'Painel do Professor';
    protected static ?string $title = 'Painel do Professor';
    protected static ?string $slug = 'dashboard-teacher';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-academic-cap';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('teacher');
    }
}
