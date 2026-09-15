<?php

namespace App\Events;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;

/**
 * Informa que o status de uma matrícula foi alterado por um fluxo de domínio.
 */
final class EnrollmentStatusChanged {

    public function __construct(
        public readonly Enrollment $enrollment,
        public readonly ?EnrollmentStatus $previousStatus,
        public readonly EnrollmentStatus $newStatus,
        public readonly string $action,
        public readonly ?string $observation = null,
        public readonly ?int $operatorId = null,
    ) {
    }
}
