<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Panel;
use Tests\TestCase;

class UserPanelAccessTest extends TestCase {

    /**
     * Verifica se um usuário ativo e desbloqueado pode acessar o painel.
     *
     * @return void
     */
    public function test_active_unlocked_user_cannot_access_an_unassigned_panel(): void {
        $user = new User([
            'active' => true,
        ]);

        $panel = $this->createMock(Panel::class);
        $panel->method('getId')->willReturn('unassigned');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * Verifica se um usuário inativo não pode acessar o painel.
     *
     * @return void
     */
    public function test_inactive_user_cannot_access_filament_panel(): void {
        $user = new User([
            'active' => false,
        ]);

        $this->assertFalse($user->canAccessPanel($this->createMock(Panel::class)));
    }

    /**
     * Verifica se um usuário bloqueado não pode acessar o painel.
     *
     * @return void
     */
    public function test_locked_user_cannot_access_filament_panel(): void {
        $user = new User();
        $user->setRawAttributes([
            'active'    => true,
            'locked_at' => '2026-05-02 00:00:00',
        ]);

        $this->assertFalse($user->canAccessPanel($this->createMock(Panel::class)));
    }
}
