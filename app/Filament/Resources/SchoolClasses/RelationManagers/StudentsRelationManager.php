<?php

namespace App\Filament\Resources\SchoolClasses\RelationManagers;

use App\Enums\Gender;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\Enrollments\StudentEnrollmentService;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class StudentsRelationManager extends RelationManager {

    protected static string $relationship = 'students';
    protected static ?string $title       = 'Alunos matriculados';

    /**
     * Configura a tabela e suas ações.
     *
     * @param Table $table
     *
     * @return Table
     */
    public function table(Table $table): Table {
        return $table
            ->columns([
                TextColumn::make('registration_number')->label('Matrícula')->searchable(),
                TextColumn::make('name')->label('Aluno')->searchable()->sortable(),
                TextColumn::make('pivot.enrollment_date')->label('Ingresso')->date(),
                TextColumn::make('pivot.roll_number')->label('Nº chamada')->toggleable(),
                TextColumn::make('pivot.status')->label('Status matrícula')->badge(),
            ])

            ->headerActions([
            ])

            ->recordActions([
                ViewAction::make('verAluno')
                    ->label('Ver aluno')
                    ->icon('fas-eye')
                    ->modalHeading(fn (Student $record): string => "Aluno | {$record->name}")
                    ->modalWidth('4xl')
                    ->infolist([
                        InfoSection::make('Identificação')
                            ->icon('fas-id-card')
                            ->columns(4)
                            ->schema([
                                ImageEntry::make('photo_url')
                                    ->label('Foto')
                                    ->disk('public')
                                    ->circular()
                                    ->imageSize(80)
                                    ->columnSpan(1),
                                TextEntry::make('name')
                                    ->label('Nome completo')
                                    ->weight('bold')
                                    ->columnSpan(2),
                                TextEntry::make('registration_number')
                                    ->label('Matrícula')
                                    ->copyable(),
                                TextEntry::make('birth_date')
                                    ->label('Nascimento')
                                    ->date('d/m/Y')
                                    ->placeholder('Não informado'),
                                TextEntry::make('age')
                                    ->label('Idade')
                                    ->suffix(' anos')
                                    ->placeholder('—'),
                                TextEntry::make('gender')
                                    ->label('Gênero')
                                    ->formatStateUsing(fn ($state) => $state instanceof Gender ? Gender::options()[$state->value] : '—'),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state instanceof StudentStatus
                                        ? StudentStatus::options()[$state->value]
                                        : '—')
                                    ->color(fn ($state) => match ($state) {
                                        StudentStatus::ACTIVE    => 'success',
                                        StudentStatus::SUSPENDED => 'warning',
                                        StudentStatus::GRADUATED => 'info',
                                        default                  => 'gray',
                                    }),
                            ]),
                        InfoSection::make('Contato e endereço')
                            ->icon('fas-address-book')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('cpf')->label('CPF')->placeholder('Não informado'),
                                TextEntry::make('rg')->label('RG')->placeholder('Não informado'),
                                TextEntry::make('email')->label('E-mail')->placeholder('Não informado')->columnSpan(2),
                                TextEntry::make('phone_number')->label('Telefone')->placeholder('Não informado'),
                                TextEntry::make('address')->label('Endereço')->placeholder('Não informado')->columnSpan(2),
                                TextEntry::make('address_district')->label('Bairro')->placeholder('Não informado'),
                                TextEntry::make('city')->label('Cidade')->placeholder('Não informado'),
                                TextEntry::make('state')->label('UF')->placeholder('—'),
                                TextEntry::make('postal_code')->label('CEP')->placeholder('Não informado'),
                            ]),
                        InfoSection::make('Responsáveis')
                            ->icon('fas-users')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('mother_name')->label('Mãe')->placeholder('Não informado')->columnSpan(2),
                                TextEntry::make('father_name')->label('Pai')->placeholder('Não informado')->columnSpan(2),
                                TextEntry::make('guardian_main')->label('Responsável principal')->placeholder('Não informado')->columnSpan(2),
                                TextEntry::make('guardian_phone')->label('Telefone do responsável')->placeholder('Não informado'),
                                TextEntry::make('guardian_email')->label('E-mail do responsável')->placeholder('Não informado'),
                            ]),
                        InfoSection::make('Matrícula nesta turma')
                            ->icon('fas-graduation-cap')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('pivot.enrollment_date')->label('Data de ingresso')->date('d/m/Y')->placeholder('Não informado'),
                                TextEntry::make('pivot.roll_number')->label('Nº chamada')->placeholder('Não informado'),
                                TextEntry::make('pivot.status')->label('Status da matrícula')->badge()->placeholder('—'),
                            ]),
                        InfoSection::make('Saúde e transporte')
                            ->icon('fas-heart-pulse')
                            ->columns(3)
                            ->schema([
                                TextEntry::make('transport_mode')
                                    ->label('Transporte')
                                    ->formatStateUsing(fn ($state) => [
                                        'none' => 'Nenhum', 'car' => 'Carro', 'bus' => 'Ônibus',
                                        'van' => 'Van', 'walk' => 'A pé', 'bike' => 'Bicicleta',
                                    ][$state] ?? '—'),
                                TextEntry::make('has_special_needs')
                                    ->label('Necessidade especial')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state ? 'Sim' : 'Não')
                                    ->color(fn ($state) => $state ? 'warning' : 'gray'),
                                TextEntry::make('allergies')->label('Alergias')->placeholder('Nenhuma informada'),
                                TextEntry::make('medical_notes')
                                    ->label('Observações médicas')
                                    ->placeholder('Nenhuma observação cadastrada.')
                                    ->prose()
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->modalSubmitAction(false),

                EditAction::make()
                    ->label('Editar matrícula')
                    ->form([
                        DatePicker::make('enrollment_date')->label('Data de matrícula')->required(),
                        TextInput::make('roll_number')->label('Nº chamada')->numeric()->minValue(1),
                        Select::make('status')
                            ->label('Status')
                            ->options(EnrollmentStatus::options())
                            ->required(),
                    ])
                    ->using(function ($record, array $data) {
                        $enrollment = Enrollment::query()
                            ->where('class_id', $this->getOwnerRecord()->id)
                            ->where('student_id', $record->id)
                            ->firstOrFail();

                        app(StudentEnrollmentService::class)->updateStatus(
                            $enrollment,
                            $data['status'],
                            [
                                'enrollment_date' => $data['enrollment_date'],
                                'roll_number'     => $data['roll_number'] ?? null,
                            ],
                        );
                    }),

                DetachAction::make()
                    ->label('Remover da turma')
                    ->requiresConfirmation(),
            ]);
    }
}
