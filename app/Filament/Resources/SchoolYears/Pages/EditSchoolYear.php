<?php

namespace App\Filament\Resources\SchoolYears\Pages;

use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Services\Domain\DeletionGuard;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSchoolYear extends EditRecord
{
    protected static string $resource = SchoolYearResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Model $record): void {
                    $blockingLinks = app(DeletionGuard::class)->blockingLinks($record);

                    if ($blockingLinks->isEmpty()) {
                        return;
                    }

                    Notification::make()
                        ->title('Exclusão do ano letivo bloqueada')
                        ->body(
                            "Altere o status para \"encerrado\" em vez de excluir.\n\n".
                            $blockingLinks->map(fn (string $link): string => "- {$link}")->implode("\n"),
                        )
                        ->danger()
                        ->send();

                    $action->cancel();
                }),
        ];
    }
}
