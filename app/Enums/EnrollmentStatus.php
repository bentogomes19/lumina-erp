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
enum EnrollmentStatus: string {

    case ACTIVE               = 'Ativa';
    case SUSPENDED            = 'Suspensa';
    case LOCKED               = 'Trancada';
    case TRANSFERRED_INTERNAL = 'Transferida Interna';
    case TRANSFERRED_EXTERNAL = 'Transferida Externa';
    case CANCELED             = 'Cancelada';
    case COMPLETED            = 'Completa';

    /**
     * Retorna os valores dos status que ocupam vaga em uma turma.
     *
     * @return array<int, string>
     */
    public static function occupyingValues(): array {
        return array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->occupiesSlot()),
        );
    }

    /**
     * Indica se o status mantém uma vaga ocupada na turma.
     *
     * @return bool
     */
    public function occupiesSlot(): bool {
        return in_array($this, [
            self::ACTIVE,
            self::SUSPENDED,
            self::LOCKED,
        ], true);
    }

    /**
     * Retorna os status de matrícula disponíveis para seleção.
     *
     * @return array
     */
    public static function options(): array {
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

    /**
     * Retorna as cores usadas para representar cada status de matrícula.
     *
     * @return array
     */
    public static function colors(): array {
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

    /**
     * Retorna o rótulo do status para exibição no indicador visual.
     *
     * @return string
     */
    public function label(): string {
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

    /**
     * Retorna a cor usada para representar o status da matrícula.
     *
     * @return string
     */
    public function color(): string {
        return self::colors()[$this->value] ?? 'gray';
    }
}
