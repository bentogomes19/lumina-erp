<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('school_year_id')
                    ->label('Ano Letivo')
                    ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year', 'id'))
                    ->default(fn () => SchoolYear::where('is_active', true)->value('id'))
                    ->live()
                    ->required(),

                Select::make('class_id')
                    ->label('Turma')
                    ->options(function (Get $get) {
                        $query = SchoolClass::query()->with('gradeLevel','schoolYear')->orderBy('name');
                        if ($get('school_year_id')) {
                            $query->where('school_year_id', $get('school_year_id'));
                        }
                        return $query->get()->mapWithKeys(fn ($c) => [
                            $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                        ]);
                    })
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('roll_number', Enrollment::nextRollNumberFor((int)$state));
                        }
                    }),

                Select::make('student_id')
                    ->label('Aluno')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Student::orderBy('name')->pluck('name','id'))
                    ->required()
                    ->helperText('Somente 1 matrícula por turma.')
                    ->rule(function (Get $get) {
                        return Rule::unique('enrollments', 'student_id')
                            ->where(fn($q) => $q->where('class_id', (int) $get('class_id')));
                    }),

                DatePicker::make('enrollment_date')
                    ->label('Data da Matrícula')
                    ->default(now())
                    ->required(),

                TextInput::make('roll_number')
                    ->label('Nº chamada')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Sugerido automaticamente, pode ajustar.'),

                Select::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::options())
                    ->default(EnrollmentStatus::ACTIVE->value)
                    ->required(),
            ]);
    }
}
