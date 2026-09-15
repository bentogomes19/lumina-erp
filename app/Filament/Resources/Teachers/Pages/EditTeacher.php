<?php

namespace App\Filament\Resources\Teachers\Pages;

use App\Filament\Resources\Teachers\TeacherResource;
use App\Services\Domain\DeletionGuard;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTeacher extends EditRecord
{
    protected static string $resource = TeacherResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (Model $record): void {
                    $blockingLinks = app(DeletionGuard::class)->blockingLinks($record);

                    if ($blockingLinks->isEmpty()) {
                        return;
                    }

                    Notification::make()
                        ->title('Atenção: professor com vínculos ativos')
                        ->body($blockingLinks->implode(', '))
                        ->warning()
                        ->send();
                }),
            ForceDeleteAction::make()
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

    /**
     * Retorna a URL usada após concluir a operação.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
