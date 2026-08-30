<?php

namespace App\Filament\Resources\Teachers\RelationManager;

use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager {

    protected static string $relationship = 'teacherAssignments';
    protected static ?string $title       = 'Turmas & Disciplinas';

    /**
     * Configura o formulário do recurso.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public function form(Schema $schema): Schema {
        return $schema->schema([
            Select::make('class_id')
                ->label('Turma')
                ->relationship('schoolClass', 'name') /* via TeacherAssignment::schoolClass() */
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            Select::make('subject_id')
                ->label('Disciplina')
                ->options(
                    fn () => Subject::orderBy('name')->pluck('name', 'id')
                )
                ->searchable()
                ->preload()
                ->required(),
        ]);
    }

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable()
                    ->url(
                        fn (TeacherAssignment $record) => SchoolClassResource::getUrl('edit', ['record' => $record->class_id])
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

                        return app(TeacherOnboardingService::class)->createAssignment($teacher, $data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar')
                    ->using(function (TeacherAssignment $record, array $data) {
                        /** @var \App\Models\Teacher $teacher */
                        $teacher = $this->getOwnerRecord();

                        return app(TeacherOnboardingService::class)
                            ->updateAssignment($record, $data, $teacher);
                    }),

                DeleteAction::make()->label('Remover'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->label('Remover selecionados'),
            ]);
    }
}
