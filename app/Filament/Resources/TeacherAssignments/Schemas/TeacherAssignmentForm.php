<?php

namespace App\Filament\Resources\TeacherAssignments\Schemas;

use App\Support\PermissionAccess;
use App\Support\TeacherAssignmentCurriculum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                Select::make('class_id')
                    ->label('Turma')
                    ->relationship('schoolClass', 'name')
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('subject_id', null))
                    ->required(),

                Select::make('teacher_id')
                    ->label('Professor')
                    ->relationship('teacher', 'name')
                    ->searchable()
                    ->required(),

                Select::make('subject_id')
                    ->label('Disciplina')
                    ->options(fn (Get $get) => TeacherAssignmentCurriculum::subjectOptions(
                        (int) $get('class_id'),
                        PermissionAccess::can('admin.teachers.assignments.curriculum_exception')
                            && (bool) $get('curriculum_exception'),
                    ))
                    ->searchable()
                    ->required(),

                Toggle::make('curriculum_exception')
                    ->label('Autorizar exceção curricular')
                    ->helperText('Permite escolher uma disciplina fora da matriz da série.')
                    ->live()
                    ->afterStateUpdated(fn (Set $set): mixed => $set('subject_id', null))
                    ->visible(fn (): bool => PermissionAccess::can('admin.teachers.assignments.curriculum_exception')),

                Textarea::make('curriculum_exception_justification')
                    ->label('Justificativa da exceção')
                    ->rows(3)
                    ->maxLength(2000)
                    ->required(fn (Get $get): bool => (bool) $get('curriculum_exception'))
                    ->visible(fn (Get $get): bool => PermissionAccess::can('admin.teachers.assignments.curriculum_exception')
                        && (bool) $get('curriculum_exception'))
                    ->columnSpanFull(),

            ]);
    }
}
