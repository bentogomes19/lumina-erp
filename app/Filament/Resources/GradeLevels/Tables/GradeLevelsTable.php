<?php

namespace App\Filament\Resources\GradeLevels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GradeLevelsTable {

    /**
     * Configura as colunas, os filtros e as ações da tabela de níveis de ensino.
     *
     * @param Table $table
     *
     * @return Table
     */
    public static function configure(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nome')->sortable()->searchable(),
                TextColumn::make('stage')->label('Etapa')->formatStateUsing(fn ($state) => $state->label())->sortable(),
                TextColumn::make('display_order')->label('Ordem')->sortable(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
