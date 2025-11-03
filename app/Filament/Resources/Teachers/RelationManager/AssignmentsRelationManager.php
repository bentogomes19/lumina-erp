<?php

namespace App\Filament\Resources\Teachers\RelationManager;

use App\Models\Subject;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherAssignments';
    protected static ?string $title = 'Turmas & Disciplinas';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('class_id')
                ->label('Turma')
                ->relationship('schoolClass', 'name') // via TeacherAssignment::schoolClass()
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            Select::make('subject_id')
                ->label('Disciplina')
                ->options(function (Get $get) {
                    $classId = $get('class_id');
                    if (!$classId) {
                        return \App\Models\Subject::orderBy('name')->pluck('name', 'id');
                    }

                    return \App\Models\Subject::query()
                        ->whereHas('teacherAssignments', fn ($q) => $q->where('class_id', $classId))
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schoolClass.name')->label('Turma')->searchable()->sortable(),
                TextColumn::make('schoolClass.gradeLevel.name')->label('Série/Ano')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('schoolClass.schoolYear.year')->label('Ano Letivo')->sortable(),
                TextColumn::make('subject.code')->label('Cód.')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject.name')->label('Disciplina')->searchable()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Vincular'),
            ])
            ->actions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Remover'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Remover selecionados'),
            ]);
    }
}
