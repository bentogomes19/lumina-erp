<?php

namespace App\Filament\Resources\Enrollments\RelationManagers;

use App\Models\EnrollmentDocument;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gerencia o checklist de documentos vinculados à matrícula.
 * Permite registrar entrega, atualizar status e fazer upload digital.
 */
class EnrollmentDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';
    protected static ?string $modelLabel = 'Documento';
    protected static ?string $pluralModelLabel = 'Documentos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('tipo')
                ->label('Tipo de Documento')
                ->options(EnrollmentDocument::TIPOS)
                ->required()
                ->searchable(),

            Select::make('status')
                ->label('Status')
                ->options(EnrollmentDocument::STATUS_OPTIONS)
                ->default('pendente')
                ->required()
                ->live(),

            DatePicker::make('data_entrega')
                ->label('Data de Entrega')
                ->nullable()
                ->visible(fn ($get) => $get('status') === 'entregue'),

            Select::make('recebido_por_user_id')
                ->label('Recebido por')
                ->relationship('recebidoPor', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn ($get) => $get('status') === 'entregue'),

            FileUpload::make('arquivo_path')
                ->label('Arquivo Digital (opcional)')
                ->helperText('PDF, JPG ou PNG — máx. 10MB')
                ->disk('private')
                ->directory('enrollment-documents')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                ->maxSize(10240) // 10MB em KB
                ->nullable()
                ->storeFileNamesIn('arquivo_nome_original'),

            Textarea::make('observacoes')
                ->label('Observações')
                ->nullable()
                ->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->label('Documento')
                    ->formatStateUsing(fn (string $state) => EnrollmentDocument::TIPOS[$state] ?? $state)
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => EnrollmentDocument::STATUS_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => EnrollmentDocument::STATUS_OPTIONS[$state] ?? $state),

                TextColumn::make('data_entrega')
                    ->label('Entregue em')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                TextColumn::make('recebidoPor.name')
                    ->label('Recebido por')
                    ->placeholder('—'),

                IconColumn::make('arquivo_path')
                    ->label('Digital')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),

                TextColumn::make('observacoes')
                    ->label('Observações')
                    ->placeholder('—')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->observacoes),
            ])
            ->defaultSort('tipo')
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
