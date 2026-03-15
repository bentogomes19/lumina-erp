<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => "https://ui-avatars.com/api/?name=" . urlencode($record->name) . "&background=random")
                    ->size(36),

                TextColumn::make('name')
                    ->label('Usuário')
                    ->description(fn ($record) => $record->email)
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('roles.name')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn ($state) => UserForm::ROLE_LABELS[$state] ?? ucfirst($state ?? '—'))
                    ->color(fn ($state) => match ($state) {
                        'ti'         => 'danger',
                        'admin'      => 'danger',
                        'secretaria' => 'warning',
                        'financeiro' => 'info',
                        'teacher'    => 'primary',
                        'student'    => 'success',
                        default      => 'gray',
                    }),

                TextColumn::make('cpf')
                    ->label('CPF')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cellphone')
                    ->label('Celular')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('last_login_at')
                    ->label('Último acesso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca')
                    ->toggleable(),

                IconColumn::make('force_password_change')
                    ->label('Troca senha')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Status: mostra bloqueado com prioridade sobre ativo/inativo
                TextColumn::make('status_display')
                    ->label('Status')
                    ->badge()
                    ->state(function (User $record): string {
                        if ($record->locked_at) {
                            return 'Bloqueado';
                        }
                        return $record->active ? 'Ativo' : 'Inativo';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Ativo'      => 'success',
                        'Inativo'    => 'gray',
                        'Bloqueado'  => 'danger',
                        default      => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options(UserForm::ROLE_LABELS)
                    ->query(fn ($query, $data) => $data['value']
                        ? $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']))
                        : $query
                    ),

                TernaryFilter::make('active')
                    ->label('Situação')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos')
                    ->placeholder('Todos'),

                Filter::make('locked')
                    ->label('Bloqueados')
                    ->query(fn ($query) => $query->whereNotNull('locked_at'))
                    ->toggle(),

                Filter::make('force_password_change')
                    ->label('Aguardando troca de senha')
                    ->query(fn ($query) => $query->where('force_password_change', true))
                    ->toggle(),

                Filter::make('created_at')
                    ->label('Criado em')
                    ->form([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(fn ($query, $data) => $query
                        ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                        ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']))
                    ),

                TrashedFilter::make(),
            ])

            ->recordActions([
                // Ação: Resetar Senha (gera senha temporária + force_password_change)
                Action::make('reset_password')
                    ->label('Resetar Senha')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Resetar senha do usuário')
                    ->modalDescription('Uma senha temporária será gerada e o usuário será obrigado a trocá-la no próximo acesso.')
                    ->action(function (User $record) {
                        $tempPassword = $record->resetToTemporaryPassword();

                        Notification::make()
                            ->title('Senha redefinida')
                            ->body("Senha temporária: **{$tempPassword}**\nEntregue ao usuário com segurança.")
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),

                // Ação: Desbloquear (somente TI/admin)
                Action::make('unlock')
                    ->label('Desbloquear')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Desbloquear usuário')
                    ->modalDescription('O contador de tentativas será zerado e o bloqueio removido.')
                    ->action(function (User $record) {
                        $record->unlock();
                        Notification::make()
                            ->title('Usuário desbloqueado')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record) =>
                        $record->locked_at && auth()->user()?->hasAnyRole(['admin', 'ti'])
                    ),

                // Ação: Inativar com motivo
                Action::make('inactivate')
                    ->label('Inativar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('inactive_reason')
                            ->label('Motivo da inativação')
                            ->required()
                            ->rows(3)
                            ->placeholder('Ex.: Solicitação do usuário, encerramento de contrato...'),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->updateQuietly([
                            'active'          => false,
                            'inactive_reason' => $data['inactive_reason'],
                        ]);
                        Notification::make()
                            ->title('Usuário inativado')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record) =>
                        $record->active && auth()->user()?->hasAnyRole(['admin', 'ti'])
                    ),

                // Ação: Reativar (somente TI/admin)
                Action::make('activate')
                    ->label('Reativar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Reativar usuário')
                    ->modalDescription('O usuário voltará a ter acesso ao sistema.')
                    ->action(function (User $record) {
                        $record->updateQuietly([
                            'active'          => true,
                            'inactive_reason' => null,
                            'locked_at'       => null,
                            'login_attempts'  => 0,
                        ]);
                        Notification::make()
                            ->title('Usuário reativado')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record) =>
                        ! $record->active && auth()->user()?->hasAnyRole(['admin', 'ti'])
                    ),

                EditAction::make()->label('Editar'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_inactivate')
                        ->label('Inativar selecionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('inactive_reason')
                                ->label('Motivo')
                                ->required()
                                ->rows(2),
                        ])
                        ->action(fn ($records, array $data) => $records->each->updateQuietly([
                            'active'          => false,
                            'inactive_reason' => $data['inactive_reason'],
                        ]))
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),

                    BulkAction::make('bulk_activate')
                        ->label('Reativar selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->updateQuietly([
                            'active'          => true,
                            'inactive_reason' => null,
                        ]))
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),

                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),

                    RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),
                ]),
            ]);
    }
}
