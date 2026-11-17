<?php

namespace App\Filament\Resources\SystemParameters\Schemas;

use App\Enums\SystemParameterType;
use App\Models\SystemParameter;
use App\Support\NumberFormatRules;
use App\Support\SystemBranding;
use Closure;
use Filament\Forms\Components\ColorPicker;
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
                    TextInput::make('value')->label('Valor atual')
                        ->visible(fn ($get): bool => !in_array($get('type'), ['boolean', 'select', 'file'], true) && $get('key') !== self::COLOR_KEY)
                        /* Máscaras de numeração: texto livre, com sugestões, validação e prévia do número gerado. */
                        ->datalist(fn ($get): ?array => NumberFormatRules::SUGGESTIONS[$get('key')] ?? null)
                        ->live(onBlur: true)
                        ->rules([
                            fn ($get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                if (NumberFormatRules::handles($get('key')) && ($error = NumberFormatRules::error($get('key'), $value))) {
                                    $fail($error);
                                }
                            },
                        ])
                        ->required(fn ($get): bool => $get('type') !== 'file')->numeric(fn ($get): bool => in_array($get('type'), ['integer', 'decimal'], true))
                        ->minValue(fn ($get) => $get('key') === 'academic.minimum_grade' ? 0 : null)
                        ->maxValue(fn ($get) => $get('key') === 'academic.minimum_grade' ? 10 : null)
                        ->helperText(fn ($get) => match (true) {
                            $get('key') === 'academic.minimum_grade' => 'Média da escola na escala de 0 a 10. Altera as cores e o acompanhamento das notas no portal do aluno e no boletim.',
                            NumberFormatRules::handles($get('key')) => NumberFormatRules::help($get('key'))
                                .(filled($get('value')) && NumberFormatRules::error($get('key'), $get('value')) === null
                                    ? ' Exemplo: '.NumberFormatRules::preview($get('key'), $get('value')).'.'
                                    : ''),
                            default => null,
                        }),
                    ColorPicker::make('color_value')->label('Valor atual')->hex()->required()
                        ->regex('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/')
                        ->helperText('Escolha a cor na paleta ou digite o código hexadecimal (ex.: #3D5A80). Aplicada ao painel administrativo e ao portal do aluno.')
                        ->visible(fn ($get): bool => $get('key') === self::COLOR_KEY),
                    TextInput::make('default_value')->label('Valor padrão')->disabled()->dehydrated(false)
                        ->helperText('Referência original do sistema. O valor padrão não é alterado ao personalizar a regra.'),
                    Toggle::make('boolean_value')->label('Ativo')->formatStateUsing(fn ($state): bool => filter_var($state, FILTER_VALIDATE_BOOLEAN))
                        ->dehydrateStateUsing(fn ($state): string => $state ? '1' : '0')->visible(fn ($get): bool => $get('type') === 'boolean'),
                    /* "options" chega como texto (uma opção por linha) porque o Textarea abaixo o formata assim. */
                    Select::make('select_value')->label('Valor atual')->options(fn ($get): array => self::optionList($get('options')))
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

    public const COLOR_KEY = 'institution.primary_color';

    /**
     * Converte as opções da lista (array salvo ou texto com uma opção por linha) em opções do Select.
     *
     * @return array<string, string>
     */
    public static function optionList(mixed $options): array
    {
        $list = is_array($options) ? $options : preg_split('/\r\n|\r|\n/', trim((string) $options));

        return collect($list)->map(fn ($option): string => trim((string) $option))->filter()->mapWithKeys(fn (string $option) => [$option => $option])->all();
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
        } elseif (array_key_exists('color_value', $data)) {
            $data['value'] = strtoupper((string) $data['color_value']);
        }

        if (isset($data['value']) && is_string($data['value']) && str_contains($data['value'], '{')) {
            $data['value'] = trim($data['value']);
        }

        unset($data['boolean_value'], $data['select_value'], $data['file_value'], $data['color_value']);

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

        if (($data['key'] ?? null) === self::COLOR_KEY) {
            $data['color_value'] = app(SystemBranding::class)->primaryColor();
        }

        return $data;
    }
}
