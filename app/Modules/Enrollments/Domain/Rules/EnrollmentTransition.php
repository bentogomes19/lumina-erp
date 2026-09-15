<?php

namespace App\Modules\Enrollments\Domain\Rules;

use App\Enums\EnrollmentStatus;

/**
 * Regra de transição do ciclo de vida de uma matrícula.
 *
 * Esta classe não conhece Filament, banco de dados ou autenticação. Ela apenas
 * informa se uma transição genérica é permitida e se a operação exige um fluxo
 * específico, como transferência ou cancelamento.
 */
final class EnrollmentTransition {

    /**
     * Transições permitidas pelo fluxo genérico de alteração de status.
     * Operações especiais ficam protegidas por requiresSpecificOperation().
     *
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_STATUS_TRANSITIONS = [
        'Ativa'     => ['Suspensa', 'Trancada', 'Transferida Interna', 'Transferida Externa', 'Cancelada', 'Completa'],
        'Suspensa'  => ['Ativa', 'Cancelada', 'Transferida Externa'],
        'Trancada'  => ['Ativa', 'Cancelada', 'Transferida Externa'],
        'Completa'  => ['Ativa'],
        'Cancelada' => ['Ativa'],
    ];

    /**
     * Status que só podem ser alterados pelos casos de uso específicos.
     *
     * @var array<int, EnrollmentStatus>
     */
    private const SPECIFIC_OPERATION_STATUSES = [
        EnrollmentStatus::LOCKED,
        EnrollmentStatus::CANCELED,
        EnrollmentStatus::TRANSFERRED_INTERNAL,
        EnrollmentStatus::TRANSFERRED_EXTERNAL,
    ];

    /**
     * Verifica se a origem está entre as origens permitidas por um fluxo.
     *
     * @param EnrollmentStatus|null $from
     * @param array<int, EnrollmentStatus>|null $allowedSources
     *
     * @return bool
     */
    public function hasAllowedSource(?EnrollmentStatus $from, ?array $allowedSources): bool {
        return $allowedSources === null
            || ($from !== null && in_array($from, $allowedSources, true));
    }

    /**
     * Indica se a transição deve passar por uma operação de domínio específica.
     *
     * @param EnrollmentStatus|null $from
     * @param EnrollmentStatus $to
     *
     * @return bool
     */
    public function requiresSpecificOperation(?EnrollmentStatus $from, EnrollmentStatus $to): bool {
        if (!$from || $from === $to) {
            return false;
        }

        return in_array($to, self::SPECIFIC_OPERATION_STATUSES, true)
            || in_array($from, [
                EnrollmentStatus::CANCELED,
                EnrollmentStatus::TRANSFERRED_INTERNAL,
                EnrollmentStatus::TRANSFERRED_EXTERNAL,
            ], true);
    }

    /**
     * Verifica se a transição genérica está prevista no fluxo acadêmico.
     *
     * @param EnrollmentStatus|null $from
     * @param EnrollmentStatus $to
     *
     * @return bool
     */
    public function allowsGenericTransition(?EnrollmentStatus $from, EnrollmentStatus $to): bool {
        if (!$from || $from === $to) {
            return true;
        }

        return in_array(
            $to->value,
            self::ALLOWED_STATUS_TRANSITIONS[$from->value] ?? [],
            true,
        );
    }
}
