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
                Section::make('Identificação')->schema([
                    TextInput::make('registration_number')->label('Matrícula')->disabled()->dehydrated(false),
                    TextInput::make('name')->label('Nome')->required()->maxLength(120),
                    DatePicker::make('birth_date')->label('Nascimento'),
                    Select::make('gender')->label('Gênero')
                        ->options(Gender::options())->rule(new EnumRule(Gender::class))->nullable(),
                    TextInput::make('cpf')->label('CPF')->mask('999.999.999-99')->unique(ignoreRecord: true)->nullable(),
                    TextInput::make('rg')->label('RG')->maxLength(20)->nullable(),
                    FileUpload::make('photo_url')->label('Foto')->image()->directory('students/photos')->imageEditor()->downloadable(),
                ])->columns(3)->columnSpan(8),

                Section::make('Contato & Endereço')->schema([
                    TextInput::make('email')->label('E-mail')->email()->maxLength(120)->nullable(),
                    TextInput::make('phone_number')->label('Telefone')->tel()->maxLength(20)->nullable(),
                    TextInput::make('address')->label('Endereço')->maxLength(120)->nullable(),
                    TextInput::make('address_district')->label('Bairro')->maxLength(60)->nullable(),
                    TextInput::make('city')->label('Cidade')->maxLength(60)->nullable(),
                    TextInput::make('state')->label('UF')->maxLength(2)->nullable(),
                    TextInput::make('postal_code')->label('CEP')->mask('99999-999')->nullable(),
                ])->columns(3)->columnSpan(8),

                Section::make('Nascimento & Nacionalidade')->schema([
                    TextInput::make('birth_city')->label('Município de Nascimento')->nullable(),
                    TextInput::make('birth_state')->label('UF Nasc.')->maxLength(2)->nullable(),
                    TextInput::make('nationality')->label('Nacionalidade')->nullable(),
                ])->columns(3)->columnSpan(4),

                Section::make('Responsáveis')->schema([
                    TextInput::make('mother_name')->label('Mãe')->maxLength(120)->nullable(),
                    TextInput::make('father_name')->label('Pai')->maxLength(120)->nullable(),
                    TextInput::make('guardian_main')->label('Responsável')->maxLength(120)->nullable(),
                    TextInput::make('guardian_phone')->label('Telefone Resp.')->tel()->maxLength(20)->nullable(),
                    TextInput::make('guardian_email')->label('E-mail Resp.')->email()->maxLength(120)->nullable(),
                ])->columns(2)->columnSpan(8),

                Section::make('Saúde & Transporte')->schema([
                    Select::make('transport_mode')->label('Transporte')
                        ->options(['none'=>'Nenhum','car'=>'Carro','bus'=>'Ônibus','van'=>'Van','walk'=>'A pé','bike'=>'Bicicleta'])->default('none'),
                    Toggle::make('has_special_needs')
                        ->label('Necessidade Especial')
                        ->default(false)
                        ->inline(false)
                        ->required(),
                    TextInput::make('allergies')->label('Alergias')->maxLength(120)->nullable(),
                    Textarea::make('medical_notes')->label('Observações Médicas')->rows(2)->columnSpanFull()->nullable(),
                ])->columns(2)->columnSpan(8),

                Section::make('Vínculo')->schema([
                    DatePicker::make('enrollment_date')->label('Data de Matrícula')->native(false),
                    DatePicker::make('exit_date')->label('Data de Saída')->native(false),
                    Select::make('status')->label('Status')
                        ->options(StudentStatus::options())
                        ->default(StudentStatus::ACTIVE->value)
                        ->required(),
                ])->columns(3)->columnSpan(4),
            ]);
    }
}
