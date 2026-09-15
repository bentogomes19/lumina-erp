<?php

namespace App\Filament\Resources\TeacherAssignments\Tables;

use App\Models\TeacherAssignment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeacherAssignmentsTable
{
    /**
     * Configura as colunas, os filtros e as ações da tabela de atribuições docentes.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'teacher' => fn ($teacher) => $teacher->withTrashed(),
                'subject' => fn ($subject) => $subject->withTrashed(),
                'schoolClass' => fn ($schoolClass) => $schoolClass->withTrashed(),
            ]))
            ->columns([
                TextColumn::make('schoolClass.name')
                    ->label('Turma')
                    ->formatStateUsing(fn (?string $state, TeacherAssignment $record): string => self::displayName(
                        $state,
                        $record->schoolClass?->trashed() ?? false,
                    )),
                TextColumn::make('teacher.name')
                    ->label('Professor')
                    ->formatStateUsing(fn (?string $state, TeacherAssignment $record): string => self::displayName(
                        $state,
                        $record->teacher?->trashed() ?? false,
                    )),
                TextColumn::make('subject.name')
                    ->label('Disciplina')
                    ->formatStateUsing(fn (?string $state, TeacherAssignment $record): string => self::displayName(
                        $state,
                        $record->subject?->trashed() ?? false,
                    )),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function displayName(?string $name, bool $trashed): string
    {
        return ($name ?? 'Indisponível').($trashed ? ' · Inativo' : '');
    }
}
