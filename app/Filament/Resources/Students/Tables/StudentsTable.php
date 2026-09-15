<?php

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentOnboardingState;
use App\Enums\StudentStatus;
use App\Filament\Actions\GuardedForceDeleteBulkAction;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Services\Enrollments\StudentEnrollmentService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StudentsTable
{
    /**
     * Configura as colunas, os filtros e as ações da tabela de alunos.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_number')->label('Matrícula')->searchable()->copyable(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('age')
                    ->label('Idade')
                    ->getStateUsing(
                        fn ($record) => $record?->birth_date
                        ? Carbon::parse($record->birth_date)->age
                        : null
                    )
                    ->placeholder('—')
                    ->alignRight()

                    /* ordena por nascimento (mais novo/mais velho), mantendo nulos no fim. */
                    ->sortable(query: function ($query, string $direction) {
                        return $query
                            ->orderByRaw('birth_date IS NULL') /* nulos por último. */
                            ->orderBy('birth_date', $direction === 'asc' ? 'desc' : 'asc');
                    }),
                TextColumn::make('classes.name')->label('Turmas')->limit(20)->toggleable(),
                TextColumn::make('email')->label('E-mail')->toggleable(),
                TextColumn::make('phone_number')->label('Telefone')->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof BackedEnum ? $state->value : $state;   /* enum ou string. */

                        return StudentStatus::options()[$value] ?? '—';
                    })
                    ->colors([
                        'success' => fn ($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::ACTIVE->value,
                        'warning' => fn ($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::SUSPENDED->value,
                        'info' => fn ($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::GRADUATED->value,
                        'gray' => fn ($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::INACTIVE->value,
                    ]),
                BadgeColumn::make('onboarding_state')
                    ->label('Onboarding')
                    ->getStateUsing(fn (Student $record) => app(StudentEnrollmentService::class)->onboardingState($record))
                    ->formatStateUsing(fn ($state): string => $state->label())
                    ->color(fn ($state): string => $state->color()),
                TextColumn::make('enrollment_date')->label('Ingresso')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StudentStatus::options())
                    ->default(StudentStatus::ACTIVE->value),
                SelectFilter::make('class_id')
                    ->label('Turma (Ano atual)')
                    ->relationship(
                        'classes',
                        'name',
                        fn ($query) => $query->where('classes.school_year_id', SchoolYear::query()
                            ->where('is_active', true)
                            ->value('id'))
                    )
                    ->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('criarAcesso')
                    ->label('Criar usuário')
                    ->icon('fas-user-plus')
                    ->visible(fn (Student $record): bool => ! $record->user_id
                        && app(StudentEnrollmentService::class)->onboardingState($record) !== StudentOnboardingState::INCONSISTENT)
                    ->modalHeading(fn ($record) => "Criar usuário para {$record->name}")
                    ->modalDescription('Será criado e vinculado 1 usuário com o papel Aluno. Nenhum convite será enviado nesta operação.')
                    ->modalSubmitActionLabel('Criar usuário sem convite')
                    ->form([
                        TextInput::make('email')
                            ->label('E-mail de acesso')
                            ->email()
                            ->default(fn ($record) => $record->email)
                            ->required(),
                    ])
                    ->action(function (Student $record, array $data): void {
                        app(StudentEnrollmentService::class)->createAccess($record, $data['email']);
                        Notification::make()
                            ->title('Usuário criado sem envio de convite')
                            ->body('Use a ação “Enviar convite” quando for o momento de conceder o primeiro acesso.')
                            ->success()
                            ->send();
                    }),
                Action::make('enviarConvite')
                    ->label('Enviar convite')
                    ->icon('fas-paper-plane')
                    ->color('warning')
                    ->visible(fn (Student $record): bool => (bool) $record->user_id
                        && app(StudentEnrollmentService::class)->onboardingState($record) !== StudentOnboardingState::INCONSISTENT)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Student $record): string => "Enviar convite para {$record->name}")
                    ->modalDescription('O convite anterior será revogado. Um novo link descartável será enviado ao e-mail do usuário vinculado.')
                    ->action(function (Student $record): void {
                        $url = app(StudentEnrollmentService::class)->inviteAccess($record);

                        Notification::make()
                            ->title('Convite enviado ao aluno')
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
                Action::make('matricularAluno')
                    ->label('Matricular aluno')
                    ->icon('fas-graduation-cap')
                    ->url(fn (Student $record): string => EnrollmentResource::getUrl('create', [
                        'student_id' => $record->id,
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $user = auth()->user();
                            $deleted = 0;
                            $blocked = 0;
                            foreach ($records as $student) {
                                if ($user->can('delete', $student)) {
                                    $student->delete();
                                    $deleted++;
                                } else {
                                    $blocked++;
                                }
                            }
                            if ($blocked > 0) {
                                Notification::make()
                                    ->title('Exclusão em lote')
                                    ->body($deleted > 0
                                        ? "{$deleted} aluno(s) excluído(s). {$blocked} não puderam ser excluídos por possuírem matrículas ou vínculo com turmas."
                                        : 'Nenhum aluno excluído. Alunos com matrículas ou vínculo com turmas não podem ser excluídos.')
                                    ->warning()
                                    ->send();
                            } elseif ($deleted > 0) {
                                Notification::make()
                                    ->title('Alunos excluídos')
                                    ->body("{$deleted} aluno(s) excluído(s).")
                                    ->success()
                                    ->send();
                            }
                        }),
                    GuardedForceDeleteBulkAction::make(),
                    BulkAction::make('bulkStatus')
                        ->label('Alterar status (selecionados)')
                        ->icon('fas-sliders')
                        ->form([
                            Select::make('status')
                                ->label('Novo status')
                                ->options(StudentStatus::options())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $status = $data['status'];
                            $records->each->update([
                                'status' => $status,
                                'status_changed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Status atualizado para os registros selecionados.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
