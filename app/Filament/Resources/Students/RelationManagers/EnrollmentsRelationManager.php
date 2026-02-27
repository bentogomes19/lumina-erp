<?php

namespace App\Filament\Resources\Students\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';
    protected static ?string $title = 'Matrículas / Turmas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->url(fn ($record) => EnrollmentResource::getUrl('edit', ['record' => $record]))
                    ->searchable(),
                TextColumn::make('schoolClass.gradeLevel.name')
                    ->label('Série'),
                TextColumn::make('schoolClass.schoolYear.year')
                    ->label('Ano letivo'),
                TextColumn::make('enrollment_date')
                    ->label('Data da matrícula')
                    ->date('d/m/Y'),
                TextColumn::make('roll_number')
                    ->label('Nº chamada')
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof EnrollmentStatus ? $state->value : $state)
                    ->color(fn ($state) => match ($state instanceof EnrollmentStatus ? $state->value : $state) {
                        'Ativa' => 'success',
                        'Suspensa' => 'warning',
                        'Cancelada' => 'danger',
                        'Completa' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('enrollment_date', 'desc')
            ->recordActions([
                \Filament\Actions\Action::make('edit')
                    ->label('Editar matrícula')
                    ->url(fn ($record) => EnrollmentResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil'),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('novaMatricula')
                    ->label('Nova matrícula')
                    ->url(fn () => EnrollmentResource::getUrl('create') . '?student_id=' . $this->getOwnerRecord()->id)
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
