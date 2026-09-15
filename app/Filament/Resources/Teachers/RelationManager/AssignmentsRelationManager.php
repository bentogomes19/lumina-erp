<?php

namespace App\Filament\Resources\Teachers\RelationManager;

use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Services\Teachers\TeacherOnboardingService;
use App\Support\PermissionAccess;
use App\Support\TeacherAssignmentCurriculum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'teacherAssignments';

    protected static ?string $title = 'Turmas & Disciplinas';

    /**
     * Configura o formulário do recurso.
     */
    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('class_id')
                ->label('Turma')
                ->relationship('schoolClass', 'name') /* via TeacherAssignment::schoolClass() */
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->afterStateUpdated(fn (Set $set): mixed => $set('subject_id', null)),

            Select::make('subject_id')
                ->label('Disciplina')
                ->options(fn (Get $get) => TeacherAssignmentCurriculum::subjectOptions(
                    (int) $get('class_id'),
                    PermissionAccess::can('admin.teachers.assignments.curriculum_exception')
                        && (bool) $get('curriculum_exception'),
                ))
                ->searchable()
                ->preload()
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

    /**
     * Configura a tabela e suas ações.
     */
    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'subject' => fn ($subject) => $subject->withTrashed(),
                'schoolClass' => fn ($schoolClass) => $schoolClass->withTrashed(),
            ]))
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->formatStateUsing(fn (?string $state, TeacherAssignment $record): string => self::displayName(
                        $state,
                        $record->schoolClass?->trashed() ?? false,
                    ))
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
                    ->formatStateUsing(fn (?string $state, TeacherAssignment $record): string => self::displayName(
                        $state,
                        $record->subject?->trashed() ?? false,
                    ))
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Vincular')
                    ->using(function (array $data) {
                        /** @var Teacher $teacher */
                        $teacher = $this->getOwnerRecord();

                        return app(TeacherOnboardingService::class)->createAssignment($teacher, $data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->label('Editar')
                    ->using(function (TeacherAssignment $record, array $data) {
                        /** @var Teacher $teacher */
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

    private static function displayName(?string $name, bool $trashed): string
    {
        return ($name ?? 'Indisponível').($trashed ? ' · Inativo' : '');
    }
}
