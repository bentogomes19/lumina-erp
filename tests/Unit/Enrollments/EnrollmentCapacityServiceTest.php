<?php

namespace Tests\Unit\Enrollments;

use App\Models\SchoolClass;
use App\Modules\Enrollments\Application\EnrollmentCapacityService;
use PHPUnit\Framework\TestCase;

class EnrollmentCapacityServiceTest extends TestCase {

    public function test_preloaded_occupation_is_used_without_requerying(): void {
        $schoolClass = new SchoolClass([
            'capacity' => 30,
        ]);
        $schoolClass->setAttribute('occupied_slots_count', 12);

        $service = new EnrollmentCapacityService();

        $this->assertSame(12, $service->occupiedSlots($schoolClass));
        $this->assertSame(18, $service->remainingSlots($schoolClass));
        $this->assertSame('Ocupação: 12/30 | Vagas restantes: 18', $service->summary($schoolClass));
    }

    public function test_null_capacity_represents_unlimited_class(): void {
        $schoolClass = new SchoolClass();
        $schoolClass->setAttribute('occupied_slots_count', 4);

        $service = new EnrollmentCapacityService();

        $this->assertNull($service->remainingSlots($schoolClass));
        $this->assertSame('Ocupação: 4 | Vagas: ilimitadas', $service->summary($schoolClass));
    }
}
