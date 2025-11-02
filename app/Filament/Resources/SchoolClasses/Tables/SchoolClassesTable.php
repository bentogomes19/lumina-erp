<?php

namespace App\Filament\Resources\SchoolClasses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SchoolClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Turma'),
                TextColumn::make('gradeLevel.name')->label('Série'),
                TextColumn::make('schoolYear.year')->label('Ano Letivo'),
                TextColumn::make('shift')->label('Turno')->formatStateUsing(fn ($state) => [
                    'morning' => 'Manhã', 'afternoon' => 'Tarde', 'evening' => 'Noite'
                ][$state]),
                TextColumn::make('homeroomTeacher.name')->label('Professor Responsável'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
