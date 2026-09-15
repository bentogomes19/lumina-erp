<?php

namespace App\Filament\Resources\SchoolYears\Tables;

use App\Enums\SchoolYearStatus;
use App\Services\Domain\DeletionGuard;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class SchoolYearsTable
{
    /**
     * Configura as colunas, os filtros e as ações da tabela de anos letivos.
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('year')
                    ->label('Ano')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('starts_at')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fim')
                    ->date('d/m/Y'),

                TextColumn::make('terms_count')
                    ->label('Períodos')
                    ->counts('terms')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SchoolYearStatus ? $state->label() : (string) $state)
                    ->color(fn ($state) => $state instanceof SchoolYearStatus ? $state->color() : 'gray'),
            ])
            ->defaultSort('year', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(SchoolYearStatus::toArray()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records): void {
                            $guard = app(DeletionGuard::class);
                            $deleted = 0;
                            $blocked = collect();

                            foreach ($records as $record) {
                                $blockingLinks = $guard->blockingLinks($record);

                                if ($blockingLinks->isNotEmpty()) {
                                    $blocked->push(sprintf(
                                        '%s (#%s): %s',
                                        $record->year,
                                        $record->getKey(),
                                        $blockingLinks->implode(', '),
                                    ));

                                    continue;
                                }

                                $deleted += (int) $record->delete();
                            }

                            $body = sprintf(
                                '%d %s e %d %s.',
                                $deleted,
                                $deleted === 1 ? 'ano letivo excluído' : 'anos letivos excluídos',
                                $blocked->count(),
                                $blocked->count() === 1 ? 'ano letivo bloqueado' : 'anos letivos bloqueados',
                            );

                            if ($blocked->isNotEmpty()) {
                                $body .= "\n\nAltere os bloqueados para o status \"encerrado\":\n• ".
                                    $blocked->implode("\n• ");
                            }

                            $notification = Notification::make()
                                ->title('Exclusão de anos letivos')
                                ->body($body);

                            ($blocked->isNotEmpty() ? $notification->warning() : $notification->success())->send();
                        })
                        ->successNotification(null)
                        ->failureNotification(null),
                ]),
            ]);
    }
}
