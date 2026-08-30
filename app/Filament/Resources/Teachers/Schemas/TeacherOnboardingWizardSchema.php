<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Enums\AcademicTitle;
use App\Enums\TeacherAccessAction;
use App\Enums\TeacherRegime;
use App\Enums\TeacherStatus;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum as EnumRule;

class TeacherOnboardingWizardSchema {

    /**
     * Retorna as etapas do onboarding administrativo de professor.
     *
     * @return array
     */
    public static function getSteps(): array {
        return [
            Step::make('Dados pessoais')
                ->description('Identificação e contato do professor')
                ->icon('fas-circle-user')
                ->schema([
                    Hidden::make('onboarding_token')
                        ->default(fn (): string => (string) Str::uuid()),

                    Section::make('Identificação')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nome completo')
                                ->required()
                                ->maxLength(120)
                                ->columnSpanFull(),

                            TextInput::make('cpf')
                                ->label('CPF')
                                ->mask('999.999.999-99')
                                ->unique(Teacher::class, 'cpf')
                                ->live(onBlur: true)
                                ->nullable(),

                            DatePicker::make('birth_date')
                                ->label('Data de nascimento'),

                            Select::make('gender')
                                ->label('Gênero')
                                ->options(['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'])
                                ->nullable(),

                            TextInput::make('email')
                                ->label('E-mail')
                                ->email()
                                ->maxLength(120)
                                ->live(onBlur: true)
                                ->nullable(),

                            TextInput::make('phone')
                                ->label('Telefone')
                                ->tel()
                                ->maxLength(20),

                            TextInput::make('mobile')
                                ->label('Celular')
                                ->tel()
                                ->maxLength(20),

                            Placeholder::make('identity_matches')
                                ->label('Verificação de duplicidade')
                                ->content(function (Get $get): HtmlString {
                                    if (!collect([$get('cpf'), $get('email'), $get('employee_number')])
                                        ->contains(fn ($value): bool => filled($value))) {
                                        return new HtmlString('<span class="text-gray-500">Informe CPF, e-mail ou matrícula funcional para procurar cadastros existentes.</span>');
                                    }

                                    $matches = app(TeacherOnboardingService::class)->findTeachersByIdentity([
                                        'cpf'             => $get('cpf'),
                                        'email'           => $get('email'),
                                        'employee_number' => $get('employee_number'),
                                    ]);

                                    if ($matches->isEmpty()) {
                                        return new HtmlString('<span class="text-success-600">Nenhum professor existente localizado.</span>');
                                    }

                                    $teachers = $matches
                                        ->map(fn (Teacher $teacher): string => e("{$teacher->name} ({$teacher->employee_number})"))
                                        ->implode(', ');

                                    return new HtmlString("<span class=\"text-danger-600\">Cadastro existente localizado: {$teachers}.</span>");
                                })
                                ->columnSpanFull(),
                        ])
                        ->columns(3),

                    Section::make('Endereço')
                        ->schema([
                            TextInput::make('address_zip')->label('CEP')->maxLength(10),
                            TextInput::make('address_street')->label('Endereço')->maxLength(120)->columnSpan(2),
                            TextInput::make('address_number')->label('Número')->maxLength(10),
                            TextInput::make('address_district')->label('Bairro')->maxLength(60),
                            TextInput::make('address_city')->label('Cidade')->maxLength(60),
                            TextInput::make('address_state')->label('UF')->minLength(2)->maxLength(2),
                        ])
                        ->columns(4),
                ]),

            Step::make('Dados profissionais')
                ->description('Matrícula funcional, formação e vínculo')
                ->icon('fas-briefcase')
                ->schema([
                    Section::make('Vínculo funcional')
                        ->schema([
                            TextInput::make('employee_number')
                                ->label('Matrícula funcional')
                                ->required()
                                ->maxLength(20)
                                ->unique(Teacher::class, 'employee_number')
                                ->live(onBlur: true),

                            Select::make('status')
                                ->label('Status')
                                ->options(TeacherStatus::options())
                                ->default(TeacherStatus::ACTIVE->value)
                                ->rule(new EnumRule(TeacherStatus::class))
                                ->live()
                                ->required(),

                            Select::make('regime')
                                ->label('Regime')
                                ->options(TeacherRegime::options())
                                ->rule(new EnumRule(TeacherRegime::class)),

                            DatePicker::make('hire_date')->label('Data de contratação')->native(false),
                            DatePicker::make('admission_date')->label('Data de admissão')->native(false),
                            DatePicker::make('termination_date')
                                ->label('Data de desligamento')
                                ->native(false)
                                ->required(fn (Get $get): bool => $get('status') === TeacherStatus::TERMINATED->value),

                            TextInput::make('weekly_workload')
                                ->label('Carga semanal (h)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(60),

                            TextInput::make('max_classes')
                                ->label('Máximo de turmas')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(30),
                        ])
                        ->columns(4),

                    Section::make('Formação')
                        ->schema([
                            Select::make('academic_title')
                                ->label('Titulação')
                                ->options(AcademicTitle::options())
                                ->rule(new EnumRule(AcademicTitle::class)),
                            TextInput::make('qualification')->label('Área de formação')->maxLength(120),
                            TextInput::make('lattes_url')->label('Currículo Lattes')->url(),
                            Textarea::make('bio')->label('Bio / observações')->rows(4)->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),

            Step::make('Acesso')
                ->description('Criação explícita da conta do portal')
                ->icon('fas-key')
                ->schema([
                    Placeholder::make('access_rule')
                        ->label('Regra de acesso')
                        ->content(fn (Get $get): string => $get('status') === TeacherStatus::ACTIVE->value
                            ? 'Professor ativo pode receber acesso. O convite continua sendo uma decisão separada.'
                            : 'Professor inativo, afastado ou desligado não poderá entrar no portal.'),

                    Radio::make('access_action')
                        ->label('Ação de acesso')
                        ->options(TeacherAccessAction::options())
                        ->default(TeacherAccessAction::NONE->value)
                        ->helperText('Nenhum usuário será criado sem uma escolha explícita.')
                        ->live()
                        ->required(),

                    TextInput::make('access_email')
                        ->label('E-mail de acesso')
                        ->email()
                        ->maxLength(255)
                        ->placeholder(fn (Get $get): ?string => $get('email'))
                        ->helperText('Se ficar vazio, será usado o e-mail pessoal informado na primeira etapa.')
                        ->required(fn (Get $get): bool => $get('access_action') !== TeacherAccessAction::NONE->value
                            && !$get('email'))
                        ->visible(fn (Get $get): bool => $get('access_action') !== TeacherAccessAction::NONE->value),
                ]),

            Step::make('Alocações')
                ->description('Turmas e disciplinas iniciais, se já definidas')
                ->icon('fas-chalkboard-user')
                ->schema([
                    Placeholder::make('assignment_info')
                        ->label('Alocações opcionais')
                        ->content('É possível concluir sem alocação. O professor ficará claramente identificado como “Sem alocação”.'),

                    Repeater::make('assignments')
                        ->label('Turmas e disciplinas')
                        ->schema([
                            Select::make('class_id')
                                ->label('Turma')
                                ->options(fn () => SchoolClass::query()
                                    ->with(['gradeLevel', 'schoolYear'])
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (SchoolClass $class): array => [
                                        $class->id => "{$class->name} — {$class->gradeLevel?->name} ({$class->schoolYear?->year})",
                                    ]))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required(),

                            Select::make('subject_id')
                                ->label('Disciplina')
                                ->options(fn () => Subject::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Adicionar alocação')
                        ->reorderable(false),
                ]),

            Step::make('Revisão')
                ->description('Confira os registros antes de concluir')
                ->icon('fas-clipboard-check')
                ->schema([
                    Placeholder::make('review_teacher')
                        ->label('Professor')
                        ->content(fn (Get $get): string => ($get('name') ?: '—') . ' · ' . ($get('employee_number') ?: 'Sem matrícula')),

                    Placeholder::make('review_status')
                        ->label('Situação funcional')
                        ->content(fn (Get $get): string => TeacherStatus::tryFrom((string) $get('status'))?->label() ?? '—'),

                    Placeholder::make('review_records')
                        ->label('Registros e ações que serão confirmados')
                        ->content(function (Get $get): string {
                            $action = TeacherAccessAction::tryFrom(
                                (string) ($get('access_action') ?? TeacherAccessAction::NONE->value)
                            );
                            $items = [
                                'Criar 1 cadastro de professor',
                                'Criar ' . count($get('assignments') ?? []) . ' alocação(ões)',
                                $action?->createsUser() ? 'Criar 1 usuário docente' : 'Não criar usuário',
                                $action?->sendsInvitation() ? 'Enviar convite de acesso' : 'Não enviar convite',
                            ];

                            return implode(' · ', $items);
                        })
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }
}
