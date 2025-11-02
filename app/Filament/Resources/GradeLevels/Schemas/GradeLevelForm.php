<?php

namespace App\Filament\Resources\GradeLevels\Schemas;

use App\Enums\EducationStage;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GradeLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->unique(ignoreRecord: true),

                Select::make('stage')
                    ->label('Etapa')
                    ->options(EducationStage::toArray())
                    ->default(EducationStage::FUND_I)
                    ->required(),

                TextInput::make('display_order')
                    ->label('Ordem de Exibição')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),

                Textarea::make('description')
                    ->label('Descrição / Observações')
                    ->rows(3)
                    ->nullable(),
            ]);
    }
}
