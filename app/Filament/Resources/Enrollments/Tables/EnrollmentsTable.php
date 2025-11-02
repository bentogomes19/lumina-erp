<?php

namespace App\Filament\Resources\Enrollments\Tables;

use App\Enums\EnrollmentStatus;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.registration_number')->label('Matrícula')->searchable(),
                TextColumn::make('student.name')->label('Aluno')->searchable()->sortable(),
                TextColumn::make('class.name')->label('Turma')->sortable(),
                TextColumn::make('class.gradeLevel.name')->label('Série')->toggleable(),
                TextColumn::make('class.schoolYear.year')->label('Ano')->sortable(),
                TextColumn::make('roll_number')->label('Nº')->alignCenter()->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors(EnrollmentStatus::colors())
                    ->formatStateUsing(fn ($s) => $s),
                TextColumn::make('enrollment_date')->label('Data')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_year_id')
                    ->label('Ano Letivo')
                    ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year','id'))
                    ->query(function ($query, $value) {
                        $query->whereHas('class', fn($q) => $q->where('school_year_id', $value));
                    }),
                SelectFilter::make('class_id')
                    ->label('Turma')
                    ->options(fn () => SchoolClass::with('gradeLevel','schoolYear')->get()
                        ->mapWithKeys(fn ($c) => [$c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})"])
                    ),
                SelectFilter::make('status')->label('Status')->options(EnrollmentStatus::options()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkStatus')
                        ->label('Alterar status')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->form([
                            Select::make('status')->options(EnrollmentStatus::options())->required(),
                        ])
                        ->action(fn ($records, $data) => $records->each->update(['status' => $data['status']])),
                    // Transferência de turma
                    BulkAction::make('transfer')
                        ->label('Transferir turma')
                        ->icon('heroicon-o-arrow-right')
                        ->form([
                            Select::make('class_id')
                                ->label('Nova Turma')
                                ->options(fn () => SchoolClass::with('gradeLevel','schoolYear')->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})"])
                                )->required(),
                        ])
                        ->action(function ($records, $data) {
                            foreach ($records as $enr) {
                                // Evita duplicidade na nova turma
                                $exists = \App\Models\Enrollment::where('student_id', $enr->student_id)
                                    ->where('class_id', $data['class_id'])->exists();
                                if (! $exists) {
                                    $enr->update([
                                        'class_id'     => $data['class_id'],
                                        'roll_number'  => \App\Models\Enrollment::nextRollNumberFor((int)$data['class_id']),
                                    ]);
                                }
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
