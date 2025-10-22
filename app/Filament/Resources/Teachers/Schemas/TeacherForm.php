<?php

namespace App\Filament\Resources\Teachers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('employee_number'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('qualification'),
                DatePicker::make('hire_date'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone')
                    ->tel(),
                Textarea::make('bio')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(['Ativo' => 'Ativo', 'Inativo' => 'Inativo'])
                    ->default('Ativo')
                    ->required(),
            ]);
    }
}
