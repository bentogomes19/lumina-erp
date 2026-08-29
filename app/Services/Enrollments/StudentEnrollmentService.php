<?php

namespace App\Services\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\EnrollmentLog;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Auth\FirstAccessInvitationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StudentEnrollmentService {

    /**
     * Cria aluno, matrícula, usuário, papel e auditoria em uma única transação.
     *
     * @param array<string, mixed> $data
     *
     * @return StudentEnrollmentResult
     */
    public function create(array $data): StudentEnrollmentResult {
        $submissionToken = (string) ($data['submission_token'] ?? Str::uuid());

        if ($result = $this->findResultBySubmissionToken($submissionToken)) {
            return $result;
        }

        try {
            $result = DB::transaction(function () use ($data, $submissionToken): StudentEnrollmentResult {
                if ($result = $this->findResultBySubmissionToken($submissionToken)) {
                    return $result;
                }

                $schoolClass = $this->findAndLockSchoolClass((int) $data['class_id']);
                $student     = $this->resolveStudent($data);

                $this->ensureStudentIsNotEnrolled($student, $schoolClass);
                $enrollment = $this->createEnrollmentInClass($schoolClass, [
                    'student_id'       => $student->id,
                    'submission_token' => $submissionToken,
                    'enrollment_date'  => $data['enrollment_date'] ?? now(),
                    'roll_number'      => $data['roll_number'] ?? Enrollment::nextRollNumberFor($schoolClass->id),
                    'status'           => $data['status'] ?? EnrollmentStatus::ACTIVE,
                ]);

                $userCreated = $this->ensureStudentUser($student);

                return new StudentEnrollmentResult($enrollment, $userCreated);
            }, attempts: 3);

            if ($result->userCreated) {
                $user          = $result->enrollment->student()->firstOrFail()->user()->firstOrFail();
                $invitationUrl = app(FirstAccessInvitationService::class)->issue($user);
                $result        = new StudentEnrollmentResult(
                    $result->enrollment,
                    $result->userCreated,
                    $result->replayed,
                    $invitationUrl,
                );
            }

            return $result;
        } catch (QueryException $exception) {
            if ($this->isSubmissionTokenCollision($exception)
                && ($result = $this->findResultBySubmissionToken($submissionToken))) {
                return $result;
            }

            if ($this->isUniqueConstraintViolation($exception)) {
                throw $this->uniqueConstraintValidationException($exception);
            }

            throw $exception;
        }
    }

    /**
     * Vincula um aluno existente a uma turma respeitando a capacidade disponível.
     *
     * @param array<string, mixed> $data
     *
     * @return Enrollment
     */
    public function enrollExistingStudent(array $data): Enrollment {
        return DB::transaction(function () use ($data): Enrollment {
            $schoolClass = $this->findAndLockSchoolClass((int) $data['class_id']);
            $student     = Student::query()->lockForUpdate()->find($data['student_id'] ?? null);

            if (!$student) {
                throw ValidationException::withMessages([
                    'student_id' => 'O aluno selecionado não está disponível.',
                ]);
            }

            $this->ensureStudentIsNotEnrolled($student, $schoolClass);

            return $this->createEnrollmentInClass($schoolClass, $data);
        }, attempts: 3);
    }

    /**
     * Transfere uma matrícula para outra turma em uma operação atômica.
     *
     * @param Enrollment $enrollment
     * @param int $targetClassId
     * @param string $reason
     * @param int|null $operatorId
     *
     * @return Enrollment
     */
    public function transfer(
        Enrollment $enrollment,
        int $targetClassId,
        string $reason,
        ?int $operatorId = null,
    ): Enrollment {
        return DB::transaction(function () use ($enrollment, $targetClassId, $reason, $operatorId): Enrollment {
            $classes = SchoolClass::query()
                ->whereIn('id', [$enrollment->class_id, $targetClassId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $source      = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $targetClass = $classes->get($targetClassId);

            if (!$targetClass || $targetClass->school_year_id !== $source->school_year_id) {
                throw ValidationException::withMessages([
                    'class_id' => 'A turma de destino deve pertencer ao mesmo ano letivo da matrícula.',
                ]);
            }

            if ($source->status !== EnrollmentStatus::ACTIVE) {
                throw ValidationException::withMessages([
                    'class_id' => 'Somente matrículas ativas podem ser transferidas entre turmas.',
                ]);
            }

            $this->ensureStudentIsNotEnrolled($source->student()->firstOrFail(), $targetClass);
            $this->ensureClassHasSlot($targetClass, EnrollmentStatus::ACTIVE);

            $statusAnterior  = $source->status->value;
            $sourceClassName = $classes->get($source->class_id)?->name ?? 'turma de origem';
            $source->update([
                'status'              => EnrollmentStatus::TRANSFERRED_INTERNAL,
                'transfer_type'       => 'internal',
                'transfer_reason'     => $reason,
                'operated_by_user_id' => $operatorId,
            ]);

            $newEnrollment = $this->createEnrollmentInClass($targetClass, [
                'student_id'             => $source->student_id,
                'enrollment_date'        => now(),
                'status'                 => EnrollmentStatus::ACTIVE,
                'previous_enrollment_id' => $source->id,
                'operated_by_user_id'    => $operatorId,
            ]);

            EnrollmentLog::registrar(
                enrollment: $source,
                acao: 'transferencia_interna',
                statusAnterior: $statusAnterior,
                statusNovo: EnrollmentStatus::TRANSFERRED_INTERNAL->value,
                observacao: "Transferido de {$sourceClassName} para {$targetClass->name}. Nova matrícula: {$newEnrollment->registration_number}. Motivo: {$reason}",
            );

            return $newEnrollment;
        }, attempts: 3);
    }

    /**
     * Cria uma rematrícula em outra turma e ano letivo com disputa segura de vaga.
     *
     * @param Enrollment $enrollment
     * @param int $targetClassId
     * @param int $schoolYearId
     * @param int|null $operatorId
     *
     * @return Enrollment
     */
    public function reenroll(
        Enrollment $enrollment,
        int $targetClassId,
        int $schoolYearId,
        ?int $operatorId = null,
    ): Enrollment {
        return DB::transaction(function () use ($enrollment, $targetClassId, $schoolYearId, $operatorId): Enrollment {
            $targetClass = $this->findAndLockSchoolClass($targetClassId);
            $source      = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);

            if (!in_array($source->status, [EnrollmentStatus::ACTIVE, EnrollmentStatus::COMPLETED], true)) {
                throw ValidationException::withMessages([
                    'class_id' => 'Esta matrícula não está elegível para rematrícula.',
                ]);
            }

            if ($targetClass->school_year_id !== $schoolYearId) {
                throw ValidationException::withMessages([
                    'class_id' => 'A turma selecionada não pertence ao ano letivo de destino.',
                ]);
            }

            $alreadyEnrolled = Enrollment::query()
                ->where('student_id', $source->student_id)
                ->where('school_year_id', $schoolYearId)
                ->whereIn('status', EnrollmentStatus::occupyingValues())
                ->exists();

            if ($alreadyEnrolled) {
                throw ValidationException::withMessages([
                    'class_id' => 'O aluno já possui matrícula que ocupa vaga no ano letivo de destino.',
                ]);
            }

            $this->ensureStudentIsNotEnrolled($source->student()->firstOrFail(), $targetClass);

            return $this->createEnrollmentInClass($targetClass, [
                'student_id'             => $source->student_id,
                'enrollment_date'        => now(),
                'status'                 => EnrollmentStatus::ACTIVE,
                'previous_enrollment_id' => $source->id,
                'operated_by_user_id'    => $operatorId,
            ]);
        }, attempts: 3);
    }

    /**
     * Atualiza o status da matrícula validando a retomada de uma vaga liberada.
     *
     * @param Enrollment $enrollment
     * @param EnrollmentStatus|string $status
     * @param array<string, mixed> $attributes
     *
     * @return Enrollment
     */
    public function updateStatus(
        Enrollment $enrollment,
        EnrollmentStatus|string $status,
        array $attributes = [],
    ): Enrollment {
        $targetStatus = $status instanceof EnrollmentStatus ? $status : EnrollmentStatus::from($status);

        return DB::transaction(function () use ($enrollment, $targetStatus, $attributes): Enrollment {
            $schoolClass = $this->findAndLockSchoolClass((int) $enrollment->class_id);
            $locked      = Enrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            $oldStatus   = $locked->status;

            if (!$oldStatus->occupiesSlot() && $targetStatus->occupiesSlot()) {
                $this->ensureClassHasSlot($schoolClass, $targetStatus);
            }

            $locked->update(array_merge($attributes, ['status' => $targetStatus]));

            return $locked->refresh();
        }, attempts: 3);
    }

    /**
     * Retorna a quantidade de matrículas que ocupam vaga na turma.
     *
     * @param SchoolClass $schoolClass
     *
     * @return int
     */
    public function occupiedSlots(SchoolClass $schoolClass): int {
        if (array_key_exists('occupied_slots_count', $schoolClass->getAttributes())) {
            return (int) $schoolClass->getAttribute('occupied_slots_count');
        }

        return Enrollment::query()
            ->where('class_id', $schoolClass->id)
            ->whereIn('status', EnrollmentStatus::occupyingValues())
            ->count();
    }

    /**
     * Retorna as vagas restantes ou nulo quando a turma é ilimitada.
     *
     * @param SchoolClass $schoolClass
     *
     * @return int|null
     */
    public function remainingSlots(SchoolClass $schoolClass): ?int {
        if (!$schoolClass->capacity) {
            return null;
        }

        return max(0, $schoolClass->capacity - $this->occupiedSlots($schoolClass));
    }

    /**
     * Formata capacidade, ocupação e vagas restantes para exibição nos formulários.
     *
     * @param SchoolClass $schoolClass
     *
     * @return string
     */
    public function capacitySummary(SchoolClass $schoolClass): string {
        $occupied = $this->occupiedSlots($schoolClass);

        if (!$schoolClass->capacity) {
            return "Ocupação: {$occupied} | Vagas: ilimitadas";
        }

        $remaining = max(0, $schoolClass->capacity - $occupied);

        return "Ocupação: {$occupied}/{$schoolClass->capacity} | Vagas restantes: {$remaining}";
    }

    /**
     * Recupera o resultado já confirmado para uma submissão repetida.
     *
     * @param string $submissionToken
     *
     * @return StudentEnrollmentResult|null
     */
    private function findResultBySubmissionToken(string $submissionToken): ?StudentEnrollmentResult {
        $enrollment = Enrollment::query()
            ->where('submission_token', $submissionToken)
            ->first();

        return $enrollment
            ? new StudentEnrollmentResult($enrollment, userCreated: false, replayed: true)
            : null;
    }

    /**
     * Localiza e bloqueia a turma para serializar a geração do número de chamada.
     *
     * @param int $classId
     *
     * @return SchoolClass
     */
    private function findAndLockSchoolClass(int $classId): SchoolClass {
        $schoolClass = SchoolClass::query()->lockForUpdate()->find($classId);

        if (!$schoolClass) {
            throw ValidationException::withMessages([
                'class_id' => 'A turma selecionada não está disponível.',
            ]);
        }

        return $schoolClass;
    }

    /**
     * Cria uma matrícula na turma previamente bloqueada após validar sua capacidade.
     *
     * @param SchoolClass $schoolClass
     * @param array<string, mixed> $data
     *
     * @return Enrollment
     */
    private function createEnrollmentInClass(SchoolClass $schoolClass, array $data): Enrollment {
        $status = $data['status'] ?? EnrollmentStatus::ACTIVE;
        $status = $status instanceof EnrollmentStatus ? $status : EnrollmentStatus::from($status);

        $this->ensureClassHasSlot($schoolClass, $status);

        return Enrollment::create(array_merge($data, [
            'class_id'       => $schoolClass->id,
            'school_year_id' => $schoolClass->school_year_id,
            'status'         => $status,
        ]));
    }

    /**
     * Impede a ocupação de uma turma que já atingiu sua capacidade.
     *
     * @param SchoolClass $schoolClass
     * @param EnrollmentStatus $status
     *
     * @return void
     */
    private function ensureClassHasSlot(SchoolClass $schoolClass, EnrollmentStatus $status): void {
        if (!$status->occupiesSlot() || !$schoolClass->capacity) {
            return;
        }

        $occupied = $this->occupiedSlots($schoolClass);

        if ($occupied < $schoolClass->capacity) {
            return;
        }

        $slotLabel = $schoolClass->capacity === 1 ? 'vaga' : 'vagas';

        throw ValidationException::withMessages([
            'class_id' => "A turma {$schoolClass->name} atingiu a capacidade de {$schoolClass->capacity} {$slotLabel}. Ocupação atual: {$occupied}.",
        ]);
    }

    /**
     * Retorna o aluno existente ou cria o novo aluno informado no Wizard.
     *
     * @param array<string, mixed> $data
     *
     * @return Student
     */
    private function resolveStudent(array $data): Student {
        if (($data['student_source'] ?? '') !== 'new') {
            $student = Student::query()->lockForUpdate()->find($data['student_id'] ?? null);

            if (!$student) {
                throw ValidationException::withMessages([
                    'student_id' => 'O aluno selecionado não está disponível.',
                ]);
            }

            return $student;
        }

        return Student::create([
            'name'              => $data['student_name'],
            'cpf'               => $data['student_cpf'] ?? null,
            'rg'                => $data['student_rg'] ?? null,
            'birth_date'        => $data['student_birth_date'] ?? null,
            'gender'            => $data['student_gender'] ?? null,
            'email'             => $data['student_email'] ?? null,
            'phone_number'      => $data['student_phone_number'] ?? null,
            'address'           => $data['student_address'] ?? null,
            'address_district'  => $data['student_address_district'] ?? null,
            'city'              => $data['student_city'] ?? null,
            'state'             => $data['student_state'] ?? null,
            'postal_code'       => $data['student_postal_code'] ?? null,
            'mother_name'       => $data['student_mother_name'] ?? null,
            'father_name'       => $data['student_father_name'] ?? null,
            'guardian_main'     => $data['student_guardian_main'] ?? null,
            'guardian_phone'    => $data['student_guardian_phone'] ?? null,
            'guardian_email'    => $data['student_guardian_email'] ?? null,
            'transport_mode'    => $data['student_transport_mode'] ?? 'none',
            'has_special_needs' => (bool) ($data['student_has_special_needs'] ?? false),
            'allergies'         => $data['student_allergies'] ?? null,
            'medical_notes'     => $data['student_medical_notes'] ?? null,
            'status'            => StudentStatus::ACTIVE,
            'enrollment_date'   => $data['enrollment_date'] ?? now(),
        ]);
    }

    /**
     * Impede que o mesmo aluno seja matriculado mais de uma vez na turma.
     *
     * @param Student $student
     * @param SchoolClass $schoolClass
     *
     * @return void
     */
    private function ensureStudentIsNotEnrolled(Student $student, SchoolClass $schoolClass): void {
        $exists = Enrollment::withTrashed()
            ->where('student_id', $student->id)
            ->where('class_id', $schoolClass->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'class_id' => 'Este aluno já possui matrícula nesta turma.',
            ]);
        }
    }

    /**
     * Cria e vincula o usuário de acesso quando o aluno ainda não possui um.
     *
     * @param Student $student
     *
     * @return bool
     */
    private function ensureStudentUser(Student $student): bool {
        if ($student->user_id) {
            return false;
        }

        $role = Role::query()->with('permissions')->where('name', 'student')->first();
        if (!$role) {
            throw new RuntimeException('O papel de aluno não está configurado.');
        }

        $user = User::create([
            'name'                  => $student->name,
            'email'                 => $this->emailForAccess($student),
            'password'              => Hash::make(Str::random(64)),
            'active'                => true,
            'force_password_change' => true,
            'cpf'                   => $student->cpf,
            'birth_date'            => $student->birth_date,
            'gender'                => $student->gender?->value ?? $student->gender,
            'address'               => $student->address,
            'city'                  => $student->city,
            'state'                 => $student->state,
            'postal_code'           => $student->postal_code,
            'cellphone'             => $student->phone_number,
        ]);

        $user->syncRoles([$role]);
        $user->syncPermissions($role->permissions);
        $student->update(['user_id' => $user->id]);

        return true;
    }

    /**
     * Retorna o e-mail válido e exclusivo usado como login do aluno.
     *
     * @param Student $student
     *
     * @return string
     */
    private function emailForAccess(Student $student): string {
        $email = Str::lower(trim((string) $student->email));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'student_email' => 'Informe um e-mail válido para criar o acesso do aluno.',
            ]);
        }

        if ($this->emailExists($email)) {
            throw ValidationException::withMessages([
                'student_email' => 'Este e-mail já está vinculado a outro usuário.',
            ]);
        }

        return $email;
    }

    /**
     * Verifica o uso de um e-mail inclusive por usuários excluídos logicamente.
     *
     * @param string $email
     *
     * @return bool
     */
    private function emailExists(string $email): bool {
        return User::withTrashed()->where('email', $email)->exists();
    }

    /**
     * Identifica uma colisão específica da chave idempotente de submissão.
     *
     * @param QueryException $exception
     *
     * @return bool
     */
    private function isSubmissionTokenCollision(QueryException $exception): bool {
        return $this->isUniqueConstraintViolation($exception)
            && str_contains(strtolower($exception->getMessage()), 'submission_token');
    }

    /**
     * Identifica erros de violação de restrição de unicidade suportados pelos bancos usados.
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
     * Converte colisões de unicidade em erros legíveis pelo formulário.
     *
     * @param QueryException $exception
     *
     * @return ValidationException
     */
    private function uniqueConstraintValidationException(QueryException $exception): ValidationException {
        $message = strtolower($exception->getMessage());

        if (str_contains($message, 'enr_student_class_unique')
            || (str_contains($message, 'enrollments.student_id') && str_contains($message, 'enrollments.class_id'))) {
            return ValidationException::withMessages([
                'class_id' => 'Este aluno já possui matrícula nesta turma.',
            ]);
        }

        if (str_contains($message, 'students_cpf_unique') || str_contains($message, 'students.cpf')) {
            return ValidationException::withMessages([
                'student_cpf' => 'Já existe um aluno cadastrado com este CPF.',
            ]);
        }

        if (str_contains($message, 'users_email_unique') || str_contains($message, 'users.email')) {
            return ValidationException::withMessages([
                'student_email' => 'Este e-mail já está vinculado a outro usuário.',
            ]);
        }

        return ValidationException::withMessages([
            'student_id' => 'Não foi possível concluir a matrícula porque os dados já foram cadastrados.',
        ]);
    }
}
