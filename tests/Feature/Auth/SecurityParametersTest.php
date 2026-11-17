<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Models\SystemParameter;
use App\Models\User;
use App\Support\SecuritySettings;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Tests\TestCase;

class SecurityParametersTest extends TestCase {

    use RefreshDatabase;

    /**
     * Prepara o painel usado para gerar as URLs de autenticação.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    /**
     * Sem parâmetros cadastrados, valem os padrões anteriores do sistema.
     *
     * @return void
     */
    public function test_defaults_apply_when_parameters_are_missing(): void {
        $this->assertSame(5, SecuritySettings::maxLoginAttempts());
        $this->assertSame(120, SecuritySettings::sessionMinutes());
        $this->assertSame(8, SecuritySettings::passwordMinLength());
        $this->assertSame(0, SecuritySettings::passwordExpirationDays());
    }

    /**
     * Valores fora da faixa segura são limitados em vez de desligar a proteção.
     *
     * @return void
     */
    public function test_out_of_range_values_are_clamped(): void {
        $this->parameter('security.login_attempts', '0');
        $this->parameter('security.password_min_length', '2');
        $this->parameter('security.session_minutes', '999999');

        $this->assertSame(1, SecuritySettings::maxLoginAttempts());
        $this->assertSame(6, SecuritySettings::passwordMinLength());
        $this->assertSame(1440, SecuritySettings::sessionMinutes());
    }

    /**
     * A conta é bloqueada no número de tentativas definido no parâmetro.
     *
     * @return void
     */
    public function test_user_is_locked_after_configured_login_attempts(): void {
        $this->parameter('security.login_attempts', '3');
        $user = $this->user();

        $user->registerFailedLogin();
        $user->registerFailedLogin();
        $this->assertNull($user->fresh()->locked_at);

        $user->registerFailedLogin();
        $this->assertNotNull($user->fresh()->locked_at);
        $this->assertSame(3, $user->fresh()->login_attempts);
    }

    /**
     * O tempo de sessão segue o parâmetro a cada requisição.
     *
     * @return void
     */
    public function test_session_lifetime_follows_parameter(): void {
        $this->parameter('security.session_minutes', '30');

        $this->get('/up')->assertOk();

        $this->assertSame(30, config('session.lifetime'));
    }

    /**
     * A regra padrão de senha exige o tamanho mínimo configurado.
     *
     * @return void
     */
    public function test_password_rule_uses_configured_min_length(): void {
        $this->parameter('security.password_min_length', '12');

        $short = Validator::make(['password' => 'Abc-123456'], ['password' => PasswordRule::default()]);
        $long  = Validator::make(['password' => 'Abc-1234567890'], ['password' => PasswordRule::default()]);

        $this->assertTrue($short->fails());
        $this->assertTrue($long->passes());
    }

    /**
     * Senha mais antiga que o prazo obriga a definir uma nova senha.
     *
     * @return void
     */
    public function test_expired_password_forces_reset(): void {
        $this->withoutExceptionHandling();
        $this->parameter('security.password_expiration_days', '90');

        Route::middleware(['web', EnsurePasswordWasChanged::class])
            ->get('/teste-senha-expirada', fn () => 'conteúdo protegido');

        $user = $this->user(['password_changed_at' => now()->subDays(91)]);
        $url  = Filament::getPanel('lumina')->getRequestPasswordResetUrl(['email' => $user->email]);

        $this->actingAs($user)
            ->get('/teste-senha-expirada')
            ->assertRedirect($url)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Senha dentro do prazo, sem data registrada ou com expiração desligada não expira.
     *
     * @return void
     */
    public function test_password_does_not_expire_when_not_applicable(): void {
        $this->parameter('security.password_expiration_days', '90');

        $this->assertFalse($this->user(['password_changed_at' => now()->subDays(30)])->passwordHasExpired());
        $this->assertFalse($this->user(['password_changed_at' => null])->passwordHasExpired());

        SystemParameter::where('key', 'security.password_expiration_days')->update(['value' => '0']);

        $this->assertFalse($this->user(['password_changed_at' => now()->subYears(5)])->passwordHasExpired());
    }

    /**
     * Redefinir a senha registra a data da troca.
     *
     * @return void
     */
    public function test_password_reset_records_change_date(): void {
        $user = $this->user(['password_changed_at' => null]);

        event(new PasswordReset($user));

        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    /**
     * Cria um usuário ativo que já definiu a própria senha.
     *
     * @param array<string, mixed> $attributes
     *
     * @return User
     */
    private function user(array $attributes = []): User {
        static $sequence = 0;
        $sequence++;

        return User::factory()->create(array_merge([
            'email'                 => "seguranca{$sequence}@example.test",
            'active'                => true,
            'force_password_change' => false,
            'password'              => Hash::make('Senha-correta-2026'),
            'password_changed_at'   => now(),
        ], $attributes));
    }

    /**
     * Cadastra um parâmetro inteiro de segurança.
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    private function parameter(string $key, string $value): void {
        SystemParameter::create([
            'key'           => $key,
            'name'          => $key,
            'category'      => 'Segurança e usuários',
            'type'          => 'integer',
            'value'         => $value,
            'default_value' => $value,
            'description'   => 'Parâmetro de teste.',
            'is_active'     => true,
            'is_system'     => true,
        ]);
    }

}
