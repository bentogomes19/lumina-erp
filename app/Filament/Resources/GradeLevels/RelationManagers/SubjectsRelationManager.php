<?php

namespace App\Filament\Resources\GradeLevels\RelationManagers;

use App\Enums\SubjectCategory;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class SubjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'subjects';
    protected static ?string $title = 'Disciplinas da Série';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Disciplina')
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Componente Curricular')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->category?->label() ?? '—')
                    ->color(fn ($record) => match ($record->category) {
                        SubjectCategory::LINGUAGENS => 'info',
                        SubjectCategory::MATEMATICA => 'warning',
                        SubjectCategory::CIENCIAS_NATUREZA => 'success',
                        SubjectCategory::CIENCIAS_HUMANAS => 'gray',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('pivot.hours_weekly')
                    ->label('Aulas/Semana')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('addSubjects')
                    ->label('Adicionar disciplina(s)')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Vincular disciplina(s) à série')
                    ->form([
                        Select::make('subject_ids')
                            ->label('Disciplinas')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->options(
                                Subject::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->required(),

                        TextInput::make('hours_weekly')
                            ->label('Aulas por semana (aplicado a todas)')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function ($livewire, array $data) {
                        $gradeLevel = $livewire->ownerRecord;
                        $ids = Arr::wrap($data['subject_ids'] ?? []);
                        if (empty($ids)) return;

                        $hours = (int) $data['hours_weekly'];
                        $attach = [];
                        foreach ($ids as $id) {
                            $attach[(int) $id] = ['hours_weekly' => $hours];
                        }

                        $gradeLevel->subjects()->syncWithoutDetaching($attach);
                    })
                    ->successNotificationTitle('Disciplinas vinculadas com sucesso'),
            ])
            ->recordActions([
                // ✅ EDITAR SOMENTE O PIVOT
                Action::make('editarVinculo')
                    ->label('Editar vínculo')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar carga horária (pivot)')
                    ->form(fn ($record) => [
                        TextInput::make('hours_weekly')
                            ->label('Aulas por semana')
                            ->numeric()
                            ->minValue(1)
                            ->default((int) ($record->pivot->hours_weekly ?? 1))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->pivot->update([
                            'hours_weekly' => (int) $data['hours_weekly'],
                        ]);
                    })
                    ->successNotificationTitle('Vínculo atualizado'),

                // ✅ DESVINCULAR (detach)
                Action::make('removerVinculo')
                    ->label('Remover da série')
                    ->icon('heroicon-o-link-slash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($livewire, $record) {
                        $gradeLevel = $livewire->ownerRecord;
                        $gradeLevel->subjects()->detach($record->id);
                    })
                    ->successNotificationTitle('Disciplina removida da série'),
            ]);
    }
}
