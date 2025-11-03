<?php

namespace App\Filament\Resources\Subjects\Tables;

use App\Enums\SubjectCategory;
use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class SubjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('category')
                    ->label('Componente')
                    ->formatStateUsing(function ($state) {
                        // $state pode vir como enum (SubjectCategory) ou string (ex.: 'linguagens')
                        if ($state instanceof SubjectCategory) {
                            return $state->label();
                        }
                        return SubjectCategory::tryFrom((string) $state)?->label() ?? '—';
                    })
                    ->colors([
                        // mapeia pela string do value (funciona com enum ou string)
                        'primary' => fn ($state) => ($state instanceof SubjectCategory ? $state->value : $state) === SubjectCategory::LINGUAGENS->value,
                        'info'    => fn ($state) => ($state instanceof SubjectCategory ? $state->value : $state) === SubjectCategory::CIENCIAS_NATUREZA->value,
                        'warning' => fn ($state) => ($state instanceof SubjectCategory ? $state->value : $state) === SubjectCategory::MATEMATICA->value,
                        'success' => fn ($state) => ($state instanceof SubjectCategory ? $state->value : $state) === SubjectCategory::CIENCIAS_HUMANAS->value,
                    ])
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'Ativa' : 'Inativa')
                    ->colors([
                        'success' => 'active',
                        'gray'    => 'inactive',
                    ])
                    ->sortable(),

                TextColumn::make('grade_levels_count')
                    ->label('Séries')
                    ->counts('gradeLevels')
                    ->tooltip('Quantidade de séries vinculadas')
                    ->toggleable(),

                TextColumn::make('created_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('category')
                    ->label('Componente')
                    ->options(SubjectCategory::toArray()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(['active'=>'Ativa','inactive'=>'Inativa']),
            ])
            ->recordActions([
                Action::make('vincularTurmaProfessor')
                    ->label('Vincular turma/professor')
                    ->icon('heroicon-o-link')
                    ->modalHeading(fn ($record) => "Vincular {$record->name}")
                    ->form([
                        FormSelect::make('class_id')
                            ->label('Turma')
                            ->options(function ($record) {
                                // se a disciplina já está ligada a séries, mostra só as turmas dessas séries
                                $gradeLevelIds = $record->gradeLevels()->pluck('grade_levels.id');
                                $query = SchoolClass::query()->with('gradeLevel','schoolYear');
                                if ($gradeLevelIds->isNotEmpty()) {
                                    $query->whereIn('grade_level_id', $gradeLevelIds);
                                }
                                return $query->orderBy('name')->get()
                                    ->mapWithKeys(fn($c) => [
                                        $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        FormSelect::make('teacher_id')
                            ->label('Professor')
                            ->options(fn () => Teacher::orderBy('name')->pluck('name','id'))
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $payload = [
                            'teacher_id' => (int) $data['teacher_id'],
                            'class_id'   => (int) $data['class_id'],
                            'subject_id' => (int) $record->id,
                        ];

                        $exists = TeacherAssignment::where($payload)->exists();
                        if ($exists) {
                            Notification::make()->title('Este vínculo já existe')->warning()->send();
                            return;
                        }

                        TeacherAssignment::create($payload);
                        Notification::make()->title('Vínculo criado com sucesso')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // status em massa
                    BulkAction::make('alterarStatus')
                        ->label('Alterar status')
                        ->icon('heroicon-o-adjustments-vertical')
                        ->modalHeading('Alterar status das disciplinas selecionadas')
                        ->form([
                            FormSelect::make('status')
                                ->label('Novo status')
                                ->options(['active'=>'Ativa','inactive'=>'Inativa'])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $updated = 0;
                            foreach ($records as $subject) {
                                $updated += (int) $subject->update(['status' => $data['status']]);
                            }
                            Notification::make()
                                ->title('Status atualizado')
                                ->body("{$updated} disciplina(s) atualizada(s).")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
                BulkAction::make('ofertarEmTurma')
                    ->label('Ofertar em turma (sem professor)')
                    ->icon('heroicon-o-academic-cap')
                    ->modalHeading('Adicionar disciplinas selecionadas à turma')
                    ->form([
                        FormSelect::make('class_id')
                            ->label('Turma')
                            ->options(fn () => SchoolClass::with('gradeLevel','schoolYear')->get()
                                ->mapWithKeys(fn($c) => [
                                    $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                ]))
                            ->searchable()->preload()->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $class = SchoolClass::findOrFail((int) $data['class_id']);
                        $ids   = $records->pluck('id')->all(); // subjects selecionadas
                        $class->subjects()->syncWithoutDetaching($ids); // evita duplicar
                        \Filament\Notifications\Notification::make()
                            ->title(count($ids) . ' disciplina(s) adicionada(s) à turma')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]);
    }
}
