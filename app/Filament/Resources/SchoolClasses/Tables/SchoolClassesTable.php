<?php

namespace App\Filament\Resources\SchoolClasses\Tables;

use App\Enums\ClassShift;
use App\Enums\ClassStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Illuminate\Support\Facades\DB;

class SchoolClassesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->toggleable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('name')
                    ->label('Turma')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('gradeLevel.name')->label('Série')->searchable()->sortable(),

                TextColumn::make('schoolYear.year')->label('Ano Letivo')->sortable(),

                TextColumn::make('shift')
                    ->label('Turno')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassShift::MORNING => 'success',
                        ClassShift::AFTERNOON => 'warning',
                        ClassShift::EVENING => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—')
                    ->color(fn ($state) => match ($state) {
                        ClassStatus::OPEN => 'success',
                        ClassStatus::CLOSED => 'danger',
                        ClassStatus::ARCHIVED => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('homeroomTeacher.name')->label('Professor Resp.')->toggleable(),

                TextColumn::make('capacity')->label('Cap.')->numeric()->alignRight()->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('matricularAluno')
                    ->label('Vincular Aluno')
                    ->icon('heroicon-o-user-plus')
                    ->modalHeading('Vincular Student')
                    ->form([
                        Select::make('student_id')
                            ->label('Aluno')
                            ->options(fn () => Student::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('enrollment_date')
                            ->label('Data de matrícula')
                            ->default(now())
                            ->required(),

                        TextInput::make('roll_number')
                            ->label('Nº chamada')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),

                        Select::make('status')
                            ->label('Status')
                            ->options(
                                collect(EnrollmentStatus::cases())
                                    ->mapWithKeys(fn ($c) => [$c->value => method_exists($c, 'label') ? $c->label() : ucfirst($c->value)])
                                    ->all()
                            )
                            ->required()
                            ->rule(new EnumRule(EnrollmentStatus::class))
                            ->default(EnrollmentStatus::ACTIVE->value),
                    ])
                    ->action(function (\App\Models\SchoolClass $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            // evita duplicidade
                            $already = Enrollment::where([
                                'class_id'   => $record->id,
                                'student_id' => $data['student_id'],
                            ])->exists();

                            if ($already) {
                                Notification::make()->title('Aluno já está matriculado nesta turma.')->warning()->send();
                                return;
                            }

                            Enrollment::create([
                                'class_id'        => $record->id,
                                'student_id'      => $data['student_id'],
                                'enrollment_date' => $data['enrollment_date'],
                                'roll_number'     => $data['roll_number'] ?? null,
                                'status'          => $data['status'],
                            ]);

                            Notification::make()->title('Matrícula realizada com sucesso.')->success()->send();
                        });
                    }),
                Action::make('verDisciplinas')
                    ->label('Ver disciplinas')
                    ->icon('heroicon-o-book-open')
                    ->modalHeading(fn ($record) => "Disciplinas — {$record->name}")
                    ->modalContent(function (\App\Models\SchoolClass $record) {
                        $badges = $record->subjects()
                            ->orderBy('name')
                            ->get()
                            ->map(fn($s) => "<span class='fi-badge fi-color-primary' style='margin:2px;padding:4px 8px;border-radius:8px;display:inline-block'>{$s->code} — {$s->name}</span>")
                            ->implode(' ');
                        return new HtmlString($badges ?: '<em>Sem disciplinas cadastradas.</em>');
                    })
                    ->modalSubmitAction(false)
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
