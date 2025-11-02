<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome do Perfil')
                                    ->required()
                                    ->maxLength(50)
                                    ->live(onBlur: true),

                                TextInput::make('guard_name')
                                    ->label('Guard')
                                    ->default('web')
                                    ->required()
                                    ->maxLength(20),
                            ]),

                        Select::make('permissions')
                            ->label('Permissões')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->relationship('permissions', 'name')
                            ->helperText('Selecione as permissões que este perfil poderá executar.'),

                    ])
                    ->columnSpan(['lg' => fn (?Role $record) => $record === null ? 3 : 2]),

                Section::make('Metadados')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Criado')
                            ->state(fn (?Role $record) => $record?->created_at?->diffForHumans()),

                        TextEntry::make('updated_at')
                            ->label('Atualizado')
                            ->state(fn (?Role $record) => $record?->updated_at?->diffForHumans()),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?Role $record) => $record === null),
            ])
            ->columns(3);
    }
}
