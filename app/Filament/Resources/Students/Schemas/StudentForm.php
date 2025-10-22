<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StudentForm
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
                TextInput::make('registration_number')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('birth_date'),
                TextInput::make('gender'),
                TextInput::make('cpf'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('phone_number')
                    ->tel(),
                TextInput::make('address'),
                TextInput::make('city'),
                TextInput::make('state'),
                TextInput::make('postal_code'),
                TextInput::make('mother_name'),
                TextInput::make('father_name'),
                Select::make('status')
                    ->options(['Ativo' => 'Ativo', 'Inativo' => 'Inativo', 'Suspenso' => 'Suspenso', 'Graduado' => 'Graduado'])
                    ->default('Ativo')
                    ->required(),
                DatePicker::make('enrollment_date'),
                DatePicker::make('exit_date'),
                TextInput::make('meta'),
            ]);
    }
}
