<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Enums\ClassShift;
use App\Enums\EnrollmentLockReason;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn () => Enrollment::query()
                ->with([
                    'student',
                    'class.gradeLevel',
                    'class.schoolYear',
                ])
            )
            ->defaultSort('enrollment_date', 'desc')

            // ── Colunas ───────────────────────────────────────────────────────
            ->columns([
                TextColumn::make('registration_number')
                    ->label('Nº Matrícula')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('class.name')
                    ->label('Turma')
                    ->sortable(),

                TextColumn::make('class.gradeLevel.name')
                    ->label('Série')
                    ->toggleable(),

                TextColumn::make('class.shift')
                    ->label('Turno')
                    ->formatStateUsing(fn ($state) => $state instanceof ClassShift ? $state->label() : (string) $state)
                    ->toggleable(),

                TextColumn::make('class.schoolYear.year')
                    ->label('Ano')
                    ->sortable(),

                TextColumn::make('roll_number')
                    ->label('Nº')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof EnrollmentStatus ? $state->label() : (string) $state)
                    ->color(fn ($state) => EnrollmentStatus::colors()[$state instanceof EnrollmentStatus ? $state->value : (string) $state] ?? 'gray'),

                TextColumn::make('enrollment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
            ])

            // ── Filtros ───────────────────────────────────────────────────────
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Ano letivo')
                    ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year', 'id'))
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (filled($value)) {
                            $query->whereHas('class', fn ($q) => $q->where('school_year_id', $value));
                        }
                    })
                    ->default(fn () => SchoolYear::where('is_active', true)->value('id')),

                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->relationship('class', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::options()),

                SelectFilter::make('turno')
                    ->label('Turno')
                    ->options(ClassShift::options())
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('class', fn ($q) => $q->where('shift', $data['value']));
                        }
                    }),

                Filter::make('periodo_cadastro')
                    ->label('Período de cadastro')
                    ->form([
                        DatePicker::make('data_inicio')->label('De')->nullable(),
                        DatePicker::make('data_fim')->label('Até')->nullable(),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['data_inicio'] ?? null, fn ($q, $v) => $q->whereDate('enrollment_date', '>=', $v))
                            ->when($data['data_fim'] ?? null, fn ($q, $v) => $q->whereDate('enrollment_date', '<=', $v));
                    }),
            ])

            // ── Ações individuais por registro (agrupadas no menu ⋯) ────────────
            ->recordActions([
                ActionGroup::make([

                    // ── Visualização / Edição ─────────────────────────────────
                    ViewAction::make()
                        ->label('Ver detalhes')
                        ->modalHeading(fn (Enrollment $record) => "Matrícula — {$record->registration_number}")
                        ->modalWidth('2xl')
                        ->infolist([

                            // ── Cabeçalho: número, status e data ─────────────
                            InfoSection::make()->schema([
                                TextEntry::make('registration_number')
                                    ->label('Nº de Matrícula')
                                    ->fontFamily(FontFamily::Mono)
                                    ->weight(FontWeight::Bold)
                                    ->copyable()
                                    ->copyMessage('Copiado!'),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state instanceof EnrollmentStatus
                                        ? $state->label()
                                        : (string) $state)
                                    ->color(fn ($state) => EnrollmentStatus::colors()[
                                        $state instanceof EnrollmentStatus ? $state->value : (string) $state
                                    ] ?? 'gray'),

                                TextEntry::make('enrollment_date')
                                    ->label('Data da Matrícula')
                                    ->date('d/m/Y'),
                            ])->columns(3),

                            // ── Dados do aluno ────────────────────────────────
                            InfoSection::make('Aluno')
                                ->icon('heroicon-o-user')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('student.name')
                                        ->label('Nome Completo')
                                        ->weight(FontWeight::SemiBold)
                                        ->columnSpan(2),

                                    TextEntry::make('student.registration_number')
                                        ->label('Registro do Aluno')
                                        ->fontFamily(FontFamily::Mono)
                                        ->placeholder('—'),

                                    TextEntry::make('student.cpf')
                                        ->label('CPF')
                                        ->placeholder('—'),

                                    TextEntry::make('student.birth_date')
                                        ->label('Data de Nascimento')
                                        ->date('d/m/Y')
                                        ->placeholder('—'),
                                ]),

                            // ── Dados acadêmicos ──────────────────────────────
                            InfoSection::make('Dados Acadêmicos')
                                ->icon('heroicon-o-academic-cap')
                                ->columns(3)
                                ->schema([
                                    TextEntry::make('schoolYear.year')
                                        ->label('Ano Letivo'),

                                    TextEntry::make('class.gradeLevel.name')
                                        ->label('Série / Etapa')
                                        ->placeholder('—'),

                                    TextEntry::make('class.name')
                                        ->label('Turma')
                                        ->placeholder('—'),

                                    TextEntry::make('class.shift')
                                        ->label('Turno')
                                        ->formatStateUsing(fn ($state) => $state instanceof ClassShift
                                            ? $state->label()
                                            : '—')
                                        ->placeholder('—'),

                                    TextEntry::make('roll_number')
                                        ->label('Nº de Chamada'),

                                    TextEntry::make('operatedBy.name')
                                        ->label('Registrado por')
                                        ->placeholder('Sistema')
                                        ->icon('heroicon-m-user-circle'),
                                ]),

                            // ── Dados de trancamento (somente se Trancada) ────
                            InfoSection::make('Dados do Trancamento')
                                ->icon('heroicon-o-lock-closed')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('locked_reason')
                                        ->label('Motivo')
                                        ->formatStateUsing(fn ($state) => EnrollmentLockReason::options()[$state] ?? $state)
                                        ->placeholder('—'),

                                    TextEntry::make('lock_expires_at')
                                        ->label('Válido até')
                                        ->date('d/m/Y')
                                        ->placeholder('Fim do ano letivo'),
                                ])
                                ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::LOCKED),

                            // ── Dados da transferência interna ────────────────
                            InfoSection::make('Dados da Transferência')
                                ->icon('heroicon-o-arrows-right-left')
                                ->schema([
                                    TextEntry::make('transfer_reason')
                                        ->label('Motivo')
                                        ->placeholder('—'),
                                ])
                                ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::TRANSFERRED_INTERNAL),

                            // ── Dados da transferência externa ────────────────
                            InfoSection::make('Dados da Transferência Externa')
                                ->icon('heroicon-o-arrow-top-right-on-square')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('transfer_destination')
                                        ->label('Instituição de Destino')
                                        ->placeholder('Não informado'),

                                    TextEntry::make('transfer_reason')
                                        ->label('Motivo')
                                        ->placeholder('—'),
                                ])
                                ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::TRANSFERRED_EXTERNAL),

                            // ── Dados do cancelamento ─────────────────────────
                            InfoSection::make('Dados do Cancelamento')
                                ->icon('heroicon-o-x-circle')
                                ->schema([
                                    TextEntry::make('cancel_reason')
                                        ->label('Motivo')
                                        ->placeholder('—'),

                                    TextEntry::make('cancel_observations')
                                        ->label('Observações Adicionais')
                                        ->placeholder('—'),
                                ])
                                ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::CANCELED),
                        ]),

                    EditAction::make()->label('Editar'),

                    // ── Documentos PDF ────────────────────────────────────────
                    Action::make('pdfComprovante')
                        ->label('Emitir Comprovante')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->url(fn (Enrollment $record) => route('pdf.enrollment.comprovante', $record->id))
                        ->openUrlInNewTab(),

                    Action::make('pdfTransferenciaInterna')
                        ->label('PDF Transferência de Turma')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::TRANSFERRED_INTERNAL)
                        ->url(fn (Enrollment $record) => route('pdf.enrollment.transferencia-interna', $record->id))
                        ->openUrlInNewTab(),

                    Action::make('pdfTransferenciaExterna')
                        ->label('Declaração de Transferência')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::TRANSFERRED_EXTERNAL)
                        ->url(fn (Enrollment $record) => route('pdf.enrollment.transferencia-externa', $record->id))
                        ->openUrlInNewTab(),

                    Action::make('pdfTrancamento')
                        ->label('Comprovante de Trancamento')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::LOCKED)
                        ->url(fn (Enrollment $record) => route('pdf.enrollment.trancamento', $record->id))
                        ->openUrlInNewTab(),

                    Action::make('pdfCancelamento')
                        ->label('Termo de Cancelamento')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::CANCELED)
                        ->url(fn (Enrollment $record) => route('pdf.enrollment.cancelamento', $record->id))
                        ->openUrlInNewTab(),

                    // ── Operações de status ───────────────────────────────────

                    // ── Trancar matrícula ─────────────────────────────────────
                Action::make('trancar')
                    ->label('Trancar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::ACTIVE)
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
                    ->action(function (array $data, Enrollment $record): void {
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'          => EnrollmentStatus::LOCKED,
                            'locked_reason'   => $data['locked_reason'],
                            'lock_expires_at' => $data['lock_expires_at'] ?? null,
                            'operated_by_user_id' => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'trancamento',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::LOCKED->value,
                            observacao: $data['observacao'] ?? null,
                        );

                        Notification::make()
                            ->title('Matrícula trancada')
                            ->body("Nº {$record->registration_number} — trancada com sucesso.")
                            ->warning()
                            ->send();
                    }),

                // ── Reativar matrícula (de Trancada → Ativa) ─────────────────
                Action::make('reativar')
                    ->label('Reativar')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::LOCKED)
                    ->modalHeading('Reativar Matrícula')
                    ->modalDescription('A matrícula voltará ao status Ativa com todos os vínculos originais intactos.')
                    ->form([
                        Textarea::make('observacao')
                            ->label('Justificativa de Reativação')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, Enrollment $record): void {
                        $statusAnterior = $record->status?->value;

                        $record->update([
                            'status'          => EnrollmentStatus::ACTIVE,
                            'locked_reason'   => null,
                            'lock_expires_at' => null,
                            'operated_by_user_id' => auth()->id(),
                        ]);

                        EnrollmentLog::registrar(
                            enrollment: $record,
                            acao: 'reativacao',
                            statusAnterior: $statusAnterior,
                            statusNovo: EnrollmentStatus::ACTIVE->value,
                            observacao: $data['observacao'],
                        );

                        Notification::make()
                            ->title('Matrícula reativada')
                            ->body("Nº {$record->registration_number} — reativada com sucesso.")
                            ->success()
                            ->send();
                    }),

                // ── Transferência interna (entre turmas) ──────────────────────
                Action::make('transferirTurma')
                    ->label('Transferir Turma')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::ACTIVE)
                    ->modalHeading('Transferência entre Turmas')
                    ->modalDescription('O aluno será transferido para outra turma dentro do mesmo ano letivo. As notas e frequências são preservadas.')
                    ->form([
                        Select::make('class_id')
                            ->label('Turma de Destino')
                            ->options(function (Enrollment $record) {
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
                    ->action(function (array $data, Enrollment $record): void {
                        // Verifica disponibilidade de vagas na turma destino
                        if (! Enrollment::classHasSlot((int) $data['class_id'])) {
                            $user = auth()->user();
                            // Somente TI pode ultrapassar o limite de vagas
                            if (! $user?->hasAnyRole(['admin', 'ti'])) {
                                Notification::make()
                                    ->title('Turma sem vagas')
                                    ->body('A turma de destino atingiu a capacidade máxima. Contate o perfil TI para forçar a transferência.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                        }

                        // Verifica se o aluno já possui matrícula ativa na turma destino
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
                                ->body('Não é permitido ter duas matrículas ativas no mesmo ano letivo para a mesma turma.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $statusAnterior = $record->status?->value;
                        $turmaOrigemNome = $record->class?->name;

                        // Encerra a matrícula atual como Transferida Interna
                        $record->update([
                            'status'          => EnrollmentStatus::TRANSFERRED_INTERNAL,
                            'transfer_type'   => 'internal',
                            'transfer_reason' => $data['transfer_reason'],
                            'operated_by_user_id' => auth()->id(),
                        ]);

                        // Cria nova matrícula na turma destino vinculando ao histórico anterior
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
                    }),

                // ── Transferência externa (outra instituição) ─────────────────
                Action::make('transferirExterna')
                    ->label('Transferência Externa')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('purple')
                    ->visible(fn (Enrollment $record) => in_array($record->status, [
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
                    ->action(function (array $data, Enrollment $record): void {
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
                            observacao: "Destino: " . ($data['transfer_destination'] ?? 'não informado') . ". Motivo: {$data['transfer_reason']}",
                        );

                        Notification::make()
                            ->title('Transferência externa registrada')
                            ->body("Matrícula {$record->registration_number} encerrada por transferência externa.")
                            ->success()
                            ->send();
                    }),

                // ── Cancelar matrícula ────────────────────────────────────────
                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Enrollment $record) => in_array($record->status, [
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
                    ->action(function (array $data, Enrollment $record): void {
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

                        Notification::make()
                            ->title('Matrícula cancelada')
                            ->body("Nº {$record->registration_number} — cancelada. O histórico acadêmico é preservado.")
                            ->danger()
                            ->send();
                    }),

                // ── Reverter cancelamento (somente TI) ───────────────────────
                Action::make('reverterCancelamento')
                    ->label('Reverter Cancelamento')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Enrollment $record) => $record->status === EnrollmentStatus::CANCELED
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
                    ->action(function (array $data, Enrollment $record): void {
                        // Dupla verificação de autorização no backend
                        if (! auth()->user()?->hasAnyRole(['admin', 'ti'])) {
                            Notification::make()->title('Acesso negado')->danger()->send();
                            return;
                        }

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

                        Notification::make()
                            ->title('Cancelamento revertido')
                            ->body("Nº {$record->registration_number} — matrícula reativada pelo perfil TI.")
                            ->warning()
                            ->send();
                    }),

                ]) // fecha ActionGroup::make([...])
            ])

            // ── Ações em lote ─────────────────────────────────────────────────
            ->toolbarActions([
                BulkActionGroup::make([

                    // Rematrícula em lote
                    BulkAction::make('rematricula')
                        ->label('Rematrícula em Lote')
                        ->icon('heroicon-o-arrow-path')
                        ->deselectRecordsAfterCompletion()
                        ->form([
                            Select::make('school_year_id')
                                ->label('Ano Letivo de Destino')
                                ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year', 'id'))
                                ->required()
                                ->searchable(),

                            Select::make('class_id')
                                ->label('Turma de Destino')
                                ->helperText('Deixe em branco para manter a mesma série/turma equivalente.')
                                ->options(fn () => SchoolClass::with('gradeLevel', 'schoolYear')
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [
                                        $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                    ])
                                )
                                ->nullable()
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data) {
                            $criadas  = 0;
                            $ignoradas = 0;

                            foreach ($records as $enrollment) {
                                // Apenas matrículas ativas ou concluídas são elegíveis
                                if (! in_array($enrollment->status, [
                                    EnrollmentStatus::ACTIVE,
                                    EnrollmentStatus::COMPLETED,
                                ])) {
                                    $ignoradas++;
                                    continue;
                                }

                                $classId = $data['class_id'] ?? $enrollment->class_id;

                                // Verifica se já existe matrícula ativa no ano destino
                                $jaExiste = Enrollment::where('student_id', $enrollment->student_id)
                                    ->where('school_year_id', $data['school_year_id'])
                                    ->whereIn('status', [
                                        EnrollmentStatus::ACTIVE->value,
                                        EnrollmentStatus::LOCKED->value,
                                    ])
                                    ->exists();

                                if ($jaExiste) {
                                    $ignoradas++;
                                    continue;
                                }

                                // Verifica vagas
                                if (! Enrollment::classHasSlot((int) $classId)) {
                                    $ignoradas++;
                                    continue;
                                }

                                Enrollment::create([
                                    'student_id'             => $enrollment->student_id,
                                    'class_id'               => $classId,
                                    'school_year_id'         => $data['school_year_id'],
                                    'enrollment_date'        => now(),
                                    'status'                 => EnrollmentStatus::ACTIVE,
                                    'previous_enrollment_id' => $enrollment->id,
                                    'operated_by_user_id'    => auth()->id(),
                                ]);

                                $criadas++;
                            }

                            Notification::make()
                                ->title('Rematrícula em lote concluída')
                                ->body("{$criadas} rematrícula(s) criada(s). {$ignoradas} ignorada(s) (já matriculados, sem vagas ou status inelegível).")
                                ->success()
                                ->send();
                        }),

                    // Alteração de status em lote (uso administrativo)
                    BulkAction::make('bulkStatus')
                        ->label('Alterar status')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->form([
                            Select::make('status')
                                ->options(EnrollmentStatus::options())
                                ->required(),
                        ])
                        ->action(fn ($records, $data) => $records->each->update([
                            'status' => $data['status'],
                        ])),

                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $user    = auth()->user();
                            $deleted = 0;
                            $blocked = 0;

                            foreach ($records as $enrollment) {
                                if ($user->can('delete', $enrollment)) {
                                    $enrollment->delete();
                                    $deleted++;
                                } else {
                                    $blocked++;
                                }
                            }

                            if ($blocked > 0) {
                                Notification::make()
                                    ->title('Exclusão em lote')
                                    ->body($deleted > 0
                                        ? "{$deleted} excluída(s). {$blocked} não excluída(s) — possuem notas lançadas. Cancele a matrícula em vez de excluir."
                                        : "Nenhuma excluída — matrículas com notas não podem ser excluídas. Cancele o status em vez de excluir.")
                                    ->warning()
                                    ->send();
                            } elseif ($deleted > 0) {
                                Notification::make()
                                    ->title('Matrículas excluídas')
                                    ->body("{$deleted} matrícula(s) excluída(s).")
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}
