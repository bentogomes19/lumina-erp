<?php

namespace App\Services\Enrollments;

use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
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
            return DB::transaction(function () use ($data, $submissionToken): StudentEnrollmentResult {
                if ($result = $this->findResultBySubmissionToken($submissionToken)) {
                    return $result;
                }

                $schoolClass = $this->findAndLockSchoolClass((int) $data['class_id']);
                $student     = $this->resolveStudent($data);

                $this->ensureStudentIsNotEnrolled($student, $schoolClass);

                $enrollment = Enrollment::create([
                    'student_id'       => $student->id,
                    'class_id'         => $schoolClass->id,
                    'school_year_id'   => $schoolClass->school_year_id,
                    'submission_token' => $submissionToken,
                    'enrollment_date'  => $data['enrollment_date'] ?? now(),
                    'roll_number'      => $data['roll_number'] ?? Enrollment::nextRollNumberFor($schoolClass->id),
                    'status'           => $data['status'] ?? 'Ativa',
                ]);

                $userCreated = $this->ensureStudentUser($student);

                return new StudentEnrollmentResult($enrollment, $userCreated);
            }, attempts: 3);
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
            'name'        => $student->name,
            'email'       => $this->uniqueEmailFor($student),
            'password'    => Hash::make(Str::random(12)),
            'active'      => true,
            'cpf'         => $student->cpf,
            'birth_date'  => $student->birth_date,
            'gender'      => $student->gender?->value ?? $student->gender,
            'address'     => $student->address,
            'city'        => $student->city,
            'state'       => $student->state,
            'postal_code' => $student->postal_code,
            'cellphone'   => $student->phone_number,
        ]);

        $user->syncRoles([$role]);
        $user->syncPermissions($role->permissions);
        $student->update(['user_id' => $user->id]);

        return true;
    }

    /**
     * Gera um e-mail disponível para o novo usuário do aluno.
     *
     * @param Student $student
     *
     * @return string
     */
    private function uniqueEmailFor(Student $student): string {
        if ($student->email && !$this->emailExists($student->email)) {
            return $student->email;
        }

        $base    = Str::slug($student->name) ?: 'aluno';
        $domain  = config('app.domain') ?: 'escola.local';
        $email   = "{$base}@{$domain}";
        $counter = 1;

        while ($this->emailExists($email)) {
            $email = "{$base}+{$counter}@{$domain}";
            $counter++;
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

        return ValidationException::withMessages([
            'student_id' => 'Não foi possível concluir a matrícula porque os dados já foram cadastrados.',
        ]);
    }
}
