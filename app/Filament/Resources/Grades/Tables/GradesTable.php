<?php

namespace App\Filament\Resources\Grades\Tables;

use App\Enums\AssessmentType;
use App\Enums\Term;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GradesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('schoolClass.name')->label('Turma')->searchable(),
                TextColumn::make('subject.name')->label('Disciplina')->searchable(),
                TextColumn::make('enrollment.student.name')->label('Aluno')->searchable(),

                BadgeColumn::make('term')->label('Período')
                    ->formatStateUsing(fn($s) => Term::tryFrom((string)$s)?->name ?? strtoupper($s))
                    ->colors(['primary']),

                BadgeColumn::make('assessment_type')->label('Tipo')
                    ->formatStateUsing(fn($s) => AssessmentType::tryFrom((string)$s)?->name ?? $s)
                    ->colors([
                        'warning' => 'work',
                        'info' => 'quiz',
                        'success' => 'participation',
                        'primary' => 'test',
                        'gray' => 'project',
                        'danger' => 'recovery',
                    ]),

                TextColumn::make('score')->label('Nota')->numeric(2)->alignRight(),
                TextColumn::make('max_score')->label('Máx.')->numeric(2)->alignRight()->toggleable(),
                TextColumn::make('weight')->label('Peso')->numeric(2)->alignRight()->toggleable(),
                TextColumn::make('percent')->label('%')->state(fn($r) => $r->percent ? "{$r->percent}%" : '—')->alignRight(),

                TextColumn::make('date_recorded')->label('Data')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('term')->label('Período')->options(Term::options()),
                SelectFilter::make('assessment_type')->label('Tipo')->options(AssessmentType::options()),
                SelectFilter::make('class_id')->label('Turma')->relationship('schoolClass','name')->searchable()->preload(),
                SelectFilter::make('subject_id')->label('Disciplina')->relationship('subject','name')->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('lock')
                        ->label('Fechar notas (selecionadas)')
                        ->icon('heroicon-o-lock-closed')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->each->update(['locked_at'=>now()])),
                ]),
            ]);
    }
}
