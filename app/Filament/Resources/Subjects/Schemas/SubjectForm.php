<?php

namespace App\Filament\Resources\Subjects\Schemas;

use App\Enums\SubjectCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código da Disciplina')
                    ->unique(ignoreRecord: true),

                TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                Textarea::make('description')
                    ->label('Descrição / Observações')
                    ->columnSpanFull(),

                Select::make('category')
                    ->label('Componente Curricular')
                    ->options(SubjectCategory::toArray())
                    ->required(),
            ]);
    }
}
