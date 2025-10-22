<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $role = $this->record->role ?? null;

        if ($role && method_exists($this->record, 'assignRole')) {
            $this->record->assignRole($role);
        }

        // Cria automaticamente o vínculo
        if ($role === 'student') {
            \App\Models\Student::create([
                'uuid' => Str::uuid(),
                'user_id' => $this->record->id,
                'name' => $this->record->name,
                'email' => $this->record->email,
            ]);
        }

        if ($role === 'teacher') {
            \App\Models\Teacher::create([
                'uuid' => Str::uuid(),
                'user_id' => $this->record->id,
                'name' => $this->record->name,
                'email' => $this->record->email,
            ]);
        }
    }

}
