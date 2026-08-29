<?php

namespace App\Services\Enrollments;

use App\Models\Enrollment;

final readonly class StudentEnrollmentResult {

    /**
     * Armazena o resultado da criação atômica de uma matrícula.
     *
     * @param Enrollment $enrollment
     * @param bool $userCreated
     * @param bool $replayed
     */
    public function __construct(
        public Enrollment $enrollment,
        public bool $userCreated,
        public bool $replayed = false,
    ) {
    }
}
