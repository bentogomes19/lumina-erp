<?php

namespace App\Enums;

/**
 * Status do ciclo de vida de uma matrícula.
 *
 * Fluxo:
 *   Nova Matrícula → [Ativa]
 *   [Ativa] → [Trancada]        → reativação → [Ativa]
 *   [Ativa] → [Transferida Interna] → nova [Ativa] em outra turma
 *   [Ativa] → [Transferida Externa] (encerramento definitivo no período)
 *   [Ativa] → [Cancelada]       (irreversível no fluxo normal)
 *   [Ativa] → [Concluída]       (encerramento do ano letivo)
 */
enum EnrollmentStatus: string
{
    case ACTIVE               = 'Ativa';
    case SUSPENDED            = 'Suspensa';
    case LOCKED               = 'Trancada';
    case TRANSFERRED_INTERNAL = 'Transferida Interna';
    case TRANSFERRED_EXTERNAL = 'Transferida Externa';
    case CANCELED             = 'Cancelada';
    case COMPLETED            = 'Completa';

    public static function options(): array
    {
        return [
            self::ACTIVE->value               => 'Ativa',
            self::SUSPENDED->value            => 'Suspensa',
            self::LOCKED->value               => 'Trancada',
            self::TRANSFERRED_INTERNAL->value => 'Transferida (Interna)',
            self::TRANSFERRED_EXTERNAL->value => 'Transferida (Externa)',
            self::CANCELED->value             => 'Cancelada',
            self::COMPLETED->value            => 'Concluída',
        ];
    }

    public static function colors(): array
    {
        return [
            self::ACTIVE->value               => 'success',
            self::SUSPENDED->value            => 'warning',
            self::LOCKED->value               => 'gray',
            self::TRANSFERRED_INTERNAL->value => 'info',
            self::TRANSFERRED_EXTERNAL->value => 'purple',
            self::CANCELED->value             => 'danger',
            self::COMPLETED->value            => 'primary',
        ];
    }

    /** Retorna label para exibição no badge */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE               => 'Ativa',
            self::SUSPENDED            => 'Suspensa',
            self::LOCKED               => 'Trancada',
            self::TRANSFERRED_INTERNAL => 'Transferida (Interna)',
            self::TRANSFERRED_EXTERNAL => 'Transferida (Externa)',
            self::CANCELED             => 'Cancelada',
            self::COMPLETED            => 'Concluída',
        };
    }

    public function color(): string
    {
        return self::colors()[$this->value] ?? 'gray';
    }
}
