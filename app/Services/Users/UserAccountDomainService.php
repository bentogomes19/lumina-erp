<?php

namespace App\Services\Users;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserAccountDomainService {

    /**
     * Papéis acadêmicos gerenciados pelos fluxos próprios de aluno e professor.
     *
     * @var array<int, string>
     */
    private const ACADEMIC_ROLES = ['student', 'teacher'];

    /**
     * Altera o papel de uma conta respeitando os vínculos acadêmicos existentes.
     *
     * @param User $user
     * @param string $roleName
     *
     * @return void
     */
    public function changeRole(User $user, string $roleName): void {
        $this->ensureRoleChangeIsAllowed($user, $roleName);

        $user->syncRoles([$roleName]);
    }

    /**
     * Atualiza nome e e-mail da conta a partir do cadastro do aluno de forma explícita.
     *
     * @param Student $student
     *
     * @return bool
     */
    public function syncAccountIdentityFromStudent(Student $student): bool {
        $user = $student->user;

        if (!$user) {
            return false;
        }

        $payload = ['name' => $student->name];

        if ($student->email) {
            $payload['email'] = $student->email;
        }

        return (bool) $user->update($payload);
    }

    /**
     * Atualiza nome e e-mail da conta a partir do cadastro do professor de forma explícita.
     *
     * @param Teacher $teacher
     *
     * @return bool
     */
    public function syncAccountIdentityFromTeacher(Teacher $teacher): bool {
        $user = $teacher->user;

        if (!$user) {
            return false;
        }

        $payload = ['name' => $teacher->name];

        if ($teacher->email) {
            $payload['email'] = $teacher->email;
        }

        return (bool) $user->update($payload);
    }

    /**
     * Garante que a troca de papel não crie inconsistência com aluno ou professor.
     *
     * @param User $user
     * @param string $roleName
     *
     * @return void
     */
    private function ensureRoleChangeIsAllowed(User $user, string $roleName): void {
        if ($user->student()->exists() && $roleName !== 'student') {
            throw ValidationException::withMessages([
                'role' => 'Usuários vinculados a alunos devem manter o papel de aluno.',
            ]);
        }

        if ($user->teacher()->exists() && $roleName !== 'teacher') {
            throw ValidationException::withMessages([
                'role' => 'Usuários vinculados a professores devem manter o papel de professor.',
            ]);
        }

        if (!$user->student()->exists() && !$user->teacher()->exists() && in_array($roleName, self::ACADEMIC_ROLES, true)) {
            throw ValidationException::withMessages([
                'role' => 'Papéis acadêmicos devem ser concedidos pelos fluxos de alunos ou professores.',
            ]);
        }
    }
}
