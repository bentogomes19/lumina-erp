<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Monta números de identificação a partir das máscaras de Parametrizações do Sistema.
 *
 * A máscara aceita marcadores como {year}; o marcador {sequence} recebe o próximo
 * número livre entre os registros que já usam o mesmo prefixo e sufixo.
 */
class NumberFormat {

    /**
     * Substitui os marcadores da máscara e calcula a sequência.
     *
     * @param string $format máscara, ex.: "ALU-{year}-{sequence}"
     * @param array<string, string> $tokens valores dos demais marcadores, ex.: ['{year}' => '2026']
     * @param Builder $existing registros já cadastrados (incluindo excluídos, para não reaproveitar números)
     * @param string $column coluna que guarda o número
     * @param int $pad quantidade mínima de dígitos da sequência
     * @param int $maxLength tamanho máximo da coluna
     *
     * @return string
     */
    public static function render(string $format, array $tokens, Builder $existing, string $column, int $pad, int $maxLength): string {
        $resolved = strtr(trim($format), $tokens);

        if (!str_contains($resolved, '{sequence}')) {
            return mb_substr($resolved, 0, $maxLength);
        }

        [$prefix, $suffix] = explode('{sequence}', $resolved, 2);

        $sequence = self::nextSequence($existing, $column, $prefix, $suffix);

        return mb_substr($prefix . str_pad((string) $sequence, $pad, '0', STR_PAD_LEFT) . $suffix, 0, $maxLength);
    }

    /**
     * Retorna o maior número já usado com o mesmo prefixo e sufixo, mais um.
     *
     * @param Builder $existing
     * @param string $column
     * @param string $prefix
     * @param string $suffix
     *
     * @return int
     */
    private static function nextSequence(Builder $existing, string $column, string $prefix, string $suffix): int {
        $like    = addcslashes($prefix, '\\%_') . '%' . addcslashes($suffix, '\\%_');
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)' . preg_quote($suffix, '/') . '$/';

        $max = $existing
            ->where($column, 'like', $like)
            ->pluck($column)
            ->map(fn (?string $value): int => $value !== null && preg_match($pattern, $value, $matches) ? (int) $matches[1] : 0)
            ->max();

        return ((int) $max) + 1;
    }

}
