<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Panel;
use Tests\TestCase;

class UserPanelAccessTest extends TestCase
{
    public function test_active_unlocked_user_can_access_filament_panel(): void
    {
        $user = new User([
            'active' => true,
        ]);

        $this->assertTrue($user->canAccessPanel($this->createMock(Panel::class)));
    }

    public function test_inactive_user_cannot_access_filament_panel(): void
    {
        $user = new User([
            'active' => false,
        ]);

        $this->assertFalse($user->canAccessPanel($this->createMock(Panel::class)));
    }

    public function test_locked_user_cannot_access_filament_panel(): void
    {
        $user = new User;
        $user->setRawAttributes([
            'active' => true,
            'locked_at' => '2026-05-02 00:00:00',
        ]);

        $this->assertFalse($user->canAccessPanel($this->createMock(Panel::class)));
    }
}
