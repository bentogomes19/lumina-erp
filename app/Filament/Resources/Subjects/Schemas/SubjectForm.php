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

class SubjectForm {

    /**
     * Configura os campos do formulário de disciplinas.
     *
     * @param Schema $schema
     *
     * @return Schema
     */
    public static function configure(Schema $schema): Schema {
        return $schema
            ->components([
                /* Duas linhas com colunas mais largas: rótulos em uma linha e campos alinhados. */
                Section::make('Identificação')->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->helperText('Ex.: PORT-BAS, MAT-1, BIOG')
                        ->regex('/^[A-Za-z0-9\-_.]+$/')
                        ->maxLength(20)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(4),

                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(120)
                        ->columnSpan(8),

                    Select::make('category')
                        ->label('Componente Curricular')
                        ->options(SubjectCategory::toArray())
                        ->required()
                        ->rule(new EnumRule(SubjectCategory::class)) /* Valida a categoria da disciplina. */
                        ->columnSpan(8),

                    Select::make('status')
                        ->label('Status')
                        ->options(['active' => 'Ativa', 'inactive' => 'Inativa'])
                        ->default('active')
                        ->required()
                        ->columnSpan(4),
                ])->columns(12)->columnSpan(8),

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
