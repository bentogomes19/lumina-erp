<?php

namespace Tests\Feature\Auth;

use App\Filament\Pages\Auth\RequestPasswordReset;
use App\Http\Middleware\EnsurePasswordWasChanged;
use App\Models\Role;
use App\Models\User;
use App\Notifications\FirstAccessInvitation;
use App\Notifications\PasswordReset as PasswordResetNotification;
use App\Services\Auth\FirstAccessInvitationService;
use Filament\Auth\Pages\PasswordReset\ResetPassword as ResetPasswordPage;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class FirstAccessTest extends TestCase {

    use RefreshDatabase;

    /**
     * Prepara o painel usado para gerar as URLs de autenticação nos testes.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('lumina'));
    }

    /**
     * Verifica que aluno e professor definem a senha pelos respectivos convites.
     *
     * @return void
     */
    public function test_student_and_teacher_can_complete_first_access(): void {
        Notification::fake();

        foreach (['student', 'teacher'] as $roleName) {
            Role::create(['name' => $roleName, 'guard_name' => 'web']);

            $user = $this->user([
                'email' => "{$roleName}@example.test",
            ]);
            $user->syncRoles([$roleName]);

            $url    = app(FirstAccessInvitationService::class)->issue($user);
            $status = $this->resetPasswordFromUrl($user, $url, "Senha-{$roleName}-2026");

            $this->assertSame(Password::PASSWORD_RESET, $status);
            $this->assertFalse($user->fresh()->force_password_change);
            $this->assertTrue(Hash::check("Senha-{$roleName}-2026", $user->fresh()->password));
            Notification::assertSentTo($user, FirstAccessInvitation::class);
        }
    }

    /**
     * Verifica que o convite expira e não pode ser reutilizado após a troca.
     *
     * @return void
     */
    public function test_invitation_expires_and_can_only_be_used_once(): void {
        Notification::fake();
        $service = app(FirstAccessInvitationService::class);
        $user    = $this->user();
        $url     = $service->issue($user);
        $token   = $this->tokenFromUrl($url);

        $this->travel($service->expiresInMinutes() + 1)->minutes();

        $this->assertFalse(Password::broker()->tokenExists($user, $token));
        $this->assertSame(
            Password::INVALID_TOKEN,
            $this->resetPasswordFromUrl($user, $url, 'Senha-expirada-2026'),
        );

        $this->travelBack();
        $newUrl = $service->issue($user);

        $this->assertSame(
            Password::PASSWORD_RESET,
            $this->resetPasswordFromUrl($user, $newUrl, 'Senha-valida-2026'),
        );
        $this->assertSame(
            Password::INVALID_TOKEN,
            $this->resetPasswordFromUrl($user, $newUrl, 'Outra-senha-2026'),
        );
    }

    /**
     * Verifica que reenviar revoga o link anterior e que a revogação invalida o atual.
     *
     * @return void
     */
    public function test_invitation_can_be_resent_and_revoked(): void {
        Notification::fake();
        $service = app(FirstAccessInvitationService::class);
        $user    = $this->user();
        $oldUrl  = $service->issue($user);
        $newUrl  = $service->issue($user);

        $this->assertFalse(Password::broker()->tokenExists($user, $this->tokenFromUrl($oldUrl)));
        $this->assertTrue(Password::broker()->tokenExists($user, $this->tokenFromUrl($newUrl)));

        $service->revoke($user);

        $this->assertFalse($service->hasActiveToken($user));
        $this->assertFalse(Password::broker()->tokenExists($user, $this->tokenFromUrl($newUrl)));
        $this->assertTrue($user->fresh()->force_password_change);
    }

    /**
     * Verifica que a tela pública solicita recuperação para um usuário ativo.
     *
     * @return void
     */
    public function test_active_user_can_request_password_recovery(): void {
        Notification::fake();
        $user = $this->user(['force_password_change' => false]);

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', $user->email)
            ->call('request');

        Notification::assertSentTo($user, PasswordResetNotification::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    /**
     * Verifica que a tela do Filament conclui a troca e libera a conta pendente.
     *
     * @return void
     */
    public function test_filament_reset_page_completes_first_access(): void {
        Notification::fake();
        $user = $this->user();
        $url  = app(FirstAccessInvitationService::class)->issue($user);

        $this->get($url)->assertOk();

        Livewire::test(ResetPasswordPage::class, [
            'email' => $user->email,
            'token' => $this->tokenFromUrl($url),
        ])
            ->set('password', 'SenhaPeloFilament2026!')
            ->set('passwordConfirmation', 'SenhaPeloFilament2026!')
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        $this->assertFalse($user->fresh()->force_password_change);
        $this->assertTrue(Hash::check('SenhaPeloFilament2026!', $user->fresh()->password));
    }

    /**
     * Verifica que uma conta inativa não recebe convite nem pode redefinir a senha.
     *
     * @return void
     */
    public function test_inactive_user_cannot_receive_invitation_or_reset_password(): void {
        Notification::fake();
        $service = app(FirstAccessInvitationService::class);
        $user    = $this->user(['active' => false]);

        try {
            $service->issue($user);
            $this->fail('O convite de uma conta inativa deveria ser rejeitado.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Não é possível enviar convite para um usuário inativo.',
                $exception->errors()['email'][0],
            );
        }

        $token = Password::broker()->createToken($user);
        $status = Password::broker()->reset([
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => 'Senha-invalida-2026',
            'password_confirmation' => 'Senha-invalida-2026',
        ], function (User $candidate): void {
            if (!$candidate->canAccessPanel(Filament::getPanel('lumina'))) {
                return;
            }

            $candidate->forceFill(['password' => 'Senha-invalida-2026'])->save();
        });

        $this->assertSame(Password::PASSWORD_RESET, $status);
        $this->assertFalse(Hash::check('Senha-invalida-2026', $user->fresh()->password));
    }

    /**
     * Verifica que a troca obrigatória encerra a sessão e leva à recuperação de senha.
     *
     * @return void
     */
    public function test_forced_password_change_blocks_erp_navigation(): void {
        $this->withoutExceptionHandling();

        Route::middleware(['web', EnsurePasswordWasChanged::class])
            ->get('/teste-troca-obrigatoria', fn () => 'conteúdo protegido');

        $user = $this->user();
        $url  = Filament::getPanel('lumina')->getRequestPasswordResetUrl(['email' => $user->email]);

        $this->actingAs($user)
            ->get('/teste-troca-obrigatoria')
            ->assertRedirect($url)
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Cria um usuário válido para os cenários de primeiro acesso.
     *
     * @param array<string, mixed> $attributes
     *
     * @return User
     */
    private function user(array $attributes = []): User {
        return User::factory()->create(array_merge([
            'email'                 => 'primeiro.acesso@example.test',
            'active'                => true,
            'force_password_change' => true,
            'password'              => Hash::make('Senha-antiga-2026'),
        ], $attributes));
    }

    /**
     * Redefine a senha usando o token presente em uma URL de convite.
     *
     * @param User $user
     * @param string $url
     * @param string $password
     *
     * @return string
     */
    private function resetPasswordFromUrl(User $user, string $url, string $password): string {
        return Password::broker()->reset([
            'email'                 => $user->email,
            'token'                 => $this->tokenFromUrl($url),
            'password'              => $password,
            'password_confirmation' => $password,
        ], function (User $candidate, string $newPassword): void {
            $candidate->forceFill([
                'password'       => Hash::make($newPassword),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($candidate));
        });
    }

    /**
     * Extrai o token descartável da URL assinada do Filament.
     *
     * @param string $url
     *
     * @return string
     */
    private function tokenFromUrl(string $url): string {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return (string) $query['token'];
    }
}
