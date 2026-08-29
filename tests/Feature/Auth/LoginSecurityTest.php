<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class LoginSecurityTest extends TestCase {

    use RefreshDatabase;

    /**
     * Prepara o painel e limpa os limites compartilhados entre os cenários.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
        RateLimiter::clear($this->identityRateLimitKey('usuario@example.test'));
        RateLimiter::clear($this->ipRateLimitKey());
    }

    /**
     * Verifica que quatro falhas são descartadas após um login válido.
     *
     * @return void
     */
    public function test_successful_login_resets_four_previous_failures(): void {
        $user = $this->user();

        for ($attempt = 1; $attempt <= 4; $attempt++) {
            $this->login($user->email, 'senha-incorreta')
                ->assertHasFormErrors(['email']);

            $this->assertSame($attempt, $user->fresh()->login_attempts);
        }

        $this->login($user->email, 'Senha-correta-2026')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, $user->fresh()->login_attempts);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertNull($user->fresh()->locked_at);
        $this->assertFalse(RateLimiter::tooManyAttempts(
            $this->identityRateLimitKey($user->email),
            User::MAX_LOGIN_ATTEMPTS,
        ));
        $this->assertFalse(RateLimiter::tooManyAttempts(
            $this->ipRateLimitKey(),
            User::MAX_LOGIN_ATTEMPTS,
        ));
    }

    /**
     * Verifica que a quinta falha bloqueia a conta e impede o acesso posterior.
     *
     * @return void
     */
    public function test_fifth_failure_locks_user_and_denies_correct_password(): void {
        $user = $this->user();

        for ($attempt = 1; $attempt <= User::MAX_LOGIN_ATTEMPTS; $attempt++) {
            $this->login($user->email, 'senha-incorreta')
                ->assertHasFormErrors(['email']);
        }

        $this->assertSame(User::MAX_LOGIN_ATTEMPTS, $user->fresh()->login_attempts);
        $this->assertNotNull($user->fresh()->locked_at);

        RateLimiter::clear($this->identityRateLimitKey($user->email));
        RateLimiter::clear($this->ipRateLimitKey());

        $this->login($user->email, 'Senha-correta-2026')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
        $this->assertSame(User::MAX_LOGIN_ATTEMPTS, $user->fresh()->login_attempts);
    }

    /**
     * Verifica que o desbloqueio administrativo remove o bloqueio e zera o contador.
     *
     * @return void
     */
    public function test_administrative_unlock_resets_security_state(): void {
        $administrator = $this->user(['email' => 'admin@example.test']);
        $user          = $this->user([
            'login_attempts' => User::MAX_LOGIN_ATTEMPTS,
            'locked_at'      => now(),
        ]);

        $this->actingAs($administrator);
        $user->unlock();

        $this->assertNull($user->fresh()->locked_at);
        $this->assertSame(0, $user->fresh()->login_attempts);
    }

    /**
     * Verifica que os limites de identidade e IP interrompem novas autenticações.
     *
     * @return void
     */
    public function test_login_is_rate_limited_by_identity_and_ip(): void {
        $email = 'inexistente@example.test';

        for ($attempt = 1; $attempt <= User::MAX_LOGIN_ATTEMPTS; $attempt++) {
            $this->login($email, 'senha-incorreta')
                ->assertHasFormErrors(['email']);
        }

        $this->assertTrue(RateLimiter::tooManyAttempts(
            $this->identityRateLimitKey($email),
            User::MAX_LOGIN_ATTEMPTS,
        ));
        $this->assertTrue(RateLimiter::tooManyAttempts(
            $this->ipRateLimitKey(),
            User::MAX_LOGIN_ATTEMPTS,
        ));

        $this->login($email, 'senha-incorreta')
            ->assertHasNoFormErrors();

        $this->assertGuest();
    }

    /**
     * Verifica que falhas existentes e inexistentes usam a mesma resposta pública.
     *
     * @return void
     */
    public function test_unknown_identity_does_not_change_public_failure_message(): void {
        $user    = $this->user();
        $message = __('filament-panels::auth/pages/login.messages.failed');

        $this->login($user->email, 'senha-incorreta')
            ->assertHasFormErrors(['email' => $message]);
        $this->login('inexistente@example.test', 'senha-incorreta')
            ->assertHasFormErrors(['email' => $message]);
    }

    /**
     * Verifica que o evento de segurança não inclui a senha fornecida.
     *
     * @return void
     */
    public function test_security_log_does_not_store_password(): void {
        $user     = $this->user();
        $password = 'segredo-que-nao-deve-ser-registrado';
        $events   = [];

        Log::listen(function (MessageLogged $event) use (&$events): void {
            $events[] = $event;
        });

        $this->login($user->email, $password)
            ->assertHasFormErrors(['email']);

        $securityEvents = collect($events)
            ->filter(fn (MessageLogged $event): bool => $event->message === 'security.authentication.failed');

        $this->assertCount(1, $securityEvents);

        $context = $securityEvents->first()->context;
        $this->assertStringNotContainsString($password, json_encode($context, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('password', $context);
        $this->assertArrayNotHasKey('credentials', $context);
    }

    /**
     * Executa uma tentativa de login pela página real do painel.
     *
     * @param string $email
     * @param string $password
     *
     * @return Testable
     */
    private function login(string $email, string $password): Testable {
        return Livewire::test(Login::class)
            ->set('data.email', $email)
            ->set('data.password', $password)
            ->call('authenticate');
    }

    /**
     * Cria um usuário ativo com credenciais conhecidas.
     *
     * @param array<string, mixed> $attributes
     *
     * @return User
     */
    private function user(array $attributes = []): User {
        return User::factory()->create(array_merge([
            'email'                 => 'usuario@example.test',
            'active'                => true,
            'force_password_change' => false,
            'login_attempts'        => 0,
            'locked_at'             => null,
            'password'              => Hash::make('Senha-correta-2026'),
        ], $attributes));
    }

    /**
     * Retorna a chave do limite global da identidade.
     *
     * @param string $email
     *
     * @return string
     */
    private function identityRateLimitKey(string $email): string {
        return 'login:identity:'.hash('sha256', strtolower(trim($email)));
    }

    /**
     * Retorna a chave do limite por IP mantido pelo componente de login.
     *
     * @return string
     */
    private function ipRateLimitKey(): string {
        return 'livewire-rate-limiter:'.sha1(Login::class.'|authenticate|127.0.0.1');
    }

}
