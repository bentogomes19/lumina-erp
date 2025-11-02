<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\ClassType;
use App\Models\GradeLevel;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Enum as EnumRule;
use App\Models\SchoolYear;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')->schema([
                    TextInput::make('code')
                        ->label('Código (Turma)')
                        ->maxLength(20)
                        ->helperText('Opcional. Ex.: 1A-MAN-2025'),

                    TextInput::make('name')
                        ->label('Nome da Turma')
                        ->required()
                        ->maxLength(80),
                ])->columns(2),

                Section::make('Contexto')->schema([
                    Select::make('grade_level_id')
                        ->label('Série / Etapa')
                        ->relationship('gradeLevel', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) =>
                            $record->name.' — '.($record->stage?->label() ?? strtoupper($record->stage?->value ?? ''))
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required(),

                    Select::make('school_year_id')
                        ->label('Ano Letivo')
                        ->relationship('schoolYear', 'year')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('shift')
                        ->label('Turno')
                        ->options(ClassShift::options())
                        ->required()
                        ->rule(new EnumRule(ClassShift::class)),

                    Select::make('type')
                        ->label('Tipo')
                        ->options(ClassType::options())
                        ->required()
                        ->rule(new EnumRule(ClassType::class))
                        ->default(ClassType::REGULAR->value),

                    Select::make('status')
                        ->label('Status')
                        ->options(ClassStatus::options())
                        ->required()
                        ->rule(new EnumRule(ClassStatus::class))
                        ->default(ClassStatus::OPEN->value),
                ])->columns(3),

                Section::make('Responsável e Capacidade')->schema([
                    Select::make('homeroom_teacher_id')
                        ->label('Professor Responsável')
                        ->relationship('homeroomTeacher', 'name')
                        ->searchable()
                        ->preload(),

                    TextInput::make('capacity')
                        ->label('Capacidade Máxima')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->helperText('Limite recomendado RM: 25~40 por turma, conforme etapa.'), // ajuste sua regra
                ])->columns(2),
            ]);
    }
}
