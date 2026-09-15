<?php

namespace App\Filament\Resources\SystemParameters\Schemas;

use App\Enums\SystemParameterType;
use App\Models\SystemParameter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SystemParameterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::fields());
    }

    /**
     * Campos reutilizáveis pelo formulário da página e pelo EditAction modal.
     *
     * @return array<int, mixed>
     */
    public static function fields(): array
    {
        return [
            Section::make('Regra e valor')
                ->description('Defina o valor que será utilizado pelo sistema. A alteração só produz efeito quando a parametrização estiver ativa.')
                ->icon('fas-sliders')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        TextInput::make('name')->label('Nome')->required()->maxLength(150)->disabled()->dehydrated(false),
                        TextInput::make('key')->label('Chave técnica')->required()->maxLength(100)->unique(ignoreRecord: true)
                            ->disabled()->dehydrated(false)
                            ->helperText('Identificador usado internamente. Não altere uma chave de sistema já utilizada.'),
                        Select::make('category')->label('Categoria')->required()->disabled()->dehydrated(false)->options([
                            'Identidade e personalização' => 'Identidade e personalização',
                            'Segurança e usuários' => 'Segurança e usuários',
                            'Matrículas e cadastros' => 'Matrículas e cadastros',
                            'Regras acadêmicas' => 'Regras acadêmicas',
                        ]),
                        Select::make('type')->label('Tipo de valor')->required()->disabled()->dehydrated(false)->options(SystemParameterType::options())->live()
                            ->helperText('O tipo determina como o valor será validado e interpretado.'),
                    ]),
                    TextInput::make('value')->label('Valor atual')->visible(fn ($get): bool => !in_array($get('type'), ['boolean', 'select', 'file'], true))
                        ->required(fn ($get): bool => $get('type') !== 'file')->numeric(fn ($get): bool => in_array($get('type'), ['integer', 'decimal'], true)),
                    TextInput::make('default_value')->label('Valor padrão')->disabled()->dehydrated(false)
                        ->helperText('Referência original do sistema. O valor padrão não é alterado ao personalizar a regra.'),
                    Toggle::make('boolean_value')->label('Ativo')->formatStateUsing(fn ($state): bool => filter_var($state, FILTER_VALIDATE_BOOLEAN))
                        ->dehydrateStateUsing(fn ($state): string => $state ? '1' : '0')->visible(fn ($get): bool => $get('type') === 'boolean'),
                    Select::make('select_value')->label('Valor atual')->options(fn ($get): array => collect($get('options') ?? [])->mapWithKeys(fn ($option) => [$option => $option])->all())
                        ->visible(fn ($get): bool => $get('type') === 'select')->required(fn ($get): bool => $get('type') === 'select'),
                    FileUpload::make('file_value')->label('Arquivo atual')->image()->disk('public')->directory('system')->visibility('public')
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'])
                        ->maxSize(2048)
                        ->openable()
                        ->downloadable()
                        ->helperText('Envie uma logo em PNG, JPG, WebP ou SVG. Deixe vazio para remover a logo atual.')
                        ->visible(fn ($get): bool => $get('type') === 'file'),
                    Textarea::make('options')->label('Opções da lista')->rows(3)->disabled()->dehydrated(false)
                        ->helperText('Opções fixas do parâmetro. O valor escolhido pode ser alterado no campo “Valor atual”.')
                        ->visible(fn ($get): bool => $get('type') === 'select')
                        ->dehydrateStateUsing(fn ($state): ?array => filled($state) ? preg_split('/\r\n|\r|\n/', trim($state)) : null)
                        ->formatStateUsing(fn ($state): string => is_array($state) ? implode("\n", $state) : (string) $state),
                    Toggle::make('is_active')->label('Parametrização ativa')->default(true)->helperText('Desative para manter o valor salvo sem aplicá-lo ao sistema.'),
                ]),
            Section::make('Como funciona')
                ->description('Esta seção é informativa e acompanha a parametrização para reduzir erros de configuração.')
                ->icon('fas-circle-info')
                ->schema([
                    TextEntry::make('usage_help')->label('Impacto da regra')->state(fn (?SystemParameter $record): string => $record?->explanation() ?? 'Ao ativar este parâmetro, o sistema irá aplicar a regra configurada nas rotinas correspondentes.')
                        ->markdown(),
                    TextEntry::make('metadata')->label('Controle')->state(fn (?SystemParameter $record): string => $record?->is_system ? 'Parâmetro padrão do sistema' : 'Parâmetro personalizado'),
                ])
                ->hidden(fn (?SystemParameter $record): bool => $record === null),
        ];
    }

    /** Normaliza campos específicos do Filament para o único valor persistido. */
    public static function normalizeData(array $data): array
    {
        // Campos imutáveis (incluindo `type`) não são enviados pelo Filament
        // quando estão desabilitados. A presença do campo auxiliar é a fonte
        // segura do tipo durante a persistência do valor editável.
        if (array_key_exists('boolean_value', $data)) {
            $data['value'] = $data['boolean_value'] ? '1' : '0';
        } elseif (array_key_exists('select_value', $data)) {
            $data['value'] = $data['select_value'];
        } elseif (array_key_exists('file_value', $data)) {
            $data['value'] = $data['file_value'];
        }

        unset($data['boolean_value'], $data['select_value'], $data['file_value']);

        return $data;
    }

    /** Preenche o campo de apresentação correto ao abrir uma edição. */
    public static function presentData(array $data): array
    {
        $type = $data['type'] ?? null;
        $type = $type instanceof SystemParameterType ? $type->value : $type;

        if ($type === SystemParameterType::BOOLEAN->value) {
            $data['boolean_value'] = filter_var($data['value'] ?? false, FILTER_VALIDATE_BOOLEAN);
        } elseif ($type === SystemParameterType::SELECT->value) {
            $data['select_value'] = $data['value'] ?? null;
        } elseif ($type === SystemParameterType::FILE->value) {
            $data['file_value'] = $data['value'] ?? null;
        }

        return $data;
    }
}
