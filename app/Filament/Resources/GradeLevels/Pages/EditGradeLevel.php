<?php

namespace App\Filament\Resources\GradeLevels\Pages;

use App\Filament\Resources\GradeLevels\GradeLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGradeLevel extends EditRecord {

    protected static string $resource = GradeLevelResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Determina se o usuário pode visualizar os registros do recurso.
     *
     * @return bool
     */
    public static function canViewAny(): bool {
        return auth()->user()?->can('roles.view') ?? false;
    }
    /**
     * Determina se o usuário pode criar um registro.
     *
     * @return bool
     */
    public static function canCreate(): bool {
        return auth()->user()?->can('roles.create') ?? false;
    }
    /**
     * Determina se o usuário pode editar o registro.
     *
     * @param mixed $record
     *
     * @return bool
     */
    public static function canEdit($record): bool {
        return auth()->user()?->can('roles.update') ?? false;
    }
    /**
     * Determina se o usuário pode excluir o registro.
     *
     * @param mixed $record
     *
     * @return bool
     */
    public static function canDelete($record): bool {
        return auth()->user()?->can('roles.delete') ?? false;
    }
}
