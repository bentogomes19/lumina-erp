<?php

namespace App\Modules\Enrollments\Application;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\SchoolClass;

/**
 * Centraliza as regras de ocupação e capacidade das turmas.
 *
 * O serviço pode usar uma contagem previamente carregada pela consulta da
 * tela, evitando uma nova consulta quando occupied_slots_count já existir.
 */
final class EnrollmentCapacityService {

    /**
     * Retorna a quantidade de matrículas que ocupam vaga na turma.
     *
     * @param SchoolClass $schoolClass
     *
     * @return int
     */
    public function occupiedSlots(SchoolClass $schoolClass): int {
        if (array_key_exists('occupied_slots_count', $schoolClass->getAttributes())) {
            return (int) $schoolClass->getAttribute('occupied_slots_count');
        }

        return Enrollment::query()
            ->where('class_id', $schoolClass->id)
            ->whereIn('status', EnrollmentStatus::occupyingValues())
            ->count();
    }

    /**
     * Retorna as vagas restantes ou nulo quando a turma é ilimitada.
     *
     * @param SchoolClass $schoolClass
     *
     * @return int|null
     */
    public function remainingSlots(SchoolClass $schoolClass): ?int {
        if (!$schoolClass->capacity) {
            return null;
        }

        return max(0, $schoolClass->capacity - $this->occupiedSlots($schoolClass));
    }

    /**
     * Formata capacidade, ocupação e vagas restantes para a interface.
     *
     * @param SchoolClass $schoolClass
     *
     * @return string
     */
    public function summary(SchoolClass $schoolClass): string {
        $occupied = $this->occupiedSlots($schoolClass);

        if (!$schoolClass->capacity) {
            return "Ocupação: {$occupied} | Vagas: ilimitadas";
        }

        $remaining = max(0, $schoolClass->capacity - $occupied);

        return "Ocupação: {$occupied}/{$schoolClass->capacity} | Vagas restantes: {$remaining}";
    }
}
