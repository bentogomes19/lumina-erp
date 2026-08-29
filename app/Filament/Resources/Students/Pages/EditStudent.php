<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditStudent extends EditRecord {

    protected static string $resource = StudentResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record && auth()->user()?->can('delete', $this->record)),
            ForceDeleteAction::make()
                ->visible(fn () => $this->record && auth()->user()?->can('forceDelete', $this->record)),
            RestoreAction::make(),
        ];
    }
}
