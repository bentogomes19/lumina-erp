<?php

namespace App\Filament\Pages\Student;

use App\Models\Grade;
use Filament\Pages\Page;

class MyGrades extends Page
{
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Minhas Notas';
    protected static ?string $navigationLabel = 'Minhas Notas';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('student');
    }

    public function getGrades()
    {
        return Grade::where('student_id', auth()->user()->student->id)->get();
    }
}
