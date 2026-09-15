<?php

namespace App\Enums;

enum SystemParameterType: string
{
    case TEXT = 'text';
    case INTEGER = 'integer';
    case DECIMAL = 'decimal';
    case BOOLEAN = 'boolean';
    case SELECT = 'select';
    case FILE = 'file';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Texto',
            self::INTEGER => 'Número inteiro',
            self::DECIMAL => 'Número decimal',
            self::BOOLEAN => 'Sim / Não',
            self::SELECT => 'Lista de opções',
            self::FILE => 'Arquivo',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->label()])->all();
    }
}
