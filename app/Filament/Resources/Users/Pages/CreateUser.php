<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\Student;
use App\Models\Teacher;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Força troca de senha no primeiro acesso e zera tentativas.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['force_password_change'] = $data['force_password_change'] ?? true;
        $data['login_attempts']        = 0;
        $data['locked_at']             = null;

        return $data;
    }

    /**
     * Após criar: sincroniza perfil e cria vínculo Student/Teacher se necessário.
     */
    protected function afterCreate(): void
    {
        $role = $this->form->getState()['role'] ?? null;

        if (! $role) {
            return;
        }

        $this->record->syncRoles([$role]);

        if ($role === 'student' && ! $this->record->student()->exists()) {
            Student::create([
                'uuid'    => (string) Str::uuid(),
                'user_id' => $this->record->id,
                'name'    => $this->record->name,
                'email'   => $this->record->email,
            ]);
        }

        if ($role === 'teacher' && ! $this->record->teacher()->exists()) {
            Teacher::create([
                'uuid'    => (string) Str::uuid(),
                'user_id' => $this->record->id,
                'name'    => $this->record->name,
                'email'   => $this->record->email,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
