<?php

namespace App\Filament\Resources\GradeLevels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GradeLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome da Série/Etapa')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('order')
                    ->label('Ordem de Exibição')
                    ->numeric()
                    ->default(1),
            ]);
    }
}
