<?php

namespace App\Services\Teachers;

use App\Models\Teacher;

final readonly class TeacherOnboardingResult {

    /**
     * Armazena o resultado consolidado do onboarding docente.
     *
     * @param Teacher $teacher
     * @param bool $userCreated
     * @param int $assignmentsCreated
     * @param bool $replayed
     * @param string|null $invitationUrl
     * @param bool $invitationSent
     */
    public function __construct(
        public Teacher $teacher,
        public bool $userCreated = false,
        public int $assignmentsCreated = 0,
        public bool $replayed = false,
        public ?string $invitationUrl = null,
        public bool $invitationSent = false,
    ) {
    }
}
