<?php

namespace App\Support;

use Illuminate\Support\Collection;

final class PermissionCatalog {

    /**
     * Retorna todas as permissões canônicas configuradas.
     *
     * @return Collection<int, array{name: string, label: string, module: string, type: string}>
     */
    public static function all(): Collection {
        return collect(config('lumina-permissions', []));
    }

    /**
     * Retorna os nomes de todas as permissões canônicas.
     *
     * @return Collection<int, string>
     */
    public static function names(): Collection {
        return self::all()->pluck('name')->values();
    }

    /**
     * Indica se o nome pertence ao catálogo canônico.
     *
     * @param string $permission
     *
     * @return bool
     */
    public static function contains(string $permission): bool {
        return self::names()->containsStrict($permission);
    }

    /**
     * Retorna o mapa de permissões legadas para seus nomes canônicos.
     *
     * @return array<string, string|array<int, string>>
     */
    public static function aliases(): array {
        return config('lumina-permission-aliases', []);
    }

    /**
     * Converte uma permissão legada para o nome canônico correspondente.
     *
     * @param string $permission
     *
     * @return string
     */
    public static function canonical(string $permission): string {
        $canonical = self::aliases()[$permission] ?? $permission;

        return is_array($canonical) ? ($canonical[0] ?? $permission) : $canonical;
    }
}
