<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\ClassType;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SchoolClassForm {

    /**
     * Configura os campos do formulário de turmas.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Identificação')
                    ->description('Defina como a turma será identificada na secretaria e nas listas acadêmicas.')
                    ->icon('fas-id-card')
                    ->schema([
                    TextInput::make('code')
                        ->label('Código (Turma)')
                        ->placeholder('Ex.: 1A-MAN-2025')
                        ->maxLength(20)
                        ->helperText('Opcional')
                        ->columnSpan(['lg' => 4]),

                    TextInput::make('name')
                        ->label('Nome da Turma')
                        ->placeholder('Ex.: 1° ANO A')
                        ->required()
                        ->maxLength(80)
                        ->columnSpan(['lg' => 8]),
                ])
                    ->columns(12)
                    ->columnSpanFull(),

                Section::make('Contexto acadêmico')
                    ->description('Associe a turma ao período letivo e defina sua configuração de funcionamento.')
                    ->icon('fas-graduation-cap')
                    ->schema([
                    Select::make('grade_level_id')
                        ->label('Série / Etapa')
                        ->relationship('gradeLevel', 'name')
                        ->getOptionLabelFromRecordUsing(
                            fn ($record) => $record->name.' | '.($record->stage?->label() ?? strtoupper($record->stage?->value ?? ''))
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        ->columnSpan(2),

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
                ])
                    ->columns(6)
                    ->columnSpanFull(),

                Section::make('Responsável e capacidade')
                    ->description('Informe o professor responsável e o limite de alunos da turma.')
                    ->icon('fas-user-tie')
                    ->schema([
                    Select::make('homeroom_teacher_id')
                        ->label('Professor Responsável')
                        ->relationship('homeroomTeacher', 'name')
                        ->searchable()
                        ->preload()
                        ->placeholder('Selecione um professor'),

                    TextInput::make('capacity')
                        ->label('Capacidade Máxima')
                        ->placeholder('Sem limite')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(60)
                        ->helperText('Deixe vazio para uma turma ilimitada. Recomendado: 25 a 40 alunos.'),
                ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
