<?php

namespace App\Filament\Actions;

use App\Services\Domain\DeletionGuard;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Throwable;

class GuardedForceDeleteBulkAction extends ForceDeleteBulkAction
{
    /** Configura a exclusão definitiva para sempre consultar os vínculos acadêmicos. */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->action(function (Collection $records): void {
                $guard = app(DeletionGuard::class);
                $deleted = 0;
                $blocked = collect();

                foreach ($records as $record) {
                    $blockingLinks = $guard->blockingLinks($record);

                    if ($blockingLinks->isNotEmpty()) {
                        $blocked->push([
                            'record' => $record,
                            'reason' => $blockingLinks->implode(', '),
                        ]);

                        continue;
                    }

                    try {
                        if ($record->forceDelete()) {
                            $deleted++;
                        } else {
                            $blocked->push([
                                'record' => $record,
                                'reason' => 'a exclusão não pôde ser concluída',
                            ]);
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $blocked->push([
                            'record' => $record,
                            'reason' => 'a exclusão não pôde ser concluída por integridade dos dados',
                        ]);
                    }
                }

                $blockedCount = $blocked->count();
                $body = sprintf(
                    '%d %s e %d %s.',
                    $deleted,
                    $deleted === 1 ? 'registro excluído' : 'registros excluídos',
                    $blockedCount,
                    $blockedCount === 1 ? 'registro bloqueado' : 'registros bloqueados',
                );

                if ($blocked->isNotEmpty()) {
                    $details = $blocked
                        ->map(fn (array $item): string => sprintf(
                            '• %s: %s',
                            $this->recordLabel($item['record']),
                            $item['reason'],
                        ))
                        ->implode("\n");
                    $body .= "\n\nBloqueados:\n{$details}";
                }

                $notification = Notification::make()
                    ->title('Exclusão definitiva em lote')
                    ->body($body);

                ($blocked->isNotEmpty() ? $notification->warning() : $notification->success())->send();
            })
            ->successNotification(null)
            ->failureNotification(null);
    }

    /** Retorna um rótulo que identifica inequivocamente o registro bloqueado. */
    private function recordLabel(Model $record): string
    {
        $name = trim((string) $record->getAttribute('name'));

        return filled($name)
            ? sprintf('%s (#%s)', $name, $record->getKey())
            : sprintf('#%s', $record->getKey());
    }
}
