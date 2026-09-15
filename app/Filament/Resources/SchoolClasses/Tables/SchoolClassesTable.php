<?php

namespace App\Filament\Resources\SchoolClasses\Tables;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\EnrollmentStatus;
use App\Filament\Actions\GuardedForceDeleteBulkAction;
use App\Filament\Actions\WarnedDeleteBulkAction;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Enum as EnumRule;

class SchoolClassesTable
{
    /**
     * Configura as colunas, os filtros e as ações da tabela de turmas.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['homeroomTeacher' => fn ($teacher) => $teacher->withTrashed()])
                ->withCount([
                    'enrollments as occupied_slots_count' => fn (Builder $enrollments) => $enrollments
                        ->whereIn('status', EnrollmentStatus::occupyingValues()),
                ]))
            ->recordAction('verTurma')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->toggleable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('gradeLevel.name')->label('Série')->searchable()->sortable(),

                TextColumn::make('schoolYear.year')->label('Ano Letivo')->sortable(),

                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassShift::MORNING => 'success',
                        ClassShift::AFTERNOON => 'warning',
                        ClassShift::EVENING => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassStatus::OPEN => 'success',
                        ClassStatus::CLOSED => 'danger',
                        ClassStatus::ARCHIVED => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('homeroomTeacher.name')
                    ->label('Professor Resp.')
                    ->formatStateUsing(fn (?string $state, SchoolClass $record): string => ($state ?? 'Não definido')
                        .($record->homeroomTeacher?->trashed() ? ' · Inativo' : ''))
                    ->toggleable(),

                TextColumn::make('occupied_slots_count')
                    ->label('Ocupação')
                    ->state(fn (SchoolClass $record): string => $record->capacity
                        ? app(StudentEnrollmentService::class)->occupiedSlots($record)."/{$record->capacity}"
                        : app(StudentEnrollmentService::class)->occupiedSlots($record).'/∞')
                    ->alignRight(),

                TextColumn::make('remaining_slots')
                    ->label('Vagas restantes')
                    ->state(fn (SchoolClass $record): string => (string) (
                        app(StudentEnrollmentService::class)->remainingSlots($record) ?? 'Ilimitadas'
                    ))
                    ->alignRight(),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Ano letivo')
                    ->options(fn () => SchoolYear::query()
                        ->orderByDesc('year')
                        ->pluck('year', 'id'))
                    ->default(fn () => SchoolYear::query()
                        ->where('is_active', true)
                        ->value('id')),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ClassStatus::options()),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('verTurma')
                        ->label('Ver turma')
                        ->icon('fas-eye')
                        ->modalHeading(fn (SchoolClass $record): string => "Turma | {$record->name}")
                        ->modalWidth('3xl')
                        ->infolist([
                            InfoSection::make('Identificação')
                                ->icon('fas-id-card')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('name')
                                        ->label('Turma')
                                        ->weight('bold')
                                        ->columnSpan(2),
                                    TextEntry::make('code')
                                        ->label('Código')
                                        ->placeholder('Não informado')
                                        ->copyable(),
                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->formatStateUsing(fn ($state) => $state instanceof ClassStatus ? $state->label() : '—')
                                        ->color(fn ($state) => match ($state) {
                                            ClassStatus::OPEN => 'success',
                                            ClassStatus::CLOSED => 'danger',
                                            ClassStatus::ARCHIVED => 'gray',
                                            default => 'gray',
                                        }),
                                ]),
                            InfoSection::make('Contexto acadêmico')
                                ->icon('fas-graduation-cap')
                                ->collapsible()
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('gradeLevel.name')->label('Série / etapa')->placeholder('—'),
                                    TextEntry::make('schoolYear.year')->label('Ano letivo')->placeholder('—'),
                                    TextEntry::make('shift')
                                        ->label('Turno')
                                        ->formatStateUsing(fn ($state) => $state instanceof ClassShift ? $state->label() : '—'),
                                    TextEntry::make('type')
                                        ->label('Tipo')
                                        ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                                    TextEntry::make('homeroomTeacher.name')
                                        ->label('Professor responsável')
                                        ->formatStateUsing(fn (?string $state, SchoolClass $record): string => ($state ?? 'Não definido')
                                            .($record->homeroomTeacher?->trashed() ? ' · Inativo' : ''))
                                        ->placeholder('Não definido')
                                        ->columnSpan(2),
                                    TextEntry::make('occupation')
                                        ->label('Ocupação')
                                        ->state(fn (SchoolClass $record): string => $record->capacity
                                            ? app(StudentEnrollmentService::class)->occupiedSlots($record)." / {$record->capacity}"
                                            : app(StudentEnrollmentService::class)->occupiedSlots($record).' / Ilimitada'),
                                ]),
                            InfoSection::make('Disciplinas da turma')
                                ->icon('fas-book-open')
                                ->collapsible()
                                ->schema([
                                    RepeatableEntry::make('subjects')
                                        ->label('Disciplinas')
                                        ->schema([
                                            TextEntry::make('name')->label('Disciplina')->weight('bold'),
                                            TextEntry::make('code')->label('Código')->placeholder('Não informado'),
                                            TextEntry::make('category')
                                                ->label('Componente')
                                                ->badge()
                                                ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                                            TextEntry::make('bncc_code')->label('BNCC')->placeholder('Não informado'),
                                            TextEntry::make('description')
                                                ->label('Descrição')
                                                ->placeholder('Sem descrição cadastrada.')
                                                ->limit(80),
                                        ])
                                        ->table([
                                            TableColumn::make('Disciplina'),
                                            TableColumn::make('Código'),
                                            TableColumn::make('Componente'),
                                            TableColumn::make('BNCC'),
                                            TableColumn::make('Descrição'),
                                        ])
                                        ->placeholder('Nenhuma disciplina cadastrada.'),
                                ]),
                        ])
                        ->modalSubmitAction(false),

                    EditAction::make(),
                    Action::make('matricularAluno')
                        ->label('Matricular aluno')
                        ->icon('fas-user-plus')
                        ->modalHeading('Matricular aluno existente')
                        ->modalDescription('Será criada somente 1 matrícula para o aluno selecionado nesta turma. Nenhum usuário ou convite será criado.')
                        ->modalSubmitActionLabel('Confirmar matrícula')
                        ->form([
                            Placeholder::make('capacity_summary')
                                ->label('Disponibilidade da turma')
                                ->content(fn (SchoolClass $record): string => app(StudentEnrollmentService::class)
                                    ->capacitySummary($record)),

                            Select::make('student_id')
                                ->label('Aluno')
                                ->options(fn () => Student::orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),

                            DatePicker::make('enrollment_date')
                                ->label('Data de matrícula')
                                ->default(now())
                                ->required(),

                            TextInput::make('roll_number')
                                ->label('Nº chamada')
                                ->numeric()
                                ->minValue(1)
                                ->nullable(),

                            Select::make('status')
                                ->label('Status')
                                ->options(
                                    collect(EnrollmentStatus::cases())
                                        ->mapWithKeys(fn ($c) => [$c->value => method_exists($c, 'label') ? $c->label() : ucfirst($c->value)])
                                        ->all()
                                )
                                ->required()
                                ->rule(new EnumRule(EnrollmentStatus::class))
                                ->default(EnrollmentStatus::ACTIVE->value),
                        ])
                        ->action(function (SchoolClass $record, array $data): void {
                            app(StudentEnrollmentService::class)->enrollExistingStudent(array_merge($data, [
                                'class_id' => $record->id,
                            ]));

                            Notification::make()->title('Matrícula realizada com sucesso.')->success()->send();
                        }),
                    Action::make('verDisciplinas')
                        ->label('Ver disciplinas')
                        ->icon('fas-book-open')
                        ->modalHeading(fn ($record) => "Disciplinas | {$record->name}")
                        ->modalWidth('3xl')
                        ->infolist([
                            InfoSection::make('Disciplinas cadastradas')
                                ->icon('fas-book-open')
                                ->collapsible()
                                ->schema([
                                    RepeatableEntry::make('subjects')
                                        ->label('Disciplinas')
                                        ->schema([
                                            TextEntry::make('name')->label('Disciplina')->weight('bold'),
                                            TextEntry::make('code')->label('Código')->placeholder('Não informado'),
                                            TextEntry::make('category')
                                                ->label('Componente')
                                                ->badge()
                                                ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                                            TextEntry::make('bncc_code')->label('BNCC')->placeholder('Não informado'),
                                            TextEntry::make('description')
                                                ->label('Descrição')
                                                ->placeholder('Sem descrição cadastrada.')
                                                ->limit(80),
                                        ])
                                        ->table([
                                            TableColumn::make('Disciplina'),
                                            TableColumn::make('Código'),
                                            TableColumn::make('Componente'),
                                            TableColumn::make('BNCC'),
                                            TableColumn::make('Descrição'),
                                        ])
                                        ->placeholder('Nenhuma disciplina cadastrada.'),
                                ]),
                        ])
                        ->modalSubmitAction(false),
                ])
                    ->label('Ações')
                    ->icon('fas-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    WarnedDeleteBulkAction::make()
                        ->impactWarning(
                            'Atenção: turmas com vínculos ativos',
                            'turma',
                            'turmas',
                        ),
                    GuardedForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
