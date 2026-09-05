<?php

namespace App\Services\Teachers;

use App\Enums\TeacherAccessAction;
use App\Enums\TeacherOnboardingState;
use App\Enums\TeacherStatus;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\Auth\FirstAccessInvitationService;
use App\Support\PermissionAccess;
use App\Support\TeacherAssignmentCurriculum;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TeacherOnboardingService {

    /**
     * Executa o cadastro docente, o acesso e as alocações iniciais em uma operação idempotente.
     *
     * @param array<string, mixed> $data
     *
     * @return TeacherOnboardingResult
     */
    public function create(array $data): TeacherOnboardingResult {
        $token        = (string) ($data['onboarding_token'] ?? Str::uuid());
        $accessAction = TeacherAccessAction::tryFrom(
            (string) ($data['access_action'] ?? TeacherAccessAction::NONE->value)
        );

        if (!$accessAction) {
            throw ValidationException::withMessages([
                'access_action' => 'Selecione uma ação válida para o acesso do professor.',
            ]);
        }

        if ($result = $this->findResultByToken($token)) {
            return $result;
        }

        try {
            $result = DB::transaction(function () use ($data, $token, $accessAction): TeacherOnboardingResult {
                if ($result = $this->findResultByToken($token)) {
                    return $result;
                }

                $matches = $this->findTeachersByIdentity($data, lockForUpdate: true);
                if ($matches->isNotEmpty()) {
                    throw $this->duplicateTeacherValidationException($matches, 'name');
                }

                $teacher = Teacher::create(array_merge(
                    $this->teacherAttributes($data),
                    ['onboarding_token' => $token],
                ));
                $assignmentsCreated = 0;

                foreach ($data['assignments'] ?? [] as $assignmentData) {
                    $assignment = $this->createAssignmentInternal($teacher, $assignmentData);
                    $assignmentsCreated += (int) $assignment->wasRecentlyCreated;
                }

                $userCreated = false;
                if ($accessAction->createsUser()) {
                    $userCreated = $this->createTeacherUser(
                        $teacher,
                        $data['access_email'] ?? $teacher->email,
                        array_key_exists('access_email', $data) ? 'access_email' : 'email',
                    );
                }

                if ($accessAction->sendsInvitation()) {
                    $this->ensureTeacherCanReceiveInvitation($teacher);
                }

                return new TeacherOnboardingResult(
                    $teacher,
                    $userCreated,
                    $assignmentsCreated,
                );
            }, attempts: 3);

            if ($accessAction->sendsInvitation() && !$result->replayed) {
                $url = app(FirstAccessInvitationService::class)
                    ->issue($result->teacher->user()->firstOrFail());

                return new TeacherOnboardingResult(
                    $result->teacher,
                    $result->userCreated,
                    $result->assignmentsCreated,
                    $result->replayed,
                    $url,
                    invitationSent: true,
                );
            }

            return $result;
        } catch (QueryException $exception) {
            if ($this->isTokenCollision($exception)
                && ($result = $this->findResultByToken($token))) {
                return $result;
            }

            if ($this->isUniqueConstraintViolation($exception)) {
                throw $this->uniqueConstraintValidationException($exception);
            }

            throw $exception;
        }
    }

    /**
     * Cria o usuário docente sem enviar convite automaticamente.
     *
     * @param Teacher $teacher
     * @param string|null $email
     *
     * @return User
     */
    public function createAccess(Teacher $teacher, ?string $email = null): User {
        return DB::transaction(function () use ($teacher, $email): User {
            $locked = Teacher::query()->lockForUpdate()->findOrFail($teacher->id);
            $this->createTeacherUser($locked, $email ?? $locked->email, 'email');

            return $locked->user()->firstOrFail();
        }, attempts: 3);
    }

    /**
     * Envia o convite ao professor somente quando sua situação funcional permite acesso.
     *
     * @param Teacher $teacher
     *
     * @return string
     */
    public function inviteAccess(Teacher $teacher): string {
        $user = $this->ensureTeacherCanReceiveInvitation($teacher, 'email');

        return app(FirstAccessInvitationService::class)->issue($user);
    }

    /**
     * Cria uma alocação docente aplicando a regra única de turma e disciplina.
     *
     * @param Teacher $teacher
     * @param array<string, mixed> $data
     *
     * @return TeacherAssignment
     */
    public function createAssignment(Teacher $teacher, array $data): TeacherAssignment {
        return DB::transaction(function () use ($teacher, $data): TeacherAssignment {
            $locked = Teacher::query()->lockForUpdate()->findOrFail($teacher->id);

            return $this->createAssignmentInternal($locked, $data);
        }, attempts: 3);
    }

    /**
     * Atualiza uma alocação sem permitir conflito de professor na turma e disciplina.
     *
     * @param TeacherAssignment $assignment
     * @param array<string, mixed> $data
     * @param Teacher|null $teacher
     *
     * @return TeacherAssignment
     */
    public function updateAssignment(
        TeacherAssignment $assignment,
        array $data,
        ?Teacher $teacher = null,
    ): TeacherAssignment {
        return DB::transaction(function () use ($assignment, $data, $teacher): TeacherAssignment {
            $locked    = TeacherAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $teacherId = (int) ($teacher?->id ?? $data['teacher_id'] ?? $locked->teacher_id);
            $classId   = (int) ($data['class_id'] ?? $locked->class_id);
            $subjectId = (int) ($data['subject_id'] ?? $locked->subject_id);

            $this->validateAssignmentReferences($teacherId, $classId, $subjectId);
            $curriculum = $this->validateAssignmentCurriculum($classId, $subjectId, $data);
            $conflict = TeacherAssignment::query()
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->whereKeyNot($locked->id)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'subject_id' => 'Esta disciplina já possui um professor vinculado nesta turma.',
                ]);
            }

            $locked->update([
                'teacher_id'                         => $teacherId,
                'class_id'                           => $classId,
                'subject_id'                         => $subjectId,
                'curriculum_exception'               => $curriculum['exception'],
                'curriculum_exception_justification' => $curriculum['justification'],
            ]);

            return $locked->refresh();
        }, attempts: 3);
    }

    /**
     * Localiza professores existentes por CPF, matrícula funcional ou e-mail.
     *
     * @param array<string, mixed> $data
     * @param bool $lockForUpdate
     *
     * @return Collection<int, Teacher>
     */
    public function findTeachersByIdentity(array $data, bool $lockForUpdate = false): Collection {
        $cpf            = $this->digits($data['cpf'] ?? null);
        $employeeNumber = Str::upper(trim((string) ($data['employee_number'] ?? '')));
        $email          = Str::lower(trim((string) ($data['email'] ?? '')));
        $matches        = collect();
        $queries        = [
            [$cpf, "REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?"],
            [$employeeNumber, 'UPPER(employee_number) = ?'],
            [$email, 'LOWER(TRIM(email)) = ?'],
        ];

        foreach ($queries as [$value, $where]) {
            if (!$value) {
                continue;
            }

            $query = Teacher::withTrashed()->whereRaw($where, [$value]);
            if ($lockForUpdate) {
                $query->lockForUpdate();
            }

            $matches = $matches->merge($query->get());
        }

        return $matches->unique('id')->values();
    }

    /**
     * Retorna o estado administrativo do onboarding docente.
     *
     * @param Teacher $teacher
     *
     * @return TeacherOnboardingState
     */
    public function onboardingState(Teacher $teacher): TeacherOnboardingState {
        $user = $teacher->user_id
            ? User::withTrashed()->with('roles')->find($teacher->user_id)
            : null;

        if (!$user) {
            return TeacherOnboardingState::WITHOUT_USER;
        }

        if ($user->trashed()
            || !$user->active
            || $user->is_locked
            || !$user->hasRole('teacher')
            || !$teacher->canAccessOperationally()) {
            return TeacherOnboardingState::ACCESS_BLOCKED;
        }

        if (!$teacher->assignments()->exists()) {
            return TeacherOnboardingState::WITHOUT_ASSIGNMENT;
        }

        return TeacherOnboardingState::READY_FOR_ACCESS;
    }

    /**
     * Recupera o resultado de uma submissão docente já processada.
     *
     * @param string $token
     *
     * @return TeacherOnboardingResult|null
     */
    private function findResultByToken(string $token): ?TeacherOnboardingResult {
        $teacher = Teacher::query()->where('onboarding_token', $token)->first();

        return $teacher
            ? new TeacherOnboardingResult($teacher, replayed: true)
            : null;
    }

    /**
     * Cria uma alocação após validar professor, turma, disciplina e conflito existente.
     *
     * @param Teacher $teacher
     * @param array<string, mixed> $data
     *
     * @return TeacherAssignment
     */
    private function createAssignmentInternal(Teacher $teacher, array $data): TeacherAssignment {
        $classId   = (int) ($data['class_id'] ?? 0);
        $subjectId = (int) ($data['subject_id'] ?? 0);

        $this->validateAssignmentReferences((int) $teacher->id, $classId, $subjectId);
        $curriculum = $this->validateAssignmentCurriculum($classId, $subjectId, $data);
        $existing = TeacherAssignment::query()
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->lockForUpdate()
            ->first();

        if ($existing && (int) $existing->teacher_id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'subject_id' => 'Esta disciplina já possui um professor vinculado nesta turma.',
            ]);
        }

        if ($existing) {
            return $existing;
        }

        return TeacherAssignment::create([
            'teacher_id'                         => $teacher->id,
            'class_id'                           => $classId,
            'subject_id'                         => $subjectId,
            'curriculum_exception'               => $curriculum['exception'],
            'curriculum_exception_justification' => $curriculum['justification'],
        ]);
    }

    /**
     * Valida se a disciplina pertence à matriz ou se a exceção foi autorizada.
     *
     * @param int $classId
     * @param int $subjectId
     * @param array<string, mixed> $data
     *
     * @return array{exception: bool, justification: string|null}
     */
    private function validateAssignmentCurriculum(int $classId, int $subjectId, array $data): array {
        if (TeacherAssignmentCurriculum::isCurricular($classId, $subjectId)) {
            return [
                'exception'     => false,
                'justification' => null,
            ];
        }

        $wantsException = filter_var($data['curriculum_exception'] ?? false, FILTER_VALIDATE_BOOL);
        $justification  = trim((string) ($data['curriculum_exception_justification'] ?? ''));

        if (!$wantsException) {
            throw ValidationException::withMessages([
                'subject_id' => 'A disciplina selecionada não pertence à matriz curricular da série da turma.',
            ]);
        }

        if (!PermissionAccess::can('admin.teachers.assignments.curriculum_exception')) {
            throw ValidationException::withMessages([
                'curriculum_exception' => 'Você não possui permissão para autorizar exceção curricular.',
            ]);
        }

        if ($justification === '') {
            throw ValidationException::withMessages([
                'curriculum_exception_justification' => 'Informe a justificativa da exceção curricular.',
            ]);
        }

        return [
            'exception'     => true,
            'justification' => $justification,
        ];
    }

    /**
     * Valida a existência dos registros usados em uma alocação docente.
     *
     * @param int $teacherId
     * @param int $classId
     * @param int $subjectId
     *
     * @return void
     */
    private function validateAssignmentReferences(int $teacherId, int $classId, int $subjectId): void {
        if (!Teacher::query()->whereKey($teacherId)->exists()) {
            throw ValidationException::withMessages([
                'teacher_id' => 'O professor selecionado não está disponível.',
            ]);
        }

        if (!SchoolClass::query()->whereKey($classId)->exists()) {
            throw ValidationException::withMessages([
                'class_id' => 'A turma selecionada não está disponível.',
            ]);
        }

        if (!Subject::query()->whereKey($subjectId)->exists()) {
            throw ValidationException::withMessages([
                'subject_id' => 'A disciplina selecionada não está disponível.',
            ]);
        }
    }

    /**
     * Cria e vincula o usuário com o papel e as permissões docentes.
     *
     * @param Teacher $teacher
     * @param string|null $email
     * @param string $field
     *
     * @return bool
     */
    private function createTeacherUser(
        Teacher $teacher,
        ?string $email,
        string $field,
    ): bool {
        if ($teacher->user_id) {
            throw ValidationException::withMessages([
                $field => 'O professor já possui um usuário de acesso vinculado.',
            ]);
        }

        $email = Str::lower(trim((string) $email));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                $field => 'Informe um e-mail válido para criar o acesso do professor.',
            ]);
        }

        if (User::withTrashed()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            throw ValidationException::withMessages([
                $field => 'Este e-mail já está vinculado a outro usuário.',
            ]);
        }

        if ($this->userCpfExists($teacher->cpf)) {
            throw ValidationException::withMessages([
                $field => 'Este CPF já está vinculado a outro usuário.',
            ]);
        }

        $role = Role::query()->where('name', 'teacher')->first();
        if (!$role) {
            throw new RuntimeException('O papel de professor não está configurado.');
        }

        $user = User::create([
            'name'                  => $teacher->name,
            'email'                 => $email,
            'password'              => Hash::make(Str::random(64)),
            'active'                => true,
            'force_password_change' => true,
            'cpf'                   => $teacher->cpf,
            'birth_date'            => $teacher->birth_date,
            'gender'                => $teacher->gender,
            'address'               => $teacher->address_street,
            'district'              => $teacher->address_district,
            'city'                  => $teacher->address_city,
            'state'                 => $teacher->address_state,
            'postal_code'           => $teacher->address_zip,
            'phone'                 => $teacher->phone,
            'cellphone'             => $teacher->mobile,
        ]);

        $user->syncRoles([$role]);
        $teacher->update(['user_id' => $user->id]);

        return true;
    }

    /**
     * Garante que o professor e seu usuário podem receber convite de acesso.
     *
     * @param Teacher $teacher
     * @param string $field
     *
     * @return User
     */
    private function ensureTeacherCanReceiveInvitation(
        Teacher $teacher,
        string $field = 'access_action',
    ): User {
        $teacher = $teacher->fresh();
        $user    = $teacher?->user()->with('roles')->first();

        if (!$teacher || !$user || !$user->hasRole('teacher')) {
            throw ValidationException::withMessages([
                $field => 'O professor não possui um usuário de acesso consistente.',
            ]);
        }

        if (!$teacher->canAccessOperationally()) {
            throw ValidationException::withMessages([
                $field => 'Somente professor ativo e não desligado pode receber convite de acesso.',
            ]);
        }

        if (!$user->active || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                $field => 'Ative o usuário e informe um e-mail válido antes de enviar o convite.',
            ]);
        }

        return $user;
    }

    /**
     * Converte os dados do Wizard em atributos persistidos do professor.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function teacherAttributes(array $data): array {
        if (!filled($data['name'] ?? null)) {
            throw ValidationException::withMessages(['name' => 'Informe o nome do professor.']);
        }

        if (!filled($data['employee_number'] ?? null)) {
            throw ValidationException::withMessages([
                'employee_number' => 'Informe a matrícula funcional do professor.',
            ]);
        }

        $fields = [
            'employee_number', 'name', 'qualification', 'academic_title', 'hire_date',
            'admission_date', 'termination_date', 'regime', 'weekly_workload', 'max_classes',
            'email', 'phone', 'mobile', 'cpf', 'birth_date', 'gender', 'address_street',
            'address_number', 'address_district', 'address_city', 'address_state',
            'address_zip', 'lattes_url', 'bio', 'status',
        ];
        $attributes = collect($fields)
            ->mapWithKeys(fn (string $field): array => [$field => $data[$field] ?? null])
            ->filter(fn ($value): bool => $value !== null)
            ->all();
        $attributes['status'] = $attributes['status'] ?? TeacherStatus::ACTIVE->value;

        return $attributes;
    }

    /**
     * Monta a validação usada quando um professor já possui a identidade informada.
     *
     * @param Collection<int, Teacher> $matches
     * @param string $field
     *
     * @return ValidationException
     */
    private function duplicateTeacherValidationException(Collection $matches, string $field): ValidationException {
        if ($matches->count() > 1) {
            return ValidationException::withMessages([
                $field => 'CPF, matrícula funcional e e-mail pertencem a professores diferentes.',
            ]);
        }

        $teacher = $matches->first();

        return ValidationException::withMessages([
            $field => "Professor já cadastrado: {$teacher->name} ({$teacher->employee_number}).",
        ]);
    }

    /**
     * Mantém somente os dígitos de um identificador.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function digits(mixed $value): string {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /**
     * Verifica se o CPF já pertence a algum usuário, inclusive excluído.
     *
     * @param mixed $cpf
     *
     * @return bool
     */
    private function userCpfExists(mixed $cpf): bool {
        $cpf = $this->digits($cpf);

        return $cpf !== '' && User::withTrashed()
            ->whereRaw("REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', '') = ?", [$cpf])
            ->exists();
    }

    /**
     * Identifica colisão da chave idempotente do onboarding.
     *
     * @param QueryException $exception
     *
     * @return bool
     */
    private function isTokenCollision(QueryException $exception): bool {
        return $this->isUniqueConstraintViolation($exception)
            && str_contains(strtolower($exception->getMessage()), 'onboarding_token');
    }

    /**
     * Identifica violações de unicidade nos bancos suportados.
     *
     * @param QueryException $exception
     *
     * @return bool
     */
    private function isUniqueConstraintViolation(QueryException $exception): bool {
        $sqlState   = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message    = strtolower($exception->getMessage());

        return $sqlState === '23505'
            || $driverCode === 1062
            || ($driverCode === 19 && str_contains($message, 'unique constraint failed'));
    }

    /**
     * Converte colisões de unicidade em mensagens dos campos do Wizard.
     *
     * @param QueryException $exception
     *
     * @return ValidationException
     */
    private function uniqueConstraintValidationException(QueryException $exception): ValidationException {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'teacher_assignments_class_id_subject_id_unique')
            || (str_contains($message, 'teacher_assignments.class_id')
                && str_contains($message, 'teacher_assignments.subject_id'))) {
            return ValidationException::withMessages([
                'subject_id' => 'Esta disciplina já possui um professor vinculado nesta turma.',
            ]);
        }

        if (str_contains($message, 'teachers_cpf_unique') || str_contains($message, 'teachers.cpf')) {
            return ValidationException::withMessages(['cpf' => 'Já existe professor com este CPF.']);
        }

        if (str_contains($message, 'employee_number')) {
            return ValidationException::withMessages([
                'employee_number' => 'Já existe professor com esta matrícula funcional.',
            ]);
        }

        if (str_contains($message, 'users_email_unique') || str_contains($message, 'users.email')) {
            return ValidationException::withMessages([
                'access_email' => 'Este e-mail já está vinculado a outro usuário.',
            ]);
        }

        return ValidationException::withMessages([
            'name' => 'Não foi possível concluir o onboarding porque os dados já foram cadastrados.',
        ]);
    }
}
