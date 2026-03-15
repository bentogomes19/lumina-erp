<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Resetar senha (TI/admin)
            Action::make('reset_password')
                ->label('Resetar Senha')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Resetar senha')
                ->modalDescription('Uma senha temporária será gerada. O usuário deverá trocá-la no próximo acesso.')
                ->action(function () {
                    /** @var User $user */
                    $user = $this->record;
                    $tempPassword = $user->resetToTemporaryPassword();

                    Notification::make()
                        ->title('Senha redefinida com sucesso')
                        ->body("Senha temporária: **{$tempPassword}**")
                        ->success()
                        ->persistent()
                        ->send();
                })
                ->visible(fn () => auth()->user()?->hasAnyRole(['admin', 'ti'])),

            // Desbloquear (somente TI/admin — regra de negócio)
            Action::make('unlock')
                ->label('Desbloquear')
                ->icon('heroicon-o-lock-open')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->unlock();
                    Notification::make()->title('Usuário desbloqueado')->success()->send();
                    $this->refreshFormData(['locked_at', 'login_attempts']);
                })
                ->visible(fn () =>
                    $this->record->locked_at && auth()->user()?->hasAnyRole(['admin', 'ti'])
                ),

            // Inativar com motivo
            Action::make('inactivate')
                ->label('Inativar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('inactive_reason')
                        ->label('Motivo da inativação')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->updateQuietly([
                        'active'          => false,
                        'inactive_reason' => $data['inactive_reason'],
                    ]);
                    Notification::make()->title('Usuário inativado')->success()->send();
                    $this->refreshFormData(['active', 'inactive_reason']);
                })
                ->visible(fn () =>
                    $this->record->active && auth()->user()?->hasAnyRole(['admin', 'ti'])
                ),

            // Reativar (somente TI/admin — regra de negócio)
            Action::make('activate')
                ->label('Reativar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('O usuário voltará a ter acesso ao sistema.')
                ->action(function () {
                    $this->record->updateQuietly([
                        'active'          => true,
                        'inactive_reason' => null,
                        'locked_at'       => null,
                        'login_attempts'  => 0,
                    ]);
                    Notification::make()->title('Usuário reativado')->success()->send();
                    $this->refreshFormData(['active', 'locked_at', 'login_attempts', 'inactive_reason']);
                })
                ->visible(fn () =>
                    ! $this->record->active && auth()->user()?->hasAnyRole(['admin', 'ti'])
                ),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Secretaria só visualiza — impede acesso à edição.
     */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (auth()->user()?->hasRole('secretaria') && ! auth()->user()?->hasAnyRole(['admin', 'ti'])) {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
