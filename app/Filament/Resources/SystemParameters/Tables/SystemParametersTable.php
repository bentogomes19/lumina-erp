<?php

namespace App\Filament\Resources\SystemParameters\Tables;

use App\Enums\SystemParameterType;
use App\Filament\Resources\SystemParameters\Schemas\SystemParameterForm;
use App\Models\SystemParameter;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SystemParametersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Parametrização')->searchable()->sortable(),
            TextColumn::make('category')->label('Categoria')->badge()->searchable(),
            TextColumn::make('type')->label('Tipo')->formatStateUsing(fn ($state): string => $state instanceof SystemParameterType ? $state->label() : (string) $state),
            TextColumn::make('value')->label('Valor')->limit(45)->toggleable(),
            IconColumn::make('is_active')->label('Ativa')->boolean(),
            TextColumn::make('updated_at')->label('Atualizada')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('category')->label('Categoria')->options(fn (): array => SystemParameter::query()->distinct()->orderBy('category')->pluck('category', 'category')->all()),
            SelectFilter::make('is_active')->label('Situação')->options([1 => 'Ativas', 0 => 'Inativas']),
        ])->recordActions([
            EditAction::make()
                ->form(SystemParameterForm::fields())
                ->mutateRecordDataUsing(fn (array $data): array => SystemParameterForm::presentData($data))
                ->mutateDataUsing(fn (array $data): array => SystemParameterForm::normalizeData($data))
                ->modalHeading(fn (SystemParameter $record): string => 'Editar '.$record->name)
                ->modalDescription(fn (SystemParameter $record): string => $record->explanation())
                ->modalWidth('3xl')
                ->slideOver(),
        ])->recordUrl(null)->recordAction('edit')->defaultSort('category');
    }
}
