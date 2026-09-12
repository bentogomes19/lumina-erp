<?php

namespace Tests\Unit;

use App\Http\Middleware\RedirectUserByRole;
use App\Models\User;
use App\Support\AdministrativeDashboardAccess;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdministrativeDashboardAccessTest extends TestCase {

    /**
     * Encerra os dublês usados nos cenários de acesso.
     *
     * @return void
     */
    protected function tearDown(): void {
        auth()->forgetGuards();
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Garante que cada papel conhecido receba um destino válido.
     *
     * @param array<int, string> $roles
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('roleDestinations')]
    public function test_destination_for_known_and_unknown_roles(array $roles, string $expected): void {
        $this->assertSame($expected, AdministrativeDashboardAccess::destinationFor($this->userWithRoles($roles)));
    }

    /**
     * Garante que a raiz do painel usa a mesma matriz de destinos.
     *
     * @param array<int, string> $roles
     * @param string $expected
     *
     * @return void
     */
    #[DataProvider('roleDestinations')]
    public function test_middleware_redirects_lumina_root_by_role(array $roles, string $expected): void {
        auth()->setUser($this->userWithRoles($roles));

        $request  = Request::create('/lumina', 'GET');
        $response = (new RedirectUserByRole())->handle(
            $request,
            fn (): Response => new Response('ok'),
        );

        $this->assertSame($expected, parse_url($response->getTargetUrl(), PHP_URL_PATH));
    }

    /**
     * Garante que apenas perfis administrativos acessam o dashboard administrativo.
     *
     * @return void
     */
    public function test_administrative_role_check_accepts_only_administrative_profiles(): void {
        $this->assertTrue(AdministrativeDashboardAccess::hasAdministrativeRole($this->userWithRoles(['financeiro'])));
        $this->assertTrue(AdministrativeDashboardAccess::hasAdministrativeRole($this->userWithRoles(['secretaria'])));
        $this->assertFalse(AdministrativeDashboardAccess::hasAdministrativeRole($this->userWithRoles(['student'])));
        $this->assertFalse(AdministrativeDashboardAccess::hasAdministrativeRole($this->userWithRoles(['teacher'])));
        $this->assertFalse(AdministrativeDashboardAccess::hasAdministrativeRole($this->userWithRoles([])));
    }

    /**
     * Retorna os destinos esperados para cada combinação de perfis.
     *
     * @return array<string, array{array<int, string>, string}>
     */
    public static function roleDestinations(): array {
        return [
            'aluno'                         => [['student'], '/aluno/dashboard-student'],
            'professor'                     => [['teacher'], '/professor/dashboard-teacher'],
            'ti'                            => [['ti'], '/lumina/dashboard-admin'],
            'administrador'                 => [['admin'], '/lumina/dashboard-admin'],
            'secretaria'                    => [['secretaria'], '/lumina/dashboard-admin'],
            'financeiro'                    => [['financeiro'], '/lumina/dashboard-admin'],
            'aluno com perfil administrativo' => [['admin', 'student'], '/aluno/dashboard-student'],
            'professor com perfil administrativo' => [['financeiro', 'teacher'], '/professor/dashboard-teacher'],
            'sem papel'                     => [[], '/lumina/acesso-pendente'],
            'papel desconhecido'            => [['externo'], '/lumina/acesso-pendente'],
        ];
    }

    /**
     * Cria um usuário em memória com a resposta esperada para hasRole.
     *
     * @param array<int, string> $roles
     *
     * @return User
     */
    private function userWithRoles(array $roles): User {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')
            ->andReturnUsing(fn (string $role): bool => in_array($role, $roles, true));

        return $user;
    }
}
