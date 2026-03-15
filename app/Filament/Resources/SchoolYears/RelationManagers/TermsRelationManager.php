<?php

namespace App\Filament\Resources\SchoolYears\RelationManagers;

use App\Enums\TermType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class TermsRelationManager extends RelationManager
{
    protected static string $relationship = 'terms';

    protected static ?string $title = 'Períodos Avaliativos';
    protected static ?string $modelLabel = 'Período';
    protected static ?string $pluralModelLabel = 'Períodos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(50)
                ->placeholder('Ex.: 1º Bimestre'),

            Select::make('type')
                ->label('Tipo')
                ->options(TermType::toArray())
                ->required(),

            TextInput::make('sequence')
                ->label('Sequência')
                ->numeric()
                ->minValue(1)
                ->maxValue(10)
                ->required()
                ->helperText('Ordem do período dentro do ano letivo.'),

            DatePicker::make('starts_at')
                ->label('Início do Período')
                ->required(),

            DatePicker::make('ends_at')
                ->label('Fim do Período')
                ->required()
                ->after('starts_at'),

            DatePicker::make('grade_entry_starts_at')
                ->label('Início Lançamento de Notas')
                ->nullable(),

            DatePicker::make('grade_entry_ends_at')
                ->label('Fim Lançamento de Notas')
                ->nullable()
                ->after('grade_entry_starts_at'),

            Toggle::make('grades_published')
                ->label('Notas publicadas no portal')
                ->default(false)
                ->helperText('Quando ativo, os alunos podem visualizar as notas deste período.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sequence')
                    ->label('Seq.')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => $state instanceof TermType ? $state->label() : (string) $state),

                TextColumn::make('starts_at')
                    ->label('Início')
                    ->date('d/m/Y'),

                TextColumn::make('ends_at')
                    ->label('Fim')
                    ->date('d/m/Y'),

                TextColumn::make('grade_entry_starts_at')
                    ->label('Lançamento')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                TextColumn::make('grade_entry_ends_at')
                    ->label('Até')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                IconColumn::make('grades_published')
                    ->label('Publicado')
                    ->boolean(),
            ])
            ->defaultSort('sequence')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
