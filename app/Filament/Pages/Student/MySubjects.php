<?php

namespace App\Filament\Pages\Student;

use App\Models\Student;
use Filament\Pages\Page;

class MySubjects extends Page
{
    protected static ?string $navigationLabel = 'Minhas Disciplinas';
    protected static ?string $title = 'Minhas Disciplinas';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-book-open';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('student') ?? false;
    }

    public function getView(): string
    {
        return 'filament.pages.student.my-subjects';
    }

    public function getSubjects()
    {
        $student = Student::where('user_id', auth()->id())->first();
        if (! $student) return collect();

        $currentClass = $student->classes()
            ->whereHas('schoolYear', fn($q) => $q->where('is_active', true))
            ->with('subjects')
            ->first();

        return $currentClass?->subjects ?? collect();
    }
}
