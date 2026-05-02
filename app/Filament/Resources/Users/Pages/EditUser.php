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

class EditUser extends EditRecord {
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array {
        return [
            $this->getResetPasswordAction(),
            $this->getUnlockAction(),
            $this->getInactivateAction(),
            $this->getActivateAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    private function getResetPasswordAction(): Action {
        return Action::make('reset_password')
            ->label('Resetar Senha')
            ->icon('fas-key')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Resetar senha')
            ->modalDescription('Uma senha temporária será gerada. O usuário deverá trocá-la no próximo acesso.')
            ->action(function () {
                $tempPassword = $this->record->resetToTemporaryPassword();

                Notification::make()
                    ->title('Senha redefinida com sucesso')
                    ->body("Senha temporária: **{$tempPassword}**")
                    ->success()
                    ->persistent()
                    ->send();
            })
            ->visible(fn () => $this->isAdminOrTi());
    }

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

    private function isAdminOrTi(): bool {
        return (bool) auth()->user()?->hasAnyRole(['admin', 'ti']);
    }

    public function mount(int|string $record): void {
        parent::mount($record);

        if (auth()->user()?->hasRole('secretaria') && ! $this->isAdminOrTi()) {
            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('index');
    }
}
