<?php

namespace App\Enums;

enum StudentOnboardingState: string {

    case WITHOUT_USER       = 'without_user';
    case WITHOUT_ENROLLMENT = 'without_enrollment';
    case COMPLETE           = 'complete';
    case INCONSISTENT       = 'inconsistent';

    /**
     * Retorna o rótulo do estado de onboarding.
     *
     * @return string
     */
    public function label(): string {
        return match ($this) {
            self::WITHOUT_USER       => 'Sem usuário',
            self::WITHOUT_ENROLLMENT => 'Sem matrícula',
            self::COMPLETE           => 'Completo',
            self::INCONSISTENT       => 'Inconsistente',
        };
    }

    /**
     * Retorna a cor usada para representar o estado no painel.
     *
     * @return string
     */
    public function color(): string {
        return match ($this) {
            self::WITHOUT_USER       => 'warning',
            self::WITHOUT_ENROLLMENT => 'info',
            self::COMPLETE           => 'success',
            self::INCONSISTENT       => 'danger',
        };
    }
}
