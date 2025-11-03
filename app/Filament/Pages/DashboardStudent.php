<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StudentProfileWidget;
use App\Filament\Widgets\UpcomingAssessments;
use Filament\Pages\Page;

class DashboardStudent extends Page
{
    protected static ?string $navigationLabel = 'Painel do Aluno';
    protected static ?string $title = 'Painel do Aluno';
    protected static ?string $slug = 'dashboard-student';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-academic-cap';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('student');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StudentProfileWidget::class,
            UpcomingAssessments::class,
        ];
    }

}
