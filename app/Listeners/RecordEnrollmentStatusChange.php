<?php

namespace App\Listeners;

use App\Events\EnrollmentStatusChanged;
use App\Models\EnrollmentLog;

/**
 * Persiste a auditoria de uma alteração de status.
 */
final class RecordEnrollmentStatusChange {

    public function handle(EnrollmentStatusChanged $event): void {
        EnrollmentLog::registrar(
            enrollment: $event->enrollment,
            acao: $event->action,
            statusAnterior: $event->previousStatus?->value,
            statusNovo: $event->newStatus->value,
            observacao: $event->observation,
            operatorId: $event->operatorId,
        );
    }
}
