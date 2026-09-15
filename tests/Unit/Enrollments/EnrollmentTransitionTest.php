<?php

namespace Tests\Unit\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Modules\Enrollments\Domain\Rules\EnrollmentTransition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnrollmentTransitionTest extends TestCase {

    /**
     * @param EnrollmentStatus $from
     * @param EnrollmentStatus $to
     * @param bool $expected
     *
     * @return void
     */
    #[DataProvider('genericTransitionProvider')]
    public function test_generic_transition_rules_are_explicit(
        EnrollmentStatus $from,
        EnrollmentStatus $to,
        bool $expected,
    ): void {
        $this->assertSame($expected, (new EnrollmentTransition())->allowsGenericTransition($from, $to));
    }

    /**
     * @return array<string, array{EnrollmentStatus, EnrollmentStatus, bool}>
     */
    public static function genericTransitionProvider(): array {
        return [
            'active to suspended' => [EnrollmentStatus::ACTIVE, EnrollmentStatus::SUSPENDED, true],
            'suspended to active' => [EnrollmentStatus::SUSPENDED, EnrollmentStatus::ACTIVE, true],
            'completed to canceled' => [EnrollmentStatus::COMPLETED, EnrollmentStatus::CANCELED, false],
            'transferred external to active' => [EnrollmentStatus::TRANSFERRED_EXTERNAL, EnrollmentStatus::ACTIVE, false],
        ];
    }

    public function test_special_operations_are_not_allowed_by_generic_status_update(): void {
        $transition = new EnrollmentTransition();

        $this->assertTrue($transition->requiresSpecificOperation(EnrollmentStatus::ACTIVE, EnrollmentStatus::CANCELED));
        $this->assertTrue($transition->requiresSpecificOperation(EnrollmentStatus::ACTIVE, EnrollmentStatus::TRANSFERRED_INTERNAL));
        $this->assertFalse($transition->requiresSpecificOperation(EnrollmentStatus::SUSPENDED, EnrollmentStatus::ACTIVE));
    }
}
