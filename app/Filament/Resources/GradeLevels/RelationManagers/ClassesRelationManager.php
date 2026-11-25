<?php

namespace App\Filament\Resources\GradeLevels\RelationManagers;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consulta das turmas vinculadas à série. Turmas são criadas e editadas em Turmas;
 * aqui cada linha leva à tela da turma.
 */
class ClassesRelationManager extends RelationManager {

    protected static string $relationship = 'classes';
    protected static ?string $title       = 'Turmas da Série';

    /**
     * Mantém a aba somente para consulta, inclusive na página de edição.
     *
     * @return bool
     */
    public function isReadOnly(): bool {
        return true;
    }

    /**
     * Configura a tabela de turmas.
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table): Table {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['schoolYear', 'homeroomTeacher' => fn ($teacher) => $teacher->withTrashed()])
                ->withCount([
                    'enrollments as occupied_slots_count' => fn (Builder $enrollments) => $enrollments
                        ->whereIn('status', EnrollmentStatus::occupyingValues()),
                ]))
            ->recordUrl(fn (SchoolClass $record): ?string => SchoolClassResource::canEdit($record)
                ? SchoolClassResource::getUrl('edit', ['record' => $record])
                : null)
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Turma')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('classes.name', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query
                        ->orderBy('classes.name', $direction)),

                TextColumn::make('schoolYear.year')
                    ->label('Ano letivo')
                    ->sortable(),

                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassShift::MORNING   => 'success',
                        ClassShift::AFTERNOON => 'warning',
                        ClassShift::EVENING   => 'info',
                        default               => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassStatus::OPEN     => 'success',
                        ClassStatus::CLOSED   => 'danger',
                        ClassStatus::ARCHIVED => 'gray',
                        default               => 'secondary',
                    }),

                TextColumn::make('homeroomTeacher.name')
                    ->label('Professor Resp.')
                    ->formatStateUsing(fn (?string $state, SchoolClass $record): string => ($state ?? 'Não definido')
                        .($record->homeroomTeacher?->trashed() ? ' · Inativo' : ''))
                    ->placeholder('Não definido')
                    ->toggleable(),

                TextColumn::make('occupied_slots_count')
                    ->label('Ocupação')
                    ->state(fn (SchoolClass $record): string => app(StudentEnrollmentService::class)->occupiedSlots($record)
                        .'/'.($record->capacity ?: '∞'))
                    ->alignRight(),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Ano letivo')
                    ->options(fn () => SchoolYear::query()->orderByDesc('year')->pluck('year', 'id'))
                    ->default(fn () => SchoolYear::query()->where('is_active', true)->value('id')),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ClassStatus::options()),
            ]);
    }
}
