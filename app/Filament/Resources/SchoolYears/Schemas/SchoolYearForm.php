<?php

namespace App\Filament\Resources\SchoolYears\Schemas;

use App\Models\SchoolYear;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

                DatePicker::make('starts_at')
                    ->label('Data de Início')
                    ->required(),

                DatePicker::make('ends_at')
                    ->label('Data de Encerramento')
                    ->required(),

                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(false)
                    ->helperText('Somente um ano letivo pode estar ativo.')
            ]);
    }
}
