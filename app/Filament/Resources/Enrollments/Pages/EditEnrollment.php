<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Enums\EnrollmentLockReason;
use App\Enums\EnrollmentStatus;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\SchoolClass;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    public function getTitle(): string
    {
        $record = $this->record;
        if ($record && $record->student) {
            return "Editar Matrícula — {$record->student->name}";
        }
        return 'Editar Matrícula';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        $record = $this->record;
        if (! $record) {
            return null;
        }

        $status  = $record->status instanceof EnrollmentStatus ? $record->status->label() : (string) $record->status;
        $turma   = $record->class?->name ?? '—';
        $ano     = $record->schoolYear?->year ?? $record->class?->schoolYear?->year ?? '—';

        return "Nº {$record->registration_number} · {$turma} · {$ano} · {$status}";
    }

    protected function getHeaderActions(): array
    {
        $record = $this->record;
        $hasGrades = $record && $record->grades()->exists();

        return [

            // ── Operações de status ────────────────────────────────────────────
            ActionGroup::make([

                // Trancar
                Action::make('trancar')
                    ->label('Trancar Matrícula')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::ACTIVE)
                    ->modalHeading('Trancar Matrícula')
                    ->modalDescription('O aluno ficará com status Trancado e não aparecerá nos lançamentos de notas/frequência.')
                    ->form([
                        Select::make('locked_reason')
                            ->label('Motivo do Trancamento')
                            ->options(EnrollmentLockReason::options())
                            ->required(),

                        DatePicker::make('lock_expires_at')
                            ->label('Válido até (prazo máximo)')
                            ->helperText('Deixe em branco para usar o fim do ano letivo.')
                            ->nullable(),

                        Textarea::make('observacao')
                            ->label('Observação')
                            ->rows(3)
                            ->nullable(),
                    ])
                    ->action(function (array $data): void {
                        $record         = $this->record;
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'              => EnrollmentStatus::LOCKED,
                            'locked_reason'       => $data['locked_reason'],
                            'lock_expires_at'     => $data['lock_expires_at'] ?? null,
                            'operated_by_user_id' => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'trancamento',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::LOCKED->value,
                            observacao: $data['observacao'] ?? null,
                        );

                        $this->refreshFormData(['status', 'locked_reason', 'lock_expires_at']);

                        Notification::make()
                            ->title('Matrícula trancada')
                            ->body("Nº {$record->registration_number} — trancada com sucesso.")
                            ->warning()
                            ->send();
                    }),

                // Reativar
                Action::make('reativar')
                    ->label('Reativar Matrícula')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::LOCKED)
                    ->modalHeading('Reativar Matrícula')
                    ->modalDescription('A matrícula voltará ao status Ativa com todos os vínculos originais intactos.')
                    ->form([
                        Textarea::make('observacao')
                            ->label('Justificativa de Reativação')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        $record         = $this->record;
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'              => EnrollmentStatus::ACTIVE,
                            'locked_reason'       => null,
                            'lock_expires_at'     => null,
                            'operated_by_user_id' => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'reativacao',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::ACTIVE->value,
                            observacao: $data['observacao'],
                        );

                        $this->refreshFormData(['status', 'locked_reason', 'lock_expires_at']);

                        Notification::make()
                            ->title('Matrícula reativada')
                            ->body("Nº {$record->registration_number} — reativada com sucesso.")
                            ->success()
                            ->send();
                    }),

                // Transferir Turma
                Action::make('transferirTurma')
                    ->label('Transferir Turma')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::ACTIVE)
                    ->modalHeading('Transferência entre Turmas')
                    ->modalDescription('O aluno será transferido para outra turma dentro do mesmo ano letivo. As notas e frequências são preservadas.')
                    ->form([
                        Select::make('class_id')
                            ->label('Turma de Destino')
                            ->options(function () {
                                $record = $this->record;
                                return SchoolClass::with('gradeLevel', 'schoolYear')
                                    ->where('school_year_id', $record->school_year_id)
                                    ->where('id', '!=', $record->class_id)
                                    ->get()
                                    ->mapWithKeys(function ($c) {
                                        $vagas = $c->capacity
                                            ? ($c->capacity - Enrollment::where('class_id', $c->id)
                                                ->whereIn('status', [
                                                    EnrollmentStatus::ACTIVE->value,
                                                    EnrollmentStatus::SUSPENDED->value,
                                                    EnrollmentStatus::LOCKED->value,
                                                ])->count())
                                            : '∞';
                                        return [
                                            $c->id => "{$c->name} — {$c->gradeLevel?->name} | Turno: {$c->shift?->label()} | Vagas: {$vagas}",
                                        ];
                                    });
                            })
                            ->required()
                            ->searchable(),

                        Textarea::make('transfer_reason')
                            ->label('Motivo da Transferência')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        $record = $this->record;

                        if (! Enrollment::classHasSlot((int) $data['class_id'])) {
                            if (! auth()->user()?->hasAnyRole(['admin', 'ti'])) {
                                Notification::make()
                                    ->title('Turma sem vagas')
                                    ->body('A turma de destino atingiu a capacidade máxima. Contate o perfil TI para forçar a transferência.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        $jaMatriculado = Enrollment::where('student_id', $record->student_id)
                            ->where('class_id', $data['class_id'])
                            ->whereIn('status', [
                                EnrollmentStatus::ACTIVE->value,
                                EnrollmentStatus::LOCKED->value,
                            ])
                            ->exists();

                        if ($jaMatriculado) {
                            Notification::make()
                                ->title('Aluno já matriculado na turma destino')
                                ->danger()
                                ->send();
                            return;
                        }

                        $statusAnterior  = $record->status?->value;
                        $turmaOrigemNome = $record->class?->name;

                        $record->update([
                            'status'              => EnrollmentStatus::TRANSFERRED_INTERNAL,
                            'transfer_type'       => 'internal',
                            'transfer_reason'     => $data['transfer_reason'],
                            'operated_by_user_id' => auth()->id(),
                        ]);

                        $novaMatricula = Enrollment::create([
                            'student_id'             => $record->student_id,
                            'class_id'               => $data['class_id'],
                            'school_year_id'         => $record->school_year_id,
                            'enrollment_date'        => now(),
                            'status'                 => EnrollmentStatus::ACTIVE,
                            'previous_enrollment_id' => $record->id,
                            'operated_by_user_id'    => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'transferencia_interna',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::TRANSFERRED_INTERNAL->value,
                            observacao: "Transferido de {$turmaOrigemNome} para turma ID {$data['class_id']}. Nova matrícula: {$novaMatricula->registration_number}. Motivo: {$data['transfer_reason']}",
                        );

                        EnrollmentLog::registrar(
                            enrollment: $novaMatricula,
                            acao: 'criacao',
                            statusNovo: EnrollmentStatus::ACTIVE->value,
                            observacao: "Criada por transferência interna a partir da matrícula {$record->registration_number}.",
                        );

                        Notification::make()
                            ->title('Transferência concluída')
                            ->body("Nova matrícula gerada: {$novaMatricula->registration_number}")
                            ->success()
                            ->send();

                        // Redireciona para a nova matrícula
                        $this->redirect(EditEnrollment::getUrl(['record' => $novaMatricula->id]));
                    }),

                // Transferência Externa
                Action::make('transferirExterna')
                    ->label('Transferência Externa')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('purple')
                    ->visible(fn () => in_array($this->record->status, [
                        EnrollmentStatus::ACTIVE,
                        EnrollmentStatus::LOCKED,
                    ]))
                    ->modalHeading('Registrar Transferência Externa')
                    ->modalDescription('Encerra definitivamente a matrícula nesta instituição. O histórico é preservado para consulta.')
                    ->form([
                        TextInput::make('transfer_destination')
                            ->label('Instituição de Destino')
                            ->helperText('Informe o nome da escola de destino, se conhecido.')
                            ->nullable(),

                        DatePicker::make('data_transferencia')
                            ->label('Data da Transferência')
                            ->default(now())
                            ->required(),

                        Textarea::make('transfer_reason')
                            ->label('Motivo')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Confirmar Transferência')
                    ->action(function (array $data): void {
                        $record         = $this->record;
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'               => EnrollmentStatus::TRANSFERRED_EXTERNAL,
                            'transfer_type'        => 'external',
                            'transfer_destination' => $data['transfer_destination'] ?? null,
                            'transfer_reason'      => $data['transfer_reason'],
                            'operated_by_user_id'  => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'transferencia_externa',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::TRANSFERRED_EXTERNAL->value,
                            observacao: 'Destino: ' . ($data['transfer_destination'] ?? 'não informado') . ". Motivo: {$data['transfer_reason']}",
                        );

                        $this->refreshFormData(['status', 'transfer_type', 'transfer_destination', 'transfer_reason']);

                        Notification::make()
                            ->title('Transferência externa registrada')
                            ->body("Matrícula {$record->registration_number} encerrada por transferência externa.")
                            ->success()
                            ->send();
                    }),

                // Cancelar
                Action::make('cancelar')
                    ->label('Cancelar Matrícula')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn () => in_array($this->record->status, [
                        EnrollmentStatus::ACTIVE,
                        EnrollmentStatus::LOCKED,
                        EnrollmentStatus::SUSPENDED,
                    ]))
                    ->modalHeading('Cancelar Matrícula')
                    ->modalDescription('⚠️ Esta operação é irreversível pelo fluxo normal. Somente o perfil TI pode reverter um cancelamento.')
                    ->form([
                        Textarea::make('cancel_reason')
                            ->label('Motivo do Cancelamento')
                            ->required()
                            ->rows(3),

                        Textarea::make('cancel_observations')
                            ->label('Observações Adicionais')
                            ->nullable()
                            ->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->modalSubmitActionLabel('Confirmar Cancelamento')
                    ->action(function (array $data): void {
                        $record         = $this->record;
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'               => EnrollmentStatus::CANCELED,
                            'cancel_reason'        => $data['cancel_reason'],
                            'cancel_observations'  => $data['cancel_observations'] ?? null,
                            'operated_by_user_id'  => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'cancelamento',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::CANCELED->value,
                            observacao: "Motivo: {$data['cancel_reason']}. " . ($data['cancel_observations'] ? "Obs: {$data['cancel_observations']}" : ''),
                        );

                        $this->refreshFormData(['status', 'cancel_reason', 'cancel_observations']);

                        Notification::make()
                            ->title('Matrícula cancelada')
                            ->body("Nº {$record->registration_number} — cancelada. O histórico acadêmico é preservado.")
                            ->danger()
                            ->send();
                    }),

                // Reverter Cancelamento (somente TI)
                Action::make('reverterCancelamento')
                    ->label('Reverter Cancelamento')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::CANCELED
                        && auth()->user()?->hasAnyRole(['admin', 'ti'])
                    )
                    ->modalHeading('Reverter Cancelamento — Perfil TI')
                    ->modalDescription('Ação restrita ao perfil TI. A matrícula voltará ao status Ativa.')
                    ->form([
                        Textarea::make('observacao')
                            ->label('Justificativa Obrigatória')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data): void {
                        if (! auth()->user()?->hasAnyRole(['admin', 'ti'])) {
                            Notification::make()->title('Acesso negado')->danger()->send();
                            return;
                        }

                        $record         = $this->record;
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'               => EnrollmentStatus::ACTIVE,
                            'cancel_reason'        => null,
                            'cancel_observations'  => null,
                            'operated_by_user_id'  => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'reversao_cancelamento',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::ACTIVE->value,
                            observacao: $data['observacao'],
                        );

                        $this->refreshFormData(['status', 'cancel_reason', 'cancel_observations']);

                        Notification::make()
                            ->title('Cancelamento revertido')
                            ->body("Nº {$record->registration_number} — matrícula reativada pelo perfil TI.")
                            ->warning()
                            ->send();
                    }),

            ])
            ->label('Operações')
            ->icon('heroicon-o-cog-6-tooth')
            ->button(),

            // ── PDFs ───────────────────────────────────────────────────────────
            ActionGroup::make([
                Action::make('pdfComprovante')
                    ->label('Comprovante de Matrícula')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn () => route('pdf.enrollment.comprovante', $this->record->id))
                    ->openUrlInNewTab(),

                Action::make('pdfTransferenciaInterna')
                    ->label('PDF Transferência de Turma')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::TRANSFERRED_INTERNAL)
                    ->url(fn () => route('pdf.enrollment.transferencia-interna', $this->record->id))
                    ->openUrlInNewTab(),

                Action::make('pdfTransferenciaExterna')
                    ->label('Declaração de Transferência')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::TRANSFERRED_EXTERNAL)
                    ->url(fn () => route('pdf.enrollment.transferencia-externa', $this->record->id))
                    ->openUrlInNewTab(),

                Action::make('pdfTrancamento')
                    ->label('Comprovante de Trancamento')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::LOCKED)
                    ->url(fn () => route('pdf.enrollment.trancamento', $this->record->id))
                    ->openUrlInNewTab(),

                Action::make('pdfCancelamento')
                    ->label('Termo de Cancelamento')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->visible(fn () => $this->record->status === EnrollmentStatus::CANCELED)
                    ->url(fn () => route('pdf.enrollment.cancelamento', $this->record->id))
                    ->openUrlInNewTab(),
            ])
            ->label('Emitir PDF')
            ->icon('heroicon-o-printer')
            ->button()
            ->color('gray'),

            // ── Excluir ────────────────────────────────────────────────────────
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $record))
                ->disabled($hasGrades)
                ->tooltip($hasGrades
                    ? 'Não é possível excluir matrícula com notas lançadas. Cancele a matrícula em vez de excluir.'
                    : null
                ),
        ];
    }

    /** Registra log de edição ao salvar alterações */
    protected function afterSave(): void
    {
        EnrollmentLog::registrar(
            enrollment: $this->record,
            acao: 'edicao',
            observacao: 'Dados da matrícula editados via painel administrativo.',
        );
    }
}
