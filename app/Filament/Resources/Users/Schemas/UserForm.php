<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Identificação')
                ->schema([
                    TextInput::make('uuid')
                        ->label('UUID')
                        ->default(fn() => (string) \Illuminate\Support\Str::uuid())
                        ->disabled(),
                    TextInput::make('name')
                        ->label('Nome completo')
                        ->required(),
                    TextInput::make('email')
                        ->label('E-mail institucional')
                        ->email()
                        ->required(),
                    TextInput::make('password')
                        ->label('Senha')
                        ->password()
                        ->dehydrated(fn($state) => filled($state))
                        ->required(fn(string $context) => $context === 'create'),
                    Select::make('role')
                        ->label('Papel / Perfil')
                        ->options(Role::pluck('name', 'name')->toArray())
                        ->required()
                        ->native(false)
                        ->helperText('Selecione o tipo de usuário (Administrador, Professor ou Aluno).'),
                ])->columns(2),

            Section::make('Informações Pessoais')
                ->schema([
                    TextInput::make('cpf')->mask('999.999.999-99')->label('CPF'),
                    TextInput::make('rg')->label('RG'),
                    DatePicker::make('birth_date')->label('Data de Nascimento'),
                    Select::make('gender')
                        ->label('Gênero')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Feminino' => 'Feminino',
                            'Outro' => 'Outro',
                        ]),
                    FileUpload::make('avatar')
                        ->label('Foto de Perfil')
                        ->image()
                        ->directory('avatars')
                        ->visibility('public'),
                ])->columns(2),

            Section::make('Endereço e Contato')
                ->schema([
                    TextInput::make('address')->label('Endereço'),
                    TextInput::make('district')->label('Bairro'),
                    TextInput::make('city')->label('Cidade'),
                    TextInput::make('state')->label('Estado'),
                    TextInput::make('postal_code')->label('CEP')->mask('99999-999'),
                    TextInput::make('phone')->label('Telefone Fixo'),
                    TextInput::make('cellphone')->label('Celular'),
                ])->columns(3),

            Section::make('Status e Configurações')
                ->schema([
                    Select::make('active')
                        ->label('Status')
                        ->options([1 => 'Ativo', 0 => 'Inativo'])
                        ->default(1),
                ]),
        ]);
    }
}
