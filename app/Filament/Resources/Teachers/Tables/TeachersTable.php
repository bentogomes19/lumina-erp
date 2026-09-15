<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Enums\TeacherOnboardingState;
use App\Enums\TeacherStatus;
use App\Filament\Actions\GuardedForceDeleteBulkAction;
use App\Filament\Actions\WarnedDeleteBulkAction;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Services\Teachers\TeacherOnboardingService;
use App\Support\PermissionAccess;
use App\Support\TeacherAssignmentCurriculum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeachersTable
{
    /**
     * Configura as colunas, os filtros e as ações da tabela de professores.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_number')->label('Matrícula')->searchable()->copyable()->toggleable(),
                TextColumn::make('name')->label('Nome')->searchable()->toggleable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Telefone')->toggleable()->toggleable(),
                TextColumn::make('weekly_workload')->label('CH (h)')->numeric()->alignRight()->toggleable(),
                TextColumn::make('status')->toggleable(true)->searchable()->sortable()
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) => $state
                        ? (is_string($state)
                            ? TeacherStatus::from($state)->label()
                            : $state->label())
                        : '—'
                    )
                    ->color(fn ($state) => match (is_string($state) ? $state : $state?->value) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'sabbatical' => 'warning',
                        'terminated' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('onboarding_state')
                    ->label('Onboarding')
                    ->badge()
                    ->getStateUsing(fn (Teacher $record) => app(TeacherOnboardingService::class)
                        ->onboardingState($record))
                    ->formatStateUsing(fn (TeacherOnboardingState $state): string => $state->label())
                    ->color(fn (TeacherOnboardingState $state): string => $state->color()),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TeacherStatus::options())
                    ->default(TeacherStatus::ACTIVE->value),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('criarAcesso')
                    ->label('Criar usuário')
                    ->icon('fas-user-plus')
                    ->visible(fn (Teacher $record): bool => ! $record->user_id)
                    ->modalHeading(fn ($record) => "Criar usuário para {$record->name}")
                    ->modalDescription('Será criado e vinculado 1 usuário docente. Nenhum convite será enviado nesta operação.')
                    ->modalSubmitActionLabel('Criar usuário sem convite')
                    ->form([
                        TextInput::make('email')
                            ->label('E-mail de acesso')
                            ->email()
                            ->default(fn ($record) => $record->email)
                            ->required(),
                    ])
                    ->action(function (Teacher $record, array $data): void {
                        app(TeacherOnboardingService::class)->createAccess($record, $data['email']);
                        Notification::make()
                            ->title('Usuário criado sem envio de convite')
                            ->body('Use a ação “Enviar convite” quando o professor estiver apto ao acesso.')
                            ->success()
                            ->send();
                    }),
                Action::make('enviarConvite')
                    ->label('Enviar convite')
                    ->icon('fas-paper-plane')
                    ->color('warning')
                    ->visible(fn (Teacher $record): bool => (bool) $record->user_id
                        && $record->canAccessOperationally())
                    ->requiresConfirmation()
                    ->modalHeading(fn (Teacher $record): string => "Enviar convite para {$record->name}")
                    ->modalDescription('O link anterior será revogado e um novo convite descartável será enviado.')
                    ->action(function (Teacher $record): void {
                        $url = app(TeacherOnboardingService::class)->inviteAccess($record);

                        Notification::make()
                            ->title('Convite enviado ao professor')
                            ->actions([
                                Action::make('openInvitation')
                                    ->label('Abrir link do convite')
                                    ->url($url)
                                    ->openUrlInNewTab(),
                            ])
                            ->success()
                            ->duration(15000)
                            ->send();
                    }),
                Action::make('vincular')
                    ->label('Adicionar alocação')
                    ->icon('fas-link')
                    ->modalHeading(fn ($record) => "Vincular {$record->name}")
                    ->modalDescription('Será criada uma alocação para a turma e disciplina selecionadas.')
                    ->form([
                        Select::make('class_id')
                            ->label('Turma')
                            ->options(
                                fn () => SchoolClass::query()
                                    ->with('gradeLevel', 'schoolYear')
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [
                                        $c->id => "{$c->name} | {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                    ])
                            )
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

                    ])
                    ->action(function (Teacher $record, array $data): void {
                        app(TeacherOnboardingService::class)->createAssignment($record, $data);
                        Notification::make()
                            ->title('Alocação confirmada')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    WarnedDeleteBulkAction::make()
                        ->impactWarning(
                            'Atenção: professores com vínculos ativos',
                            'professor',
                            'professores',
                        ),
                    GuardedForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                    BulkAction::make('alterarStatus')
                        ->label('Alterar status')
                        ->icon('fas-sliders')
                        ->modalHeading('Alterar status dos professores selecionados')
                        ->form([
                            Select::make('status')
                                ->label('Novo status')
                                ->options(TeacherStatus::options())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $novo = $data['status'];
                            $total = 0;

                            DB::transaction(function () use ($records, $novo, &$total) {
                                $total = $records->each->update(['status' => $novo])->count();
                            });

                            Notification::make()
                                ->title('Status atualizado')
                                ->body("{$total} professor(es) atualizado(s) para ".(TeacherStatus::from($novo)->label()))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
