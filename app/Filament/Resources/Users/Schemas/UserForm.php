<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Gender;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Spatie\Permission\Models\Role;

class UserForm
{
    /** Labels dos perfis para exibição */
    public const ROLE_LABELS = [
        'ti'         => 'TI',
        'secretaria' => 'Secretaria',
        'financeiro' => 'Financeiro',
        'admin'      => 'Administrador',
        'teacher'    => 'Professor',
        'student'    => 'Aluno',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ── Identificação ────────────────────────────────────────────────
            Section::make('Identificação')
                ->icon('heroicon-o-identification')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome completo')
                        ->placeholder('Ex: MARIA OLIVEIRA')
                        ->prefixIcon('heroicon-o-user')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('E-mail institucional')
                        ->autocomplete('off')
                        ->email()
                        ->unique(table: 'users', column: 'email', ignoreRecord: true)
                        ->required(),

                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->revealable()
                        ->placeholder('Mínimo 8 caracteres')
                        ->minLength(8)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $context) => $context === 'create')
                        ->helperText('Deixe em branco para manter a senha atual.')
                        ->autocomplete('new-password'),

                    Select::make('role')
                        ->label('Perfil / Papel')
                        ->options(self::ROLE_LABELS)
                        ->required()
                        ->native(false)
                        ->afterStateHydrated(function ($component, $record) {
                            $component->state($record?->roles?->first()?->name);
                        })
                        ->dehydrateStateUsing(function ($state, $record) {
                            if (! $state || ! $record) {
                                return $state;
                            }
                            $record->syncRoles([$state]);
                            $role = Role::where('name', $state)->with('permissions')->first();
                            if ($role) {
                                $record->syncPermissions($role->permissions);
                            }
                            return $state;
                        })
                        ->helperText('Define as permissões de acesso ao sistema.'),

                    Toggle::make('active')
                        ->label('Ativo')
                        ->onColor('success')
                        ->offColor('danger')
                        ->inline(false)
                        ->default(true),

                    Toggle::make('force_password_change')
                        ->label('Forçar troca de senha')
                        ->helperText('O usuário será obrigado a redefinir a senha no próximo acesso.')
                        ->onColor('warning')
                        ->default(true)
                        ->inline(false),
                ])->columns(2),

            // ── Segurança (somente leitura em edição) ────────────────────────
            Section::make('Segurança e Acessos')
                ->icon('heroicon-o-shield-check')
                ->schema([
                    Placeholder::make('last_login_at')
                        ->label('Último acesso')
                        ->content(fn ($record) => $record?->last_login_at
                            ? $record->last_login_at->format('d/m/Y H:i:s')
                            : '—'),

                    Placeholder::make('login_attempts')
                        ->label('Tentativas de login falhas')
                        ->content(fn ($record) => $record?->login_attempts ?? 0),

                    Placeholder::make('locked_at')
                        ->label('Bloqueado em')
                        ->content(fn ($record) => $record?->locked_at
                            ? '🔒 ' . $record->locked_at->format('d/m/Y H:i:s')
                            : '—'),

                    Placeholder::make('created_at')
                        ->label('Criado em')
                        ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i:s') ?? '—'),
                ])
                ->columns(2)
                ->visibleOn('edit')
                ->collapsible(),

            // ── Informações Pessoais ──────────────────────────────────────────
            Section::make('Informações Pessoais')
                ->icon('heroicon-o-user-circle')
                ->schema([
                    TextInput::make('cpf')
                        ->label('CPF')
                        ->mask('999.999.999-99')
                        ->unique(table: 'users', column: 'cpf', ignoreRecord: true)
                        ->prefixIcon('heroicon-o-identification'),

                    TextInput::make('rg')
                        ->label('RG')
                        ->maxLength(20),

                    DatePicker::make('birth_date')
                        ->label('Data de Nascimento')
                        ->maxDate(now()->subYears(5)),

                    Select::make('gender')
                        ->label('Gênero')
                        ->options(Gender::options())
                        ->native(false)
                        ->rule(new EnumRule(Gender::class))
                        ->nullable(),

                    FileUpload::make('avatar')
                        ->label('Foto de Perfil')
                        ->image()
                        ->imageEditor()
                        ->circleCropper()
                        ->directory('avatars')
                        ->visibility('public')
                        ->columnSpan(2),
                ])->columns(2),

            // ── Endereço e Contato ────────────────────────────────────────────
            Section::make('Endereço e Contato')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    TextInput::make('postal_code')
                        ->label('CEP')
                        ->mask('99999-999')
                        ->placeholder('00000-000')
                        ->live(onBlur: true)
                        ->suffixIcon('heroicon-o-magnifying-glass')
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                return;
                            }
                            $cep = preg_replace('/[^0-9]/', '', $state);
                            if (strlen($cep) !== 8) {
                                return;
                            }
                            try {
                                $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");
                            } catch (\Exception $e) {
                                Notification::make()->title('Erro ao consultar CEP')->danger()->send();
                                return;
                            }
                            if ($response->failed() || isset($response->json()['erro'])) {
                                Notification::make()->title('CEP não encontrado')->warning()->send();
                                return;
                            }
                            $data = $response->json();
                            $set('address', $data['logradouro'] ?? '');
                            $set('district', $data['bairro'] ?? '');
                            $set('city', $data['localidade'] ?? '');
                            $set('state', $data['uf'] ?? '');
                            Notification::make()->title('Endereço preenchido!')->success()->send();
                        }),

                    TextInput::make('address')->label('Endereço'),
                    TextInput::make('district')->label('Bairro'),
                    TextInput::make('city')->label('Cidade'),
                    TextInput::make('state')->label('Estado')->maxLength(2),
                    TextInput::make('phone')->label('Telefone Fixo')->mask('(99) 9999-9999'),
                    TextInput::make('cellphone')->label('Celular')->mask('(99) 99999-9999'),
                ])->columns(3),
        ]);
    }
}
