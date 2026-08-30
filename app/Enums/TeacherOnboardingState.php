<?php

namespace App\Enums;

enum TeacherOnboardingState: string {

    case WITHOUT_USER       = 'without_user';
    case WITHOUT_ASSIGNMENT = 'without_assignment';
    case READY_FOR_ACCESS   = 'ready_for_access';
    case ACCESS_BLOCKED     = 'access_blocked';

    /**
     * Retorna o rótulo do estado de onboarding docente.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::WITHOUT_USER       => 'Sem usuário',
            self::WITHOUT_ASSIGNMENT => 'Sem alocação',
            self::READY_FOR_ACCESS   => 'Pronto para acesso',
            self::ACCESS_BLOCKED     => 'Acesso bloqueado',
        };
    }

    /**
     * Retorna a cor usada no painel administrativo.
     *
     * @return string
     */
    public function color(): string {
        return match ($this) {
            self::WITHOUT_USER       => 'warning',
            self::WITHOUT_ASSIGNMENT => 'info',
            self::READY_FOR_ACCESS   => 'success',
            self::ACCESS_BLOCKED     => 'danger',
        };
    }
}
