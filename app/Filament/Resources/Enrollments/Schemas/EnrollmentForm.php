<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Identificação da matrícula (somente edição) ────────────────
                Section::make('Identificação')
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->visibleOn('edit')
                    ->schema([
                        TextInput::make('registration_number')
                            ->label('Nº Matrícula')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Gerado automaticamente. Imutável.')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(1),

                        Placeholder::make('student_name_preview')
                            ->label('Aluno')
                            ->content(fn ($record) => $record?->student?->name ?? '—')
                            ->columnSpan(2),
                    ]),

                // ── Dados do aluno ─────────────────────────────────────────────
                Section::make('Aluno')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Select::make('student_id')
                            ->label('Aluno')
                            ->searchable()
                            ->preload()
                            ->options(fn () => Student::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->disabledOn('edit')
                            ->helperText('Somente 1 matrícula ativa por turma por período letivo.')
                            ->rule(function (Get $get, $record) {
                                return Rule::unique('enrollments', 'student_id')
                                    ->where(fn ($q) => $q
                                        ->where('class_id', (int) $get('class_id'))
                                        ->whereIn('status', [
                                            EnrollmentStatus::ACTIVE->value,
                                            EnrollmentStatus::SUSPENDED->value,
                                            EnrollmentStatus::LOCKED->value,
                                        ])
                                    )
                                    ->ignore($record?->id);
                            })
                            ->columnSpanFull(),

                        Placeholder::make('student_cpf_preview')
                            ->label('CPF')
                            ->content(fn ($record) => $record?->student?->cpf ?? '—')
                            ->visibleOn('edit'),

                        Placeholder::make('student_birth_preview')
                            ->label('Data de Nascimento')
                            ->content(fn ($record) => $record?->student?->birth_date
                                ? $record->student->birth_date->format('d/m/Y')
                                : '—')
                            ->visibleOn('edit'),
                    ]),

                // ── Dados acadêmicos ───────────────────────────────────────────
                Section::make('Dados Acadêmicos')
                    ->icon('heroicon-o-academic-cap')
                    ->columns(2)
                    ->schema([
                        Select::make('school_year_id')
                            ->label('Ano Letivo')
                            ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year', 'id'))
                            ->default(fn () => SchoolYear::where('is_active', true)->value('id'))
                            ->live()
                            ->required()
                            ->prefixIcon('heroicon-o-calendar'),

                        Select::make('class_id')
                            ->label('Turma')
                            ->options(function (Get $get) {
                                $query = SchoolClass::query()->with('gradeLevel', 'schoolYear')->orderBy('name');
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
                                    $set('roll_number', Enrollment::nextRollNumberFor((int) $state));
                                }
                            })
                            ->prefixIcon('heroicon-o-user-group'),

                        DatePicker::make('enrollment_date')
                            ->label('Data da Matrícula')
                            ->default(now())
                            ->required()
                            ->prefixIcon('heroicon-o-calendar-days'),

                        TextInput::make('roll_number')
                            ->label('Nº Chamada')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('Sugerido automaticamente, pode ajustar.')
                            ->prefixIcon('heroicon-o-list-bullet'),
                    ]),

                // ── Status ─────────────────────────────────────────────────────
                Section::make('Status')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        Select::make('status')
                            ->label('Status da Matrícula')
                            ->options(EnrollmentStatus::options())
                            ->default(EnrollmentStatus::ACTIVE->value)
                            ->required()
                            ->helperText('Para trancamentos, transferências ou cancelamentos, use as ações específicas na barra superior da página de edição — elas registram o histórico automaticamente.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
