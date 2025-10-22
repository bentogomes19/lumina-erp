<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Início';
    protected static ?string $title = 'Painel do Aluno';
    protected static ?string $slug = 'dashboard';
    protected string $view = 'filament.student.pages.dashboard';
}
