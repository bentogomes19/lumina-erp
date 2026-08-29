<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class StudentsRelationManager extends RelationManager {

    protected static string $relationship = 'students';
    protected static ?string $title       = 'Alunos matriculados';

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
                TextColumn::make('registration_number')->label('Matrícula')->searchable(),
                TextColumn::make('name')->label('Aluno')->searchable()->sortable(),
                TextColumn::make('pivot.enrollment_date')->label('Ingresso')->date(),
                TextColumn::make('pivot.roll_number')->label('Nº chamada')->toggleable(),
                TextColumn::make('pivot.status')->label('Status matrícula')->badge(),
            ])

            ->headerActions([
            ])

            ->recordActions([
                EditAction::make()
                    ->label('Editar matrícula')
                    ->form([
                        DatePicker::make('enrollment_date')->label('Data de matrícula')->required(),
                        TextInput::make('roll_number')->label('Nº chamada')->numeric()->minValue(1),
                        Select::make('status')
                            ->label('Status')
                            ->options(EnrollmentStatus::options())
                            ->required(),
                    ])
                    ->using(function ($record, array $data) {
                        $enrollment = Enrollment::query()
                            ->where('class_id', $this->getOwnerRecord()->id)
                            ->where('student_id', $record->id)
                            ->firstOrFail();

                        app(StudentEnrollmentService::class)->updateStatus(
                            $enrollment,
                            $data['status'],
                            [
                                'enrollment_date' => $data['enrollment_date'],
                                'roll_number'     => $data['roll_number'] ?? null,
                            ],
                        );
                    }),

                DetachAction::make()
                    ->label('Remover da turma')
                    ->requiresConfirmation(),
            ]);
    }
}
