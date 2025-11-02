<?php

namespace App\Filament\Resources\SchoolClasses\Tables;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
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
                TextColumn::make('code')
                    ->label('Código')
                    ->toggleable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('gradeLevel.name')->label('Série')->searchable()->sortable(),

                TextColumn::make('schoolYear.year')->label('Ano Letivo')->sortable(),

                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassShift::MORNING => 'success',
                        ClassShift::AFTERNOON => 'warning',
                        ClassShift::EVENING => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassStatus::OPEN => 'success',
                        ClassStatus::CLOSED => 'danger',
                        ClassStatus::ARCHIVED => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('homeroomTeacher.name')->label('Professor Resp.')->toggleable(),

                TextColumn::make('capacity')->label('Cap.')->numeric()->alignRight()->toggleable(),
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
