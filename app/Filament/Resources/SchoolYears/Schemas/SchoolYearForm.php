<?php

namespace App\Filament\Resources\SchoolYears\Schemas;

use App\Enums\SchoolYearStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SchoolYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('year')
                    ->label('Ano Letivo')
                    ->required()
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100),

                Select::make('status')
                    ->label('Status')
                    ->options(SchoolYearStatus::toArray())
                    ->default(SchoolYearStatus::PLANNING->value)
                    ->required()
                    ->helperText('Somente um ano letivo pode estar "Ativo" por vez.'),

                DatePicker::make('starts_at')
                    ->label('Data de Início')
                    ->required(),

                DatePicker::make('ends_at')
                    ->label('Data de Encerramento')
                    ->required()
                    ->after('starts_at'),
            ]);
    }
}
