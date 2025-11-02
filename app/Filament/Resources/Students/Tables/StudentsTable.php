<?php

namespace App\Filament\Resources\Students\Tables;

use App\Enums\StudentStatus;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class StudentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('registration_number')->label('Matrícula')->searchable()->copyable(),
                TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                TextColumn::make('age')
                    ->label('Idade')
                    ->getStateUsing(fn($record) => $record?->birth_date
                        ? Carbon::parse($record->birth_date)->age
                        : null
                    )
                    ->placeholder('—')
                    ->alignRight()
                    // ordena por nascimento (mais novo/mais velho), mantendo nulos no fim
                    ->sortable(query: function ($query, string $direction) {
                        return $query
                            ->orderByRaw('birth_date IS NULL') // nulos por último
                            ->orderBy('birth_date', $direction === 'asc' ? 'desc' : 'asc');
                    }),
                TextColumn::make('classes.name')->label('Turmas')->limit(20)->toggleable(),
                TextColumn::make('email')->label('E-mail')->toggleable(),
                TextColumn::make('phone_number')->label('Telefone')->toggleable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        $value = $state instanceof BackedEnum ? $state->value : $state;   // enum ou string
                        return StudentStatus::options()[$value] ?? '—';
                    })
                    ->colors([
                        'success' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::ACTIVE->value,
                        'warning' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::SUSPENDED->value,
                        'info' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::GRADUATED->value,
                        'gray' => fn($state) => ($state instanceof BackedEnum ? $state->value : $state) === StudentStatus::INACTIVE->value,
                    ]),
                TextColumn::make('enrollment_date')->label('Ingresso')->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StudentStatus::options()),
                SelectFilter::make('class_id')
                    ->label('Turma (Ano atual)')
                    ->relationship('classes', 'name')
                    ->searchable()->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulkStatus')
                        ->label('Alterar status (selecionados)')
                        ->icon('heroicon-o-adjustments-vertical')
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Novo status')
                                ->options(StudentStatus::options())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $status = $data['status'];
                            $records->each->update([
                                'status' => $status,
                                'status_changed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Status atualizado para os registros selecionados.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
