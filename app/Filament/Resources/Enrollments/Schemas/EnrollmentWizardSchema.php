<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Enums\EnrollmentStatus;
use App\Enums\Gender;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\IbgeLocalidadesService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\Facades\Http;

class EnrollmentWizardSchema
{
    public static function getSteps(): array
    {
        return [
            Step::make('Identificação do aluno')
                ->description('Aluno já cadastrado ou novo cadastro na secretaria')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Radio::make('student_source')
                        ->label('Tipo de cadastro')
                        ->options([
                            'existing' => 'Aluno já cadastrado no sistema',
                            'new' => 'Novo aluno — preencher cadastro',
                        ])
                        ->default('existing')
                        ->live()
                        ->required(),

                    Select::make('student_id')
                        ->label('Buscar aluno')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Student::orderBy('name')->get()->mapWithKeys(fn ($s) => [
                            $s->id => "{$s->name} — {$s->registration_number}" . ($s->cpf ? " ({$s->cpf})" : ''),
                        ]))
                        ->required(fn (Get $get) => $get('student_source') === 'existing')
                        ->visible(fn (Get $get) => $get('student_source') === 'existing')
                        ->helperText('Digite o nome, matrícula ou CPF para localizar o aluno.'),

                    Section::make('Dados do novo aluno')
                        ->visible(fn (Get $get) => $get('student_source') === 'new')
                        ->schema([
                            TextInput::make('student_name')
                                ->label('Nome completo')
                                ->required(fn (Get $get) => $get('student_source') === 'new')
                                ->maxLength(120)
                                ->columnSpanFull(),

                            TextInput::make('student_cpf')
                                ->label('CPF')
                                ->mask('999.999.999-99')
                                ->unique(Student::class, 'cpf')
                                ->nullable()
                                ->columnSpan(2),

                            TextInput::make('student_rg')
                                ->label('RG')
                                ->maxLength(20)
                                ->nullable()
                                ->columnSpan(2),

                            DatePicker::make('student_birth_date')
                                ->label('Data de nascimento')
                                ->required(fn (Get $get) => $get('student_source') === 'new')
                                ->columnSpan(2),

                            Select::make('student_gender')
                                ->label('Gênero')
                                ->options(Gender::options())
                                ->nullable()
                                ->columnSpan(2),

                            TextInput::make('student_email')
                                ->label('E-mail')
                                ->email()
                                ->maxLength(120)
                                ->required(fn (Get $get) => $get('student_source') === 'new')
                                ->columnSpan(3),

                            TextInput::make('student_phone_number')
                                ->label('Telefone / Celular')
                                ->tel()
                                ->maxLength(20)
                                ->required(fn (Get $get) => $get('student_source') === 'new')
                                ->columnSpan(3),
                        ])
                        ->columns(6),
                ])
                ->columns(1),

            Step::make('Dados da matrícula')
                ->description('Ano letivo, turma, data e status da matrícula')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    Section::make('Ano letivo e turma')
                        ->icon('heroicon-o-calendar-days')
                        ->description('Selecione o ano letivo e a turma em que o aluno será matriculado.')
                        ->schema([
                            Select::make('school_year_id')
                                ->label('Ano letivo')
                                ->options(fn () => SchoolYear::orderByDesc('year')->pluck('year', 'id'))
                                ->default(fn () => SchoolYear::where('is_active', true)->value('id'))
                                ->live()
                                ->required(),

                            Select::make('class_id')
                                ->label('Turma')
                                ->options(function (Get $get) {
                                    $query = SchoolClass::query()->with('gradeLevel', 'schoolYear')->orderBy('name');
                                    if ($get('school_year_id')) {
                                        $query->where('school_year_id', $get('school_year_id'));
                                    }
                                    return $query->get()->mapWithKeys(fn ($c) => [
                                        $c->id => "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})",
                                    ]);
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $set('roll_number', Enrollment::nextRollNumberFor((int) $state));
                                    }
                                }),
                        ])
                        ->columns(2),

                    Section::make('Data e número de chamada')
                        ->icon('heroicon-o-identification')
                        ->description('Data da matrícula e número do aluno na lista de chamada.')
                        ->schema([
                            DatePicker::make('enrollment_date')
                                ->label('Data da matrícula')
                                ->default(now())
                                ->required()
                                ->native(false),

                            TextInput::make('roll_number')
                                ->label('Nº de chamada')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('Sugerido automaticamente pela turma. Pode ajustar se necessário.'),

                            Select::make('status')
                                ->label('Status da matrícula')
                                ->options(EnrollmentStatus::options())
                                ->default(EnrollmentStatus::ACTIVE->value)
                                ->required(),
                        ])
                        ->columns(3),
                ])
                ->columns(1),

            Step::make('Endereço')
                ->description('Endereço do aluno (novo cadastro)')
                ->icon('heroicon-o-home')
                ->schema([
                    Section::make('Endereço e contato')
                        ->icon('heroicon-o-map-pin')
                        ->description('Informe o CEP para preencher automaticamente ou selecione estado e cidade.')
                        ->schema([
                            TextInput::make('student_postal_code')
                                ->label('CEP')
                                ->mask('99999-999')
                                ->placeholder('Digite o CEP...')
                                ->live(onBlur: true)
                                ->suffixIcon('heroicon-o-magnifying-glass')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        return;
                                    }
                                    $cep = preg_replace('/[^0-9]/', '', $state);
                                    if (strlen($cep) !== 8) {
                                        Notification::make()
                                            ->title('CEP inválido')
                                            ->body('Digite um CEP válido com 8 dígitos.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    try {
                                        $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Erro de conexão')
                                            ->body('Não foi possível acessar o serviço ViaCEP.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    if ($response->failed()) {
                                        Notification::make()
                                            ->title('Erro na consulta')
                                            ->body('Não foi possível buscar o endereço.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                    $data = $response->json();
                                    if (isset($data['erro']) && $data['erro'] === true) {
                                        Notification::make()
                                            ->title('CEP não encontrado')
                                            ->body('Verifique o CEP e tente novamente.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }
                                    $set('student_address', $data['logradouro'] ?? '');
                                    $set('student_address_district', $data['bairro'] ?? '');
                                    $set('student_city', $data['localidade'] ?? '');
                                    $set('student_state', $data['uf'] ?? '');
                                    Notification::make()
                                        ->title('Endereço carregado')
                                        ->body('Endereço preenchido com sucesso.')
                                        ->success()
                                        ->send();
                                })
                                ->columnSpan(2),

                            TextInput::make('student_address')
                                ->label('Endereço')
                                ->maxLength(255)
                                ->nullable()
                                ->columnSpanFull(),

                            TextInput::make('student_address_district')
                                ->label('Bairro')
                                ->maxLength(60)
                                ->nullable()
                                ->columnSpan(2),

                            Select::make('student_state')
                                ->label('Estado')
                                ->options(fn () => IbgeLocalidadesService::getEstadosOptions())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('student_city', null))
                                ->placeholder('Selecione ou digite o estado (ex: SP)')
                                ->helperText('Digite a sigla (SP, RJ) ou o nome do estado.')
                                ->nullable()
                                ->columnSpan(2),

                            Select::make('student_city')
                                ->label('Cidade')
                                ->options(function (Get $get) {
                                    $uf = $get('student_state');
                                    if (! $uf || strlen($uf) !== 2) {
                                        return [];
                                    }
                                    return IbgeLocalidadesService::getMunicipiosOptions($uf);
                                })
                                ->searchable()
                                ->preload()
                                ->placeholder('Primeiro selecione o estado')
                                ->helperText('Lista de municípios do estado selecionado (fonte: IBGE).')
                                ->disabled(fn (Get $get) => ! $get('student_state'))
                                ->nullable()
                                ->columnSpan(2),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->visible(fn (Get $get) => $get('student_source') === 'new'),

            Step::make('Responsáveis')
                ->description('Mãe, pai e responsável legal pelo aluno')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Section::make('Dados dos pais')
                        ->icon('heroicon-o-users')
                        ->description('Nome da mãe e do pai (ou responsáveis).')
                        ->schema([
                            TextInput::make('student_mother_name')
                                ->label('Nome da mãe')
                                ->maxLength(120)
                                ->placeholder('Nome completo da mãe')
                                ->nullable()
                                ->columnSpanFull(),

                            TextInput::make('student_father_name')
                                ->label('Nome do pai')
                                ->maxLength(120)
                                ->placeholder('Nome completo do pai')
                                ->nullable()
                                ->columnSpanFull(),
                        ])
                        ->columns(1),

                    Section::make('Responsável legal')
                        ->icon('heroicon-o-phone')
                        ->description('Em caso de ausência dos pais, informe quem pode ser contatado.')
                        ->schema([
                            TextInput::make('student_guardian_main')
                                ->label('Nome do responsável')
                                ->maxLength(120)
                                ->placeholder('Nome do responsável legal')
                                ->nullable()
                                ->columnSpanFull(),

                            TextInput::make('student_guardian_phone')
                                ->label('Telefone do responsável')
                                ->tel()
                                ->mask('(99) 99999-9999')
                                ->maxLength(20)
                                ->placeholder('(00) 00000-0000')
                                ->nullable()
                                ->columnSpan(2),

                            TextInput::make('student_guardian_email')
                                ->label('E-mail do responsável')
                                ->email()
                                ->maxLength(120)
                                ->placeholder('email@exemplo.com')
                                ->nullable()
                                ->columnSpan(2),
                        ])
                        ->columns(2),
                ])
                ->columns(1)
                ->visible(fn (Get $get) => $get('student_source') === 'new'),

            Step::make('Saúde e transporte')
                ->description('Informações para a secretaria e coordenação')
                ->icon('heroicon-o-heart')
                ->schema([
                    Select::make('student_transport_mode')
                        ->label('Meio de transporte')
                        ->options([
                            'none' => 'Nenhum',
                            'car' => 'Carro',
                            'bus' => 'Ônibus escolar',
                            'van' => 'Van',
                            'walk' => 'A pé',
                            'bike' => 'Bicicleta',
                        ])
                        ->default('none')
                        ->columnSpan(2),

                    Toggle::make('student_has_special_needs')
                        ->label('Necessidade educacional especial')
                        ->default(false)
                        ->columnSpan(2),

                    TextInput::make('student_allergies')
                        ->label('Alergias')
                        ->maxLength(255)
                        ->nullable()
                        ->columnSpanFull(),

                    Textarea::make('student_medical_notes')
                        ->label('Observações médicas')
                        ->rows(3)
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->visible(fn (Get $get) => $get('student_source') === 'new'),

            Step::make('Revisão')
                ->description('Confira os dados antes de concluir a matrícula')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Placeholder::make('review_student')
                        ->label('Aluno')
                        ->content(fn (Get $get) => $get('student_source') === 'existing' && $get('student_id')
                            ? (Student::find($get('student_id'))?->name ?? '—')
                            : $get('student_name') . ' (novo cadastro)'),

                    Placeholder::make('review_class')
                        ->label('Turma / Ano')
                        ->content(function (Get $get) {
                            $id = $get('class_id');
                            if (!$id) return '—';
                            $c = SchoolClass::with('gradeLevel', 'schoolYear')->find($id);
                            return $c ? "{$c->name} — {$c->gradeLevel?->name} ({$c->schoolYear?->year})" : '—';
                        }),

                    Placeholder::make('review_date')
                        ->label('Data da matrícula')
                        ->content(fn (Get $get) => $get('enrollment_date') ? \Carbon\Carbon::parse($get('enrollment_date'))->format('d/m/Y') : '—'),

                    Placeholder::make('review_status')
                        ->label('Status')
                        ->content(fn (Get $get) => $get('status') ?? 'Ativa'),
                ])
                ->columns(2),
        ];
    }
}
