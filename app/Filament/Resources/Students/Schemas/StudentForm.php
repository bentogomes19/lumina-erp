<?php

namespace App\Filament\Resources\Students\Schemas;

use App\Enums\Gender;
use App\Enums\StudentStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Enum as EnumRule;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Identificação')
                    ->schema([
                        TextInput::make('registration_number')
                            ->label('Matrícula')
                            ->placeholder('Gerado automaticamente')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpan(3),

                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(120)
                            ->columnSpan(5),

                        DatePicker::make('birth_date')
                            ->label('Nascimento')
                            ->columnSpan(2),

                        Select::make('gender')
                            ->label('Gênero')
                            ->options(Gender::options())
                            ->rule(new EnumRule(Gender::class))
                            ->nullable()
                            ->columnSpan(2),

                        TextInput::make('cpf')
                            ->label('CPF')
                            ->mask('999.999.999-99')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->columnSpan(3),

                        TextInput::make('rg')
                            ->label('RG')
                            ->maxLength(20)
                            ->nullable()
                            ->columnSpan(3),
                    ])
                    ->columns(10)
                    ->columnSpan(8),

                Section::make('Foto')
                    ->schema([
                        FileUpload::make('photo_url')
                            ->label('Foto')
                            ->image()
                            ->directory('students/photos')
                            ->imageEditor()
                            ->downloadable(),
                    ])
                    ->columns(1)
                    ->columnSpan(4),


                Section::make('Contato & Endereço')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(4),

                        TextInput::make('phone_number')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(20)
                            ->nullable()
                            ->columnSpan(4),

                        TextInput::make('postal_code')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->nullable()
                            ->columnSpan(4),

                        TextInput::make('address')
                            ->label('Endereço')
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(6),

                        TextInput::make('address_district')
                            ->label('Bairro')
                            ->maxLength(60)
                            ->nullable()
                            ->columnSpan(6),

                        TextInput::make('city')
                            ->label('Cidade')
                            ->maxLength(60)
                            ->nullable()
                            ->columnSpan(6),

                        TextInput::make('state')
                            ->label('UF')
                            ->maxLength(2)
                            ->minLength(2)
                            ->extraAttributes(['style' => 'text-transform:uppercase'])
                            ->nullable()
                            ->columnSpan(2),
                    ])
                    ->columns(12)
                    ->columnSpan(8),

                Section::make('Nascimento & Nacionalidade')
                    ->schema([
                        TextInput::make('birth_city')
                            ->label('Município de Nascimento')
                            ->nullable(),

                        TextInput::make('birth_state')
                            ->label('UF Nasc.')
                            ->maxLength(2)
                            ->minLength(2)
                            ->extraAttributes(['style' => 'text-transform:uppercase'])
                            ->nullable(),

                        TextInput::make('nationality')
                            ->label('Nacionalidade')
                            ->nullable(),
                    ])
                    ->columns(3)
                    ->columnSpan(4),

                Section::make('Responsáveis')
                    ->schema([
                        TextInput::make('mother_name')
                            ->label('Mãe')
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(6),

                        TextInput::make('father_name')
                            ->label('Pai')
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(6),

                        TextInput::make('guardian_main')
                            ->label('Responsável')
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(6),

                        TextInput::make('guardian_phone')
                            ->label('Telefone Resp.')
                            ->tel()
                            ->maxLength(20)
                            ->nullable()
                            ->columnSpan(3),

                        TextInput::make('guardian_email')
                            ->label('E-mail Resp.')
                            ->email()
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(3),
                    ])
                    ->columns(12)
                    ->columnSpan(8),

                Section::make('Vínculo')
                    ->schema([
                        DatePicker::make('enrollment_date')
                            ->label('Data de Matrícula')
                            ->native(false)
                            ->columnSpan(6),

                        DatePicker::make('exit_date')
                            ->label('Data de Saída')
                            ->native(false)
                            ->columnSpan(6),

                        Select::make('status')
                            ->label('Status')
                            ->options(StudentStatus::options())
                            ->default(StudentStatus::ACTIVE->value)
                            ->required()
                            ->columnSpan(12),
                    ])
                    ->columns(12)
                    ->columnSpan(4),

                Section::make('Saúde & Transporte')
                    ->schema([
                        Select::make('transport_mode')
                            ->label('Transporte')
                            ->options([
                                'none' => 'Nenhum',
                                'car'  => 'Carro',
                                'bus'  => 'Ônibus',
                                'van'  => 'Van',
                                'walk' => 'A pé',
                                'bike' => 'Bicicleta',
                            ])
                            ->default('none')
                            ->columnSpan(3),

                        Toggle::make('has_special_needs')
                            ->label('Necessidade Especial')
                            ->default(false)
                            ->inline(false)
                            ->required()
                            ->columnSpan(3),

                        TextInput::make('allergies')
                            ->label('Alergias')
                            ->maxLength(120)
                            ->nullable()
                            ->columnSpan(6),

                        Textarea::make('medical_notes')
                            ->label('Observações Médicas')
                            ->rows(3)
                            ->nullable()
                            ->columnSpan(12),
                    ])
                    ->columns(12)
                    ->columnSpan(12),

            ]);
    }
}
