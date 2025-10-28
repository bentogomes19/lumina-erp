<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->hidden()
                    ->label('UUID')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->description(fn ($record) => $record->email)
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
                    ->color(fn ($state) => match ($state) {
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
                    ->dateTime()
                    ->sortable()
                    ->hidden(),
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
