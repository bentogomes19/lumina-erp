<?php

namespace App\Filament\Resources\Enrollments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Exibe o histórico de auditoria da matrícula.
 * Somente leitura — logs são imutáveis.
 */
class EnrollmentLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';

    protected static ?string $title = 'Histórico de Ações';
    protected static ?string $modelLabel = 'Registro';
    protected static ?string $pluralModelLabel = 'Histórico';

    // Aba somente leitura — sem formulário de criação
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data / Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                TextColumn::make('acao')
                    ->label('Ação')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'criacao'                => 'success',
                        'edicao'                 => 'info',
                        'trancamento'            => 'warning',
                        'reativacao'             => 'success',
                        'cancelamento'           => 'danger',
                        'reversao_cancelamento'  => 'warning',
                        'transferencia_interna'  => 'info',
                        'transferencia_externa'  => 'gray',
                        default                  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'criacao'                => 'Criação',
                        'edicao'                 => 'Edição',
                        'trancamento'            => 'Trancamento',
                        'reativacao'             => 'Reativação',
                        'cancelamento'           => 'Cancelamento',
                        'reversao_cancelamento'  => 'Reversão de Cancelamento',
                        'transferencia_interna'  => 'Transferência Interna',
                        'transferencia_externa'  => 'Transferência Externa',
                        default                  => ucfirst($state),
                    }),

                TextColumn::make('status_anterior')
                    ->label('Status Anterior')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status_novo')
                    ->label('Novo Status')
                    ->placeholder('—')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('operador.name')
                    ->label('Operador')
                    ->placeholder('Sistema')
                    ->searchable(),

                TextColumn::make('ip_origem')
                    ->label('IP')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('observacao')
                    ->label('Observação')
                    ->placeholder('—')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->observacao),
            ])
            ->defaultSort('created_at', 'desc')
            // Sem ações — logs são somente leitura
            ->recordActions([])
            ->toolbarActions([]);
    }
}
