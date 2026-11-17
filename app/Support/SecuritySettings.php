<?php

namespace App\Support;

use App\Models\SystemParameter;

/**
 * Lê os parâmetros de segurança cadastrados em Parametrizações do Sistema.
 *
 * Cada valor é limitado a uma faixa segura para que um cadastro incorreto
 * (zero, negativo ou absurdo) não desligue a proteção correspondente.
 */
class SecuritySettings {

    public const DEFAULT_LOGIN_ATTEMPTS = 5;

    public const DEFAULT_SESSION_MINUTES = 120;

    public const DEFAULT_PASSWORD_MIN_LENGTH = 8;

    /**
     * Tentativas de login inválidas permitidas antes do bloqueio (1 a 20).
     *
     * @return int
     */
    public static function maxLoginAttempts(): int {
        return self::integer('security.login_attempts', self::DEFAULT_LOGIN_ATTEMPTS, 1, 20);
    }

    /**
     * Minutos de inatividade até a sessão expirar (5 minutos a 24 horas).
     *
     * @return int
     */
    public static function sessionMinutes(): int {
        return self::integer('security.session_minutes', self::DEFAULT_SESSION_MINUTES, 5, 1440);
    }

    /**
     * Tamanho mínimo de novas senhas (6 a 64 caracteres).
     *
     * @return int
     */
    public static function passwordMinLength(): int {
        return self::integer('security.password_min_length', self::DEFAULT_PASSWORD_MIN_LENGTH, 6, 64);
    }

    /**
     * Dias até a senha expirar; zero desativa a expiração (máximo de 10 anos).
     *
     * @return int
     */
    public static function passwordExpirationDays(): int {
        return self::integer('security.password_expiration_days', 0, 0, 3650);
    }

    /**
     * Lê um parâmetro inteiro, limitado à faixa informada.
     *
     * @param string $key
     * @param int $default
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    private static function integer(string $key, int $default, int $min, int $max): int {
        $value = SystemParameter::read($key, $default);

        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }

}
