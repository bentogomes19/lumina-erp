<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                TextInput::make('class_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('enrollment_date'),
                TextInput::make('roll_number'),
                Select::make('status')
                    ->options([
            'Ativa' => 'Ativa',
            'Suspensa' => 'Suspensa',
            'Cancelada' => 'Cancelada',
            'Completa' => 'Completa',
        ])
                    ->default('Ativa')
                    ->required(),
            ]);
    }
}
