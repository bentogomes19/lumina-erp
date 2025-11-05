<?php

namespace App\Filament\Resources\Teachers\RelationManager;

use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherAssignments';
    protected static ?string $title = 'Turmas & Disciplinas';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('class_id')
                ->label('Turma')
                ->relationship('schoolClass', 'name') // via TeacherAssignment::schoolClass()
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            Select::make('subject_id')
                ->label('Disciplina')
                ->options(fn () =>
                Subject::orderBy('name')->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->url(fn (TeacherAssignment $record) =>
                    SchoolClassResource::getUrl('edit', ['record' => $record->class_id])
                    )
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('schoolClass.gradeLevel.name')
                    ->label('Série/Ano')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('schoolClass.schoolYear.year')
                    ->label('Ano Letivo')
                    ->sortable(),

                TextColumn::make('subject.code')
                    ->label('Cód.')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Vincular')
                    ->using(function (array $data) {
                        /** @var \App\Models\Teacher $teacher */
                        $teacher = $this->getOwnerRecord();

                        $exists = TeacherAssignment::where('class_id', $data['class_id'])
                            ->where('subject_id', $data['subject_id'])
                            ->where('teacher_id', '!=', $teacher->id)
                            ->exists();

                        if ($exists) {
                            throw ValidationException::withMessages([
                                'subject_id' => 'Esta disciplina já possui um professor vinculado nesta turma.',
                            ]);
                        }

                        return TeacherAssignment::updateOrCreate(
                            [
                                'class_id'   => $data['class_id'],
                                'subject_id' => $data['subject_id'],
                            ],
                            [
                                'teacher_id' => $teacher->id,
                            ],
                        );
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar')
                    ->using(function (TeacherAssignment $record, array $data) {
                        /** @var \App\Models\Teacher $teacher */
                        $teacher = $this->getOwnerRecord();

                        $exists = TeacherAssignment::where('class_id', $data['class_id'])
                            ->where('subject_id', $data['subject_id'])
                            ->where('teacher_id', '!=', $teacher->id)
                            ->exists();

                        if ($exists) {
                            throw ValidationException::withMessages([
                                'subject_id' => 'Esta disciplina já possui um professor vinculado nesta turma.',
                            ]);
                        }

                        $record->update([
                            'class_id'   => $data['class_id'],
                            'subject_id' => $data['subject_id'],
                            'teacher_id' => $teacher->id,
                        ]);

                        return $record;
                    }),

                DeleteAction::make()->label('Remover'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Remover selecionados'),
            ]);
    }
}
