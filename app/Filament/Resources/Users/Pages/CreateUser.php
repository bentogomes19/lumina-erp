<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Teacher;
use App\Services\Auth\FirstAccessInvitationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateUser extends CreateRecord {

    protected static string $resource = UserResource::class;

    /**
     * Força troca de senha no primeiro acesso e zera tentativas.
     *
     * @param array $data
     *
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array {
        if (($data['role'] ?? null) === 'student') {
            throw ValidationException::withMessages([
                'role' => 'Crie o acesso do aluno pelo recurso Alunos ou pelo fluxo Matricular aluno.',
            ]);
        }

        $data['force_password_change'] = $data['force_password_change'] ?? true;
        $data['login_attempts']        = 0;
        $data['locked_at']             = null;
        $data['password']              = Hash::make(Str::random(64));

        return $data;
    }

    /**
     * Sincroniza o perfil, cria o vínculo docente e envia o convite após cadastrar o usuário.
     *
     * @return void
     */
    protected function afterCreate(): void {
        $role = $this->form->getState()['role'] ?? null;

        if (!$role) {
            return;
        }

        $this->record->syncRoles([$role]);

        if ($role === 'teacher' && !$this->record->teacher()->exists()) {
            Teacher::create([
                'uuid'    => (string) Str::uuid(),
                'user_id' => $this->record->id,
                'name'    => $this->record->name,
                'email'   => $this->record->email,
            ]);
        }

        $url = app(FirstAccessInvitationService::class)->issue($this->record);

        Notification::make()
            ->title('Usuário criado e convite enviado')
            ->body('O link é descartável e também pode ser entregue ao usuário por um canal seguro.')
            ->actions([
                Action::make('openInvitation')
                    ->label('Abrir link do convite')
                    ->url($url)
                    ->openUrlInNewTab(),
            ])
            ->success()
            ->duration(15000)
            ->send();
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
