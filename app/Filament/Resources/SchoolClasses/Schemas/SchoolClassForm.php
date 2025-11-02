<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use App\Models\SchoolYear;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome da Turma')
                    ->required(),

                Select::make('grade_level_id')
                    ->label('Série / Etapa')
                    ->relationship('gradeLevel', 'name')
                    ->required(),

                Select::make('school_year_id')
                    ->label('Ano Letivo')
                    ->options(SchoolYear::orderBy('year', 'desc')->pluck('year', 'id'))
                    ->default(SchoolYear::where('is_active', true)->value('id'))
                    ->required(),

                Select::make('shift')
                    ->label('Turno')
                    ->options([
                        'morning' => 'Manhã',
                        'afternoon' => 'Tarde',
                        'evening' => 'Noite',
                    ])
                    ->required(),

                Select::make('homeroom_teacher_id')
                    ->label('Professor Responsável')
                    ->relationship('homeroomTeacher', 'name')
                    ->searchable(),

                TextInput::make('capacity')
                    ->label('Capacidade Máxima')
                    ->numeric(),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Aberta',
                        'closed' => 'Fechada',
                        'archived' => 'Arquivada',
                    ])
                    ->required(),
            ]);
    }
}
