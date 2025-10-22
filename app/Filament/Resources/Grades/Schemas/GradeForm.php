<?php

namespace App\Filament\Resources\Grades\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class GradeForm
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
                TextInput::make('subject_id')
                    ->required()
                    ->numeric(),
                TextInput::make('teacher_id')
                    ->numeric(),
                TextInput::make('score')
                    ->required()
                    ->numeric(),
                TextInput::make('max_score')
                    ->required()
                    ->numeric()
                    ->default(10.0),
                Textarea::make('comment')
                    ->columnSpanFull(),
                DatePicker::make('date_recorded'),
            ]);
    }
}
