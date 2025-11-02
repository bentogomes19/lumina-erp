<?php

namespace App\Filament\Resources\Teachers\Tables;

use App\Enums\TeacherStatus;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeachersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_number')->label('Matrícula')->searchable()->copyable()->toggleable(),
                TextColumn::make('name')->label('Nome')->searchable()->toggleable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Telefone')->toggleable()->toggleable(),
                TextColumn::make('weekly_workload')->label('CH (h)')->numeric()->alignRight()->toggleable(),
                TextColumn::make('status')->toggleable(true)->searchable()->sortable()
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state
                        ? (is_string($state)
                            ? \App\Enums\TeacherStatus::from($state)->label()
                            : $state->label())
                        : '—'
                    )
                    ->color(fn($state) => match (is_string($state) ? $state : $state?->value) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'sabbatical' => 'warning',
                        'terminated' => 'danger',
                        default => 'secondary',
                    }),
                TextColumn::make('created_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(TeacherStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('vincular')
                    ->label('Vincular a turma/discip.')
                    ->icon('heroicon-o-link')
                    ->modalHeading(fn($record) => "Vincular {$record->name}")
                    ->form([
                        Select::make('class_id')
                            ->label('Turma')
                            ->options(fn() => SchoolClass::query()
                                ->with('gradeLevel', 'schoolYear')
                                ->get()
                                ->mapWithKeys(fn($c) => [
                                    $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                ])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('subject_id')
                            ->label('Disciplina')
                            ->options(function (Get $get) {
                                $classId = $get('class_id');
                                if ($classId) {
                                    $class = SchoolClass::with('gradeLevel')->find($classId);
                                    if ($class?->gradeLevel && method_exists($class->gradeLevel, 'subjects')) {
                                        $ids = $class->gradeLevel->subjects()->pluck('subjects.id');
                                        return Subject::whereIn('id', $ids)->orderBy('name')->pluck('name', 'id');
                                    }
                                }
                                // fallback
                                return Subject::orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Action::make('ativar')
                            ->label('Ativar')
                            ->visible(fn($record) => $record->status !== TeacherStatus::ACTIVE->value)
                            ->action(fn($record) => $record->update(['status' => TeacherStatus::ACTIVE->value])),

                        Action::make('inativar')
                            ->label('Inativar')
                            ->color('warning')
                            ->visible(fn($record) => $record->status !== TeacherStatus::INACTIVE->value)
                            ->action(fn($record) => $record->update(['status' => TeacherStatus::INACTIVE->value])),
                    ])
                    ->action(function (\App\Models\Teacher $record, array $data) {
                        $teacherId = $record->id;
                        $classId = (int)$data['class_id'];
                        $subjectId = (int)$data['subject_id'];

                        // evita duplicata manualmente (além do índice único no banco)
                        $exists = TeacherAssignment::where([
                            'teacher_id' => $teacherId,
                            'class_id' => $classId,
                            'subject_id' => $subjectId,
                        ])->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Este vínculo já existe')
                                ->warning()
                                ->send();
                            return;
                        }

                        TeacherAssignment::create([
                            'teacher_id' => $teacherId,
                            'class_id' => $classId,
                            'subject_id' => $subjectId,
                        ]);

                        Notification::make()
                            ->title('Vínculo criado com sucesso')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),

                    BulkAction::make('alterarStatus')
                        ->label('Alterar status')
                        ->icon('heroicon-o-adjustments-vertical')
                        ->modalHeading('Alterar status dos professores selecionados')
                        ->form([
                            Select::make('status')
                                ->label('Novo status')
                                ->options(TeacherStatus::options()) // ['active'=>'Ativo', ...]
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $novo = $data['status'];
                            $total = 0;

                            DB::transaction(function () use ($records, $novo, &$total) {
                                $total = $records->each->update(['status' => $novo])->count();
                            });

                            Notification::make()
                                ->title('Status atualizado')
                                ->body("{$total} professor(es) atualizado(s) para " . (TeacherStatus::from($novo)->label()))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
