<?php

namespace App\Filament\Resources\Grades\Schemas;

use App\Enums\AssessmentType;
use App\Enums\Term;
use App\Models\Enrollment;
use App\Models\Subject;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Enum as EnumRule;

class GradeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contexto')->schema([
                    Select::make('class_id')
                        ->label('Turma')
                        ->relationship('schoolClass', 'name') // exige schoolClass() no model
                        ->searchable()->preload()->required()->live(),

                    Select::make('subject_id')
                        ->label('Disciplina')
                        ->options(function ($get) {
                            $classId = $get('class_id');
                            if (!$classId) return [];
                            // puxe das disciplinas da turma (por grade_level_subject ou class_subject_teacher)
                            return Subject::query()
                                ->whereIn('id', function ($q) use ($classId) {
                                    $q->select('subject_id')->from('class_subject_teacher')->where('class_id', $classId);
                                })
                                ->orderBy('name')->pluck('name', 'id');
                        })
                        ->searchable()->preload()->required()->live(),

                    Select::make('enrollment_id')
                        ->label('Aluno (Matrícula)')
                        ->options(function ($get) {
                            $classId = $get('class_id');
                            if (!$classId) return [];
                            return Enrollment::with('student')
                                ->where('class_id', $classId)
                                ->get()
                                ->mapWithKeys(fn($e) => [$e->id => "{$e->student?->name}"]);
                        })
                        ->searchable()->preload()->required(),
                ])->columns(3),

                Section::make('Avaliação')->schema([
                    Select::make('term')->label('Período/Bimestre')
                        ->options(Term::options())->required()->rule(new EnumRule(Term::class)),

                    Select::make('assessment_type')->label('Tipo')
                        ->options(AssessmentType::options())->required()->rule(new EnumRule(AssessmentType::class)),

                    TextInput::make('sequence')->label('Seq.')->numeric()->minValue(1)->default(1)->required(),

                    DatePicker::make('date_recorded')->label('Data')->default(now()),
                ])->columns(4),

                Section::make('Pontuação')->schema([
                    TextInput::make('score')->label('Nota')->numeric()->minValue(0)->required(),
                    TextInput::make('max_score')->label('Máx.')->numeric()->minValue(1)->default(10)->required(),
                    TextInput::make('weight')->label('Peso')->numeric()->minValue(0.1)->default(1)->required(),
                    TextInput::make('percent')->label('% (auto)')->disabled()->dehydrated(false)
                        ->formatStateUsing(fn($record) => $record?->percent),
                ])->columns(4),

                Section::make('Observações')->schema([
                    Textarea::make('comment')->rows(3)->columnSpanFull(),
                ]),
            ]);
    }
}
