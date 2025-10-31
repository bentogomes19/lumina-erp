<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('uuid')
                    ->hidden()
                    ->label('UUID')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->description(fn($record) => $record->email)
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('roles.name')
                    ->label('Perfil')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'admin' => 'Administrador',
                            'teacher' => 'Professor',
                            'student' => 'Aluno',
                            default => ucfirst($state ?? '—'),
                        };
                    })
                    ->color(fn($state) => match ($state) {
                        'admin' => 'danger',
                        'teacher' => 'warning',
                        'student' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                ImageColumn::make('avatar')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png'))
                    ->size(size: 30)
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
            ])
            ->filters([

                SelectFilter::make('role')
                    ->label('Perfil')
                    ->options([
                        'admin' => 'Administrador',
                        'teacher' => 'Professor',
                        'student' => 'Aluno',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value']) {
                            $query->whereHas('roles', fn($q) => $q->where('name', $data['value']));
                        }
                    }),

                // Filtra por ativo / inativo
                TernaryFilter::make('active')
                    ->label('Status')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos')
                    ->placeholder('Todos'),

                // Filtro por intervalo de datas
                Filter::make('created_at')
                    ->label('Criado em')
                    ->form([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->query(function ($query, $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),

                // Filtra pela cidade
                SelectFilter::make('city')
                    ->label('Cidade')
                    ->options(fn() => \App\Models\User::query()->pluck('city', 'city')->filter()->unique())
                    ->searchable(),

                TrashedFilter::make(),

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
