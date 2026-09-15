<?php

namespace App\Filament\Actions;

use App\Services\Domain\DeletionGuard;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WarnedDeleteBulkAction extends DeleteBulkAction
{
    private string $impactWarningTitle = 'Atenção: registros com vínculos ativos';

    private string $singularRecordLabel = 'registro';

    private string $pluralRecordLabel = 'registros';

    protected function setUp(): void
    {
        parent::setUp();

        $this->before(function (Collection $records): void {
            $guard = app(DeletionGuard::class);
            $linkedRecords = collect();
            $totalLinks = 0;

            foreach ($records as $record) {
                $blockingLinks = $guard->blockingLinks($record);

                if ($blockingLinks->isEmpty()) {
                    continue;
                }

                $linkedRecords->push([
                    'record' => $record,
                    'links' => $blockingLinks,
                ]);
                $totalLinks += $this->sumLinkCounts($blockingLinks);
            }

            if ($linkedRecords->isEmpty()) {
                return;
            }

            $linkedCount = $linkedRecords->count();
            $recordLabel = $records->count() === 1 ? $this->singularRecordLabel : $this->pluralRecordLabel;
            $linkLabel = $totalLinks === 1 ? 'vínculo encontrado' : 'vínculos encontrados';
            $details = $linkedRecords
                ->map(fn (array $item): string => sprintf(
                    '• %s: %s',
                    $this->recordLabel($item['record']),
                    $item['links']->implode(', '),
                ))
                ->implode("\n");

            Notification::make()
                ->title($this->impactWarningTitle)
                ->body(sprintf(
                    "%d de %d %s selecionados possuem vínculos ativos (%s %s).\n\n%s",
                    $linkedCount,
                    $records->count(),
                    $recordLabel,
                    number_format($totalLinks, 0, ',', '.'),
                    $linkLabel,
                    $details,
                ))
                ->warning()
                ->send();
        });
    }

    public function impactWarning(string $title, string $singularRecordLabel, string $pluralRecordLabel): static
    {
        $this->impactWarningTitle = $title;
        $this->singularRecordLabel = $singularRecordLabel;
        $this->pluralRecordLabel = $pluralRecordLabel;

        return $this;
    }

    /** @param Collection<int, string> $links */
    private function sumLinkCounts(Collection $links): int
    {
        return $links->sum(function (string $link): int {
            preg_match('/^([\d.]+)/', $link, $matches);

            return (int) str_replace('.', '', $matches[1] ?? '0');
        });
    }

    private function recordLabel(Model $record): string
    {
        $name = trim((string) $record->getAttribute('name'));

        return filled($name)
            ? sprintf('%s (#%s)', $name, $record->getKey())
            : sprintf('#%s', $record->getKey());
    }
}
