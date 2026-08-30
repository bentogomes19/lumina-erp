<?php

namespace Tests\Unit;

use App\Enums\TeacherStatus;
use App\Models\Teacher;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TeacherOperationalAccessTest extends TestCase {

    /**
     * Garante que somente professor ativo e sem desligamento tenha situação operacional.
     *
     * @param TeacherStatus $status
     * @param string|null $terminationDate
     * @param bool $expected
     *
     * @return void
     */
    #[DataProvider('operationalStatuses')]
    public function test_teacher_operational_access_rule(
        TeacherStatus $status,
        ?string $terminationDate,
        bool $expected,
    ): void {
        $teacher = new Teacher([
            'status'           => $status->value,
            'termination_date' => $terminationDate,
        ]);

        $this->assertSame($expected, $teacher->canAccessOperationally());
    }

    /**
     * Retorna situações funcionais usadas para validar a regra de acesso docente.
     *
     * @return array<string, array{TeacherStatus, string|null, bool}>
     */
    public static function operationalStatuses(): array {
        return [
            'ativo'                       => [TeacherStatus::ACTIVE, null, true],
            'ativo com desligamento'      => [TeacherStatus::ACTIVE, '2026-08-30', false],
            'inativo'                     => [TeacherStatus::INACTIVE, null, false],
            'afastado'                    => [TeacherStatus::SABBATICAL, null, false],
            'desligado'                   => [TeacherStatus::TERMINATED, '2026-08-30', false],
        ];
    }
}
