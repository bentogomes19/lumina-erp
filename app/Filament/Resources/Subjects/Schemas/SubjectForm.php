<?php

namespace App\Filament\Resources\Subjects\Schemas;

use App\Enums\SubjectCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Enum as EnumRule;

class SubjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->helperText('Ex.: PORT-BAS, MAT-1, BIOG')
                        ->regex('/^[A-Za-z0-9\-_.]+$/')
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),

                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(120),

                    Select::make('category')
                        ->label('Componente Curricular')
                        ->options(SubjectCategory::toArray())
                        ->required()
                        ->rule(new EnumRule(SubjectCategory::class)), // ✅ valida enum

                    Select::make('status')
                        ->label('Status')
                        ->options(['active' => 'Ativa', 'inactive' => 'Inativa'])
                        ->default('active')
                        ->required(),
                ])->columns(4)->columnSpan(8),

                Section::make('BNCC')->schema([
                    TextInput::make('bncc_code')
                        ->label('Código BNCC')
                        ->helperText('Ex.: EF06LP01')
                        ->maxLength(20),

                    TextInput::make('bncc_reference_url')
                        ->label('Referência BNCC (URL)')
                        ->url()
                        ->maxLength(255),

                    TagsInput::make('tags')
                        ->label('Tags / Eixos')
                        ->placeholder('Adicionar tag…')
                        ->suggestions(['Leitura','Produção textual','Geometria','Álgebra','Investigação','Projetos']),
                ])->columns(2)->columnSpan(4),

                Section::make('Descrição')->schema([
                    Textarea::make('description')
                        ->label('Descrição / Observações')
                        ->rows(4)
                        ->columnSpanFull(),
                ])->columnSpan(12),
            ]);
    }
}
