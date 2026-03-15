<?php

namespace App\Filament\Resources\SchoolYears\Tables;

use App\Enums\SchoolYearStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchoolYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Ano')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('starts_at')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fim')
                    ->date('d/m/Y'),

                TextColumn::make('terms_count')
                    ->label('Períodos')
                    ->counts('terms')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SchoolYearStatus ? $state->label() : (string) $state)
                    ->color(fn ($state) => $state instanceof SchoolYearStatus ? $state->color() : 'gray'),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(SchoolYearStatus::toArray()),
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
