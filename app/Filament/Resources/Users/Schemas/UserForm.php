<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rules\Enum as EnumRule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Identificação')
                ->icon('heroicon-o-identification') // Ícone da seção
                ->schema([

                    TextInput::make('name')
                        ->label('Nome completo')
                        ->placeholder('Ex: MARIA OLIVEIRA')
                        ->prefixIcon('heroicon-o-user')
                        ->required(),

                    TextInput::make('email')
                        ->label('E-mail institucional')
                        ->placeholder('E-mail')
                        ->autocomplete('off')
                        ->email()
                        ->required(),

                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->placeholder('Digite uma senha...')
                        ->dehydrated(fn($state) => filled($state)) // evita salvar se estiver vazio
                        ->required(fn(string $context) => $context === 'create') // obriga só na criação
                        ->helperText('Deixe em branco para manter a senha atual.')
                        ->hint('Mínimo 8 caracteres')
                        ->autocomplete('new-password')
                        ->hintIcon('heroicon-o-key'),

                    Select::make('role')
                        ->label('Papel / Perfil')
                        ->options([
                            'admin' => 'Administrador',
                            'teacher' => 'Professor',
                            'student' => 'Aluno',
                        ])
                        ->default(fn($record) => $record?->roles?->first()?->name)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state === 'student') {
                                $set('active', true);
                            }
                        })
                        ->afterStateHydrated(function ($component, $state, $record) {
                            $component->state($record?->roles?->first()?->name);
                        })
                        ->dehydrateStateUsing(function ($state, $record) {
                            if (!$state || !$record) {
                                return $state;
                            }

                            // Atualiza o papel
                            $record->syncRoles([$state]);

                            // Sincroniza permissões conforme o papel
                            $role = Role::where('name', $state)->with('permissions')->first();
                            if ($role) {
                                $record->syncPermissions($role->permissions);
                            }

                            return $state;
                        })
                        ->required()
                        ->native(false)
                        ->helperText('Selecione o tipo de usuário (Administrador, Professor ou Aluno).'),

                    Toggle::make('active')
                        ->label('Ativo')
                        ->onColor('success')
                        ->offColor('danger')
                        ->inline(false)
                        ->default(true)
                        ->columnSpan(1),
                ])->columns(2),

            Section::make('Informações Pessoais')
                ->schema([
                    TextInput::make('cpf')
                        ->label('CPF')
                        ->mask('999.999.999-99')
                        ->prefixIcon('heroicon-o-identification'),

                    TextInput::make('rg')->label('RG'),

                    DatePicker::make('birth_date')->label('Data de Nascimento'),

                    Select::make('gender')
                        ->label('Gênero')
                        ->options(Gender::options())     // valores: M, F, O
                        ->native(false)
                        ->required()                     // se quiser obrigatório
                        ->rule(new EnumRule(Gender::class))
                        ->nullable(),

                    FileUpload::make('avatar')
                        ->label('Foto de Perfil')
                        ->image()
                        ->imageEditor() // permite corte, zoom, etc.
                        ->circleCropper()
                        ->directory('avatars')
                        ->visibility('public')
                ])->columns(2),

            Section::make('Endereço e Contato')
                ->schema([
                    TextInput::make('postal_code')
                        ->label('CEP')
                        ->mask('99999-999')
                        ->placeholder('Digite o CEP...')
                        ->live(onBlur: true)
                        ->suffixIcon('heroicon-o-magnifying-glass') // ícone de busca no campo
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                return;
                            }

                            $cep = preg_replace('/[^0-9]/', '', $state);

                            if (strlen($cep) !== 8) {
                                Notification::make()
                                    ->title('CEP inválido')
                                    ->body('Digite um CEP válido com 8 dígitos.')
                                    ->danger()
                                    ->sendToDatabase(auth()->user());
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

                            if (
                                !isset($data['logradouro']) &&
                                !isset($data['bairro']) &&
                                !isset($data['localidade'])
                            ) {
                                Notification::make()
                                    ->title('CEP não encontrado')
                                    ->body('Verifique o CEP informado — endereço não retornado pela API.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            if (isset($data['erro']) && $data['erro'] === true) {
                                Notification::make()
                                    ->title('CEP não encontrado')
                                    ->body('Verifique o CEP e tente novamente.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $set('address', $data['logradouro'] ?? '');
                            $set('district', $data['bairro'] ?? '');
                            $set('city', $data['localidade'] ?? '');
                            $set('state', $data['uf'] ?? '');

                            Notification::make()
                                ->title('Endereço carregado com sucesso!')
                                ->success()
                                ->send();
                        }),
                    TextInput::make('address')->label('Endereço'),
                    TextInput::make('district')->label('Bairro'),
                    TextInput::make('city')->label('Cidade'),
                    TextInput::make('state')->label('Estado'),
                    TextInput::make('phone')->label('Telefone Fixo'),
                    TextInput::make('cellphone')->label('Celular')->mask('(99) 9999-9999'),
                ])->columns(3)
                ->columnSpanFull(),
        ]);
    }
}
