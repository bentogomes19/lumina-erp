<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserSessionTest extends TestCase {

    use RefreshDatabase;

    /**
     * Garante que um usuário inativado com sessão aberta volta ao login, sem 403.
     *
     * @return void
     */
    public function test_inactivated_user_with_open_session_is_redirected_to_login(): void {
        $this->assertRedirectsToLogin(['active' => false]);
    }

    /**
     * Garante que um usuário bloqueado com sessão aberta volta ao login, sem 403.
     *
     * @return void
     */
    public function test_locked_user_with_open_session_is_redirected_to_login(): void {
        $this->assertRedirectsToLogin(['active' => true, 'locked_at' => now()]);
    }

    /**
     * Acessa cada painel com a sessão do usuário e confere o redirecionamento ao login.
     *
     * @param array<string, mixed> $attributes
     *
     * @return void
     */
    private function assertRedirectsToLogin(array $attributes): void {
        foreach (['lumina', 'professor', 'aluno'] as $panelId) {
            $panel = Filament::getPanel($panelId);
            $user  = User::factory()->create($attributes);

            $this->actingAs($user, $panel->getAuthGuard())
                ->get($panel->getUrl())
                ->assertRedirect($panel->getLoginUrl())
                ->assertSessionHasErrors('email');

            $this->assertGuest($panel->getAuthGuard());
        }
    }

}
