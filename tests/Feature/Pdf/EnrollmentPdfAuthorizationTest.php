<?php

namespace Tests\Feature\Pdf;

use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EnrollmentPdfAuthorizationTest extends TestCase {

    use RefreshDatabase;

    /**
     * Verifica se cada perfil autorizado pode emitir todos os documentos de matrícula.
     *
     * @param string $role
     * @param string $route
     *
     * @return void
     */
    #[DataProvider('authorizedRoleAndEndpointProvider')]
    public function test_authorized_roles_can_emit_each_enrollment_document(string $role, string $route): void {
        $user       = $this->userWithRole($role);
        $enrollment = $this->enrollmentFor($role === 'student' ? $user : null);

        $this->mockPdfGeneration();

        $this->actingAs($user)
            ->get(route($route, $enrollment))
            ->assertOk();
    }

    /**
     * Verifica se um aluno não pode emitir documentos da matrícula de outro aluno.
     *
     * @param string $route
     *
     * @return void
     */
    #[DataProvider('endpointProvider')]
    public function test_student_cannot_emit_another_students_document(string $route): void {
        $studentUser       = $this->userWithRole('student');
        $anotherEnrollment = $this->enrollmentFor();

        $this->actingAs($studentUser)
            ->get(route($route, $anotherEnrollment))
            ->assertForbidden();
    }

    /**
     * Verifica se perfis sem regra explícita não podem emitir documentos de matrícula.
     *
     * @param string $role
     * @param string $route
     *
     * @return void
     */
    #[DataProvider('unauthorizedRoleAndEndpointProvider')]
    public function test_roles_without_a_formal_rule_are_denied_by_default_for_each_document(
        string $role,
        string $route,
    ): void {
        $user       = $this->userWithRole($role);
        $enrollment = $this->enrollmentFor();

        $this->actingAs($user)
            ->get(route($route, $enrollment))
            ->assertForbidden();
    }

    /**
     * Verifica se um usuário inativo não pode emitir documentos de matrícula.
     *
     * @return void
     */
    public function test_inactive_user_cannot_emit_enrollment_document(): void {
        $user       = $this->userWithRole('admin', active: false);
        $enrollment = $this->enrollmentFor();

        $this->actingAs($user)
            ->get(route('pdf.enrollment.comprovante', $enrollment))
            ->assertForbidden();
    }

    /**
     * Verifica se um usuário bloqueado não pode emitir documentos de matrícula.
     *
     * @return void
     */
    public function test_locked_user_cannot_emit_enrollment_document(): void {
        $user = $this->userWithRole('admin');
        $user->update(['locked_at' => now()]);
        $enrollment = $this->enrollmentFor();

        $this->actingAs($user)
            ->get(route('pdf.enrollment.comprovante', $enrollment))
            ->assertForbidden();
    }

    /**
     * Verifica se um visitante é redirecionado para a página de autenticação.
     *
     * @return void
     */
    public function test_unauthenticated_user_is_redirected_to_login(): void {
        $enrollment = $this->enrollmentFor();

        $this->get(route('pdf.enrollment.comprovante', $enrollment))
            ->assertRedirect(route('login'));
    }

    /**
     * Verifica se uma matrícula inexistente produz uma resposta de recurso não encontrado.
     *
     * @return void
     */
    public function test_nonexistent_enrollment_returns_not_found(): void {
        $user = $this->userWithRole('admin');

        $this->actingAs($user)
            ->get(route('pdf.enrollment.comprovante', ['enrollment' => 999999]))
            ->assertNotFound();
    }

    /**
     * Fornece os perfis autorizados e os endpoints usados nos testes.
     *
     * @return array<string, array{string, string}>
     */
    public static function authorizedRoleAndEndpointProvider(): array {
        $cases = [];

        foreach (['admin', 'ti', 'secretaria', 'financeiro', 'student'] as $role) {
            foreach (self::endpoints() as $endpoint => $route) {
                $cases["{$role} - {$endpoint}"] = [$role, $route];
            }
        }

        return $cases;
    }

    /**
     * Fornece os endpoints de documentos usados nos testes.
     *
     * @return array<string, array{string}>
     */
    public static function endpointProvider(): array {
        return array_map(
            static fn (string $route): array => [$route],
            self::endpoints(),
        );
    }

    /**
     * Fornece os perfis não autorizados e os endpoints usados nos testes.
     *
     * @return array<string, array{string, string}>
     */
    public static function unauthorizedRoleAndEndpointProvider(): array {
        $cases = [];

        foreach (['teacher', 'responsavel'] as $role) {
            foreach (self::endpoints() as $endpoint => $route) {
                $cases["{$role} - {$endpoint}"] = [$role, $route];
            }
        }

        return $cases;
    }

    /**
     * Retorna os endpoints de documentos cobertos pelos testes.
     *
     * @return array<string, string>
     */
    private static function endpoints(): array {
        return [
            'comprovante'           => 'pdf.enrollment.comprovante',
            'transferencia interna' => 'pdf.enrollment.transferencia-interna',
            'transferencia externa' => 'pdf.enrollment.transferencia-externa',
            'trancamento'           => 'pdf.enrollment.trancamento',
            'cancelamento'          => 'pdf.enrollment.cancelamento',
        ];
    }

    /**
     * Cria um usuário com o perfil usado pelo cenário de teste.
     *
     * @param string $role
     * @param bool $active
     *
     * @return User
     */
    private function userWithRole(string $role, bool $active = true): User {
        $user = User::factory()->create(['active' => $active]);

        Role::findOrCreate($role, 'web');
        $user->assignRole($role);

        return $user;
    }

    /**
     * Cria uma matrícula usada pelos cenários de teste.
     *
     * @param User|null $studentUser
     *
     * @return Enrollment
     */
    private function enrollmentFor(?User $studentUser = null): Enrollment {
        $owner      = $studentUser ?? User::factory()->create();
        $student    = Student::factory()->create(['user_id' => $owner->id]);
        $schoolYear = SchoolYear::create([
            'year'      => 2026,
            'starts_at' => '2026-02-02',
            'ends_at'   => '2026-12-18',
            'status'    => 'planejamento',
        ]);
        $gradeLevel = GradeLevel::create([
            'name'          => 'Nível '.Str::uuid(),
            'stage'         => 'fundamental_i',
            'display_order' => 1,
        ]);
        $class = SchoolClass::create([
            'uuid'           => Str::uuid(),
            'name'           => 'Turma '.Str::random(8),
            'grade_level_id' => $gradeLevel->id,
            'school_year_id' => $schoolYear->id,
            'shift'          => 'morning',
            'type'           => 'regular',
            'capacity'       => 30,
            'status'         => 'open',
        ]);

        $enrollmentId = DB::table('enrollments')->insertGetId([
            'student_id'          => $student->id,
            'class_id'            => $class->id,
            'school_year_id'      => $schoolYear->id,
            'registration_number' => '2026'.Str::random(10),
            'enrollment_date'     => '2026-02-02',
            'roll_number'         => 1,
            'status'              => 'Ativa',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return Enrollment::findOrFail($enrollmentId);
    }

    /**
     * Simula a geração do PDF nos cenários de teste.
     *
     * @return void
     */
    private function mockPdfGeneration(): void {
        Pdf::shouldReceive('loadView')->once()->andReturnSelf();
        Pdf::shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        Pdf::shouldReceive('stream')->once()->andReturn(response('PDF'));
    }
}
