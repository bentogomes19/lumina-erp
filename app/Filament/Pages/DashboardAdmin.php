<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardStats;
use Filament\Pages\Page;

class DashboardAdmin extends Page
{
    protected static ?string $navigationLabel = 'Painel do Administrador';
    protected static ?string $title = 'Painel do Administrador';
    protected static ?string $slug = 'dashboard-admin';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = 0;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.dashboard-admin';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardStats::class,
        ];
    }
        
        

    public function getHeaderWidgetsColumns(): int | array
    {
        return 2;
    }
}
