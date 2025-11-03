<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subjects';
    protected static ?string $title = 'Disciplinas da Turma';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Código')->toggleable()->copyable(),
                TextColumn::make('name')->label('Disciplina')->searchable()->sortable(),
                TextColumn::make('categoryLabel')->label('Componente')->badge()->toggleable(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Adicionar disciplina')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['code','name'])
                    ->recordTitleAttribute('name'),
            ])
            ->actions([
                DetachAction::make()->label('Remover'),
            ]);
    }
}
