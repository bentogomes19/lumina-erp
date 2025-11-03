<?php

namespace App\Filament\Resources\Teachers\Schemas;

use App\Enums\AcademicTitle;
use App\Enums\TeacherRegime;
use App\Enums\TeacherStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rules\Enum as EnumRule;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeacherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Identificação')->schema([
                    TextInput::make('uuid')->label('UUID')->disabled()->dehydrated(false),
                    TextInput::make('employee_number')->label('Matrícula')->maxLength(20)->unique(ignoreRecord: true),
                    TextInput::make('name')->label('Nome')->required()->maxLength(120),
                    TextInput::make('cpf')->label('CPF')->mask('999.999.999-99')->unique(ignoreRecord: true)->nullable(),
                    DatePicker::make('birth_date')->label('Data de Nascimento'),
                    Select::make('gender')->label('Gênero')->options(['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'])->nullable(),
                ])
                    ->columns(3)
                    ->columnSpan(6),

                Section::make('Formação')->schema([
                    Select::make('academic_title')
                        ->label('Titulação')
                        ->options(AcademicTitle::options())
                        ->rule(new EnumRule(AcademicTitle::class)),
                    TextInput::make('qualification')->label('Área de Formação')->maxLength(120),
                    TextInput::make('lattes_url')->label('Currículo Lattes')->url(),
                ])
                    ->columns(3)
                    ->columnSpan(6),

                Section::make('Contato')->schema([
                    TextInput::make('email')->label('E-mail')->email()->maxLength(120),
                    TextInput::make('phone')->label('Telefone')->tel()->maxLength(20),
                    TextInput::make('mobile')->label('Celular')->tel()->maxLength(20),
                    TextInput::make('address_zip')->label('CEP')->maxLength(10),
                    TextInput::make('address_street')->label('Endereço')->maxLength(120),
                    TextInput::make('address_number')->label('Número')->maxLength(10),
                    TextInput::make('address_district')->label('Bairro')->maxLength(60),
                    TextInput::make('address_city')->label('Cidade')->maxLength(60),
                    TextInput::make('address_state')->label('UF')->maxLength(2),
                ])
                    ->columns(3)
                    ->columnSpan(6),

                Section::make('Vínculo e Carga')->schema([
                    DatePicker::make('hire_date')
                        ->label('Data de Contratação')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d'),

                    DatePicker::make('admission_date')
                        ->label('Admissão')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d'),

                    DatePicker::make('termination_date')
                        ->label('Desligamento')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->format('Y-m-d'),
                    Select::make('regime')
                        ->label('Regime')
                        ->options(TeacherRegime::options())
                        ->rule(new EnumRule(TeacherRegime::class)),
                    TextInput::make('weekly_workload')->label('Carga Semanal (h)')->numeric()->minValue(1)->maxValue(60),
                    TextInput::make('max_classes')->label('Máx. turmas')->numeric()->minValue(1)->maxValue(30),
                    Select::make('status')
                        ->label('Status')
                        ->options(TeacherStatus::options())
                        ->default(TeacherStatus::ACTIVE->value)
                        ->rule(new EnumRule(TeacherStatus::class))
                        ->required(),
                ])
                    ->columns(3)
                    ->columnSpan(6),

                Section::make('Observações')->schema([
                    Textarea::make('bio')->label('Bio / Observações')->rows(4)->columnSpanFull(),
                ])
                    ->columnSpan(12),
            ]);
    }
}
