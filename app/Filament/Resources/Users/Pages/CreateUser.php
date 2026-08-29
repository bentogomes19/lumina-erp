<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

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
        $data['force_password_change'] = $data['force_password_change'] ?? true;
        $data['login_attempts']        = 0;
        $data['locked_at']             = null;

        return $data;
    }

    /**
     * Sincroniza o perfil e cria o vínculo com aluno ou professor após cadastrar o usuário.
     *
     * @return void
     */
    protected function afterCreate(): void {
        $role = $this->form->getState()['role'] ?? null;

        if (!$role) {
            return;
        }

        $this->record->syncRoles([$role]);

        if ($role === 'student' && !$this->record->student()->exists()) {
            Student::create([
                'uuid'    => (string) Str::uuid(),
                'user_id' => $this->record->id,
                'name'    => $this->record->name,
                'email'   => $this->record->email,
            ]);
        }

        if ($role === 'teacher' && !$this->record->teacher()->exists()) {
            Teacher::create([
                'uuid'    => (string) Str::uuid(),
                'user_id' => $this->record->id,
                'name'    => $this->record->name,
                'email'   => $this->record->email,
            ]);
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
