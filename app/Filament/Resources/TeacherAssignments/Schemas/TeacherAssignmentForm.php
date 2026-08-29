<?php

namespace App\Filament\Resources\TeacherAssignments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class TeacherAssignmentForm {

    /**
     * Configura os campos do formulário de atribuições docentes.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                Select::make('school_class_id')
                    ->label('Turma')
                    ->relationship('schoolClass', 'name')
                    ->required(),

                Select::make('teacher_id')
                    ->label('Professor')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->required(),

                Select::make('subject_id')
                    ->label('Disciplina')
                    ->relationship('subject', 'name')
                    ->searchable()
                    ->required(),

            ]);
    }
}
