<?php

namespace App\Filament\Resources\SchoolClasses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SchoolClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('grade')
                    ->required(),
                Select::make('shift')
                    ->options(['morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'])
                    ->default('morning')
                    ->required(),
                TextInput::make('homeroom_teacher_id')
                    ->numeric(),
                TextInput::make('capacity')
                    ->numeric(),
                Select::make('status')
                    ->options(['open' => 'Open', 'closed' => 'Closed', 'archived' => 'Archived'])
                    ->default('open')
                    ->required(),
            ]);
    }
}
