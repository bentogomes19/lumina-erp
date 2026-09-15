<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Services\Domain\DeletionGuard;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record && auth()->user()?->can('delete', $this->record)),
            ForceDeleteAction::make()
                ->visible(fn () => $this->record && auth()->user()?->can('forceDelete', $this->record))
                ->before(function (ForceDeleteAction $action, Model $record): void {
                    $blockingLinks = app(DeletionGuard::class)->blockingLinks($record);

                    if ($blockingLinks->isEmpty()) {
                        return;
                    }

                    Notification::make()
                        ->title('Exclusão definitiva bloqueada')
                        ->body($blockingLinks->map(fn (string $link): string => "- {$link}")->implode("\n"))
                        ->danger()
                        ->send();

                    $action->cancel();
                }),
            RestoreAction::make(),
        ];
    }
}
