<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\Auth\FirstAccessInvitationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord {

    protected static string $resource = UserResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            $this->getSendInvitationAction(),
            $this->getRevokeInvitationAction(),
            $this->getUnlockAction(),
            $this->getInactivateAction(),
            $this->getActivateAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Retorna a ação usada para enviar ou reenviar o convite de acesso.
     *
     * @return Action
     */
    private function getSendInvitationAction(): Action {
        return Action::make('send_first_access_invitation')
            ->label('Enviar/Reenviar convite')
            ->icon('fas-key')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Enviar convite de acesso')
            ->modalDescription('O link anterior será revogado. O novo convite expirará e poderá ser usado somente uma vez.')
            ->action(function () {
                $url = app(FirstAccessInvitationService::class)->issue($this->record);

                Notification::make()
                    ->title('Convite enviado')
                    ->body('O link foi enviado por e-mail e pode ser entregue ao usuário por um canal seguro.')
                    ->actions([
                        Action::make('openInvitation')
                            ->label('Abrir link do convite')
                            ->url($url)
                            ->openUrlInNewTab(),
                    ])
                    ->success()
                    ->duration(15000)
                    ->send();

                $this->refreshFormData(['force_password_change']);
            })
            ->visible(fn () => !$this->record->trashed()
                && $this->record->active
                && !$this->record->is_locked
                && $this->isAdminOrTi());
    }

    /**
     * Retorna a ação usada para revogar o convite emitido.
     *
     * @return Action
     */
    private function getRevokeInvitationAction(): Action {
        return Action::make('revoke_first_access_invitation')
            ->label('Revogar convite')
            ->icon('fas-link-slash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Revogar convite de acesso')
            ->modalDescription('O link emitido deixará de funcionar. O usuário continuará sem acesso até receber um novo convite.')
            ->action(function () {
                app(FirstAccessInvitationService::class)->revoke($this->record);

                Notification::make()
                    ->title('Convite revogado')
                    ->success()
                    ->send();
            })
            ->visible(fn () => $this->record->force_password_change && $this->isAdminOrTi());
    }

    /**
     * Retorna a ação usada para desbloquear o usuário.
     *
     * @return Action
     */
    private function getUnlockAction(): Action {
        return Action::make('unlock')
            ->label('Desbloquear')
            ->icon('fas-lock-open')
            ->color('success')
            ->requiresConfirmation()
            ->action(function () {
                $this->record->unlock();
                Notification::make()->title('Usuário desbloqueado')->success()->send();
                $this->refreshFormData(['locked_at', 'login_attempts']);
            })
            ->visible(fn () => $this->record->locked_at && $this->isAdminOrTi());
    }

    /**
     * Retorna a ação usada para inativar o usuário.
     *
     * @return Action
     */
    private function getInactivateAction(): Action {
        return Action::make('inactivate')
            ->label('Inativar')
            ->icon('fas-circle-xmark')
            ->color('danger')
            ->form([
                Textarea::make('inactive_reason')
                    ->label('Motivo da inativação')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data) {
                $this->record->inactivate($data['inactive_reason']);
                Notification::make()->title('Usuário inativado')->success()->send();
                $this->refreshFormData(['active', 'inactive_reason']);
            })
            ->visible(fn () => $this->record->active && $this->isAdminOrTi());
    }

    /**
     * Retorna a ação usada para reativar o usuário.
     *
     * @return Action
     */
    private function getActivateAction(): Action {
        return Action::make('activate')
            ->label('Reativar')
            ->icon('fas-circle-check')
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('O usuário voltará a ter acesso ao sistema.')
            ->action(function () {
                $this->record->activate();
                Notification::make()->title('Usuário reativado')->success()->send();
                $this->refreshFormData(['active', 'locked_at', 'login_attempts', 'inactive_reason']);
            })
            ->visible(fn () => !$this->record->active && $this->isAdminOrTi());
    }

    /**
     * Determina se o usuário atual pertence aos perfis de administrador ou TI.
     *
     * @return bool
     */
    private function isAdminOrTi(): bool {
        return (bool) auth()->user()?->hasAnyRole(['admin', 'ti']);
    }

    /**
     * Inicializa o estado necessário para exibir a página.
     *
     * @param int|string $record
     *
     * @return void
     */
    public function mount(int|string $record): void {
        parent::mount($record);

        if (auth()->user()?->hasRole('secretaria') && !$this->isAdminOrTi()) {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    /**
     * Retorna a URL usada após concluir a operação.
     *
     * @return string
     */
    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('index');
    }
}
