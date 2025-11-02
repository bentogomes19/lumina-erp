<?php

namespace App\Filament\Pages\Student;

use App\Models\Student;
use Filament\Pages\Page;

class MySubjects extends Page
{
    protected static ?string $navigationLabel = 'Minhas Disciplinas';
    protected static ?string $title = 'Minhas Disciplinas';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-book-open';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('student');
    }

    public function getSubjects()
    {
        return Student::where('user_id', auth()->id())
            ->first()
            ->class
            ->subjects;
    }
}
