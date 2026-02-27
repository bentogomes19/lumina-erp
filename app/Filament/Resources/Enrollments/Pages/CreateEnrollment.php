<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Enums\StudentStatus;
use App\Filament\Resources\Enrollments\EnrollmentResource;
use App\Filament\Resources\Enrollments\Schemas\EnrollmentWizardSchema;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CreateEnrollment extends CreateRecord
{
    use HasWizard;

    protected static string $resource = EnrollmentResource::class;

    public function mount(): void
    {
        parent::mount();
        $sid = request()->query('student_id');
        if (filled($sid)) {
            $this->form->fill([
                'student_id' => $sid,
                'student_source' => 'existing',
            ]);
        }
    }

    public function getSteps(): array
    {
        return EnrollmentWizardSchema::getSteps();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['student_source'] ?? '') === 'new') {
            $student = DB::transaction(function () use ($data) {
                return Student::create([
                    'name' => $data['student_name'],
                    'cpf' => $data['student_cpf'] ?? null,
                    'rg' => $data['student_rg'] ?? null,
                    'birth_date' => $data['student_birth_date'] ?? null,
                    'gender' => $data['student_gender'] ?? null,
                    'email' => $data['student_email'] ?? null,
                    'phone_number' => $data['student_phone_number'] ?? null,
                    'address' => $data['student_address'] ?? null,
                    'address_district' => $data['student_address_district'] ?? null,
                    'city' => $data['student_city'] ?? null,
                    'state' => $data['student_state'] ?? null,
                    'postal_code' => $data['student_postal_code'] ?? null,
                    'mother_name' => $data['student_mother_name'] ?? null,
                    'father_name' => $data['student_father_name'] ?? null,
                    'guardian_main' => $data['student_guardian_main'] ?? null,
                    'guardian_phone' => $data['student_guardian_phone'] ?? null,
                    'guardian_email' => $data['student_guardian_email'] ?? null,
                    'transport_mode' => $data['student_transport_mode'] ?? 'none',
                    'has_special_needs' => (bool) ($data['student_has_special_needs'] ?? false),
                    'allergies' => $data['student_allergies'] ?? null,
                    'medical_notes' => $data['student_medical_notes'] ?? null,
                    'status' => StudentStatus::ACTIVE,
                    'enrollment_date' => $data['enrollment_date'] ?? now(),
                ]);
            });
            $data['student_id'] = $student->id;
        }

        // Garantir unicidade: um aluno só pode ter uma matrícula por turma
        $exists = Enrollment::where('student_id', $data['student_id'])
            ->where('class_id', $data['class_id'])
            ->exists();
        if ($exists) {
            $validator = validator([], []);
            $validator->errors()->add('class_id', 'Este aluno já possui matrícula nesta turma.');
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return [
            'student_id' => $data['student_id'],
            'class_id' => $data['class_id'],
            'enrollment_date' => $data['enrollment_date'],
            'roll_number' => $data['roll_number'] ?? Enrollment::nextRollNumberFor((int) $data['class_id']),
            'status' => $data['status'] ?? 'Ativa',
        ];
    }

    protected function afterCreate(): void
    {
        $enrollment = $this->record;
        $student = $enrollment->student;

        if (!$student || $student->user_id) {
            return;
        }

        DB::transaction(function () use ($student) {
            $email = $student->email;
            if (!$email || User::where('email', $email)->exists()) {
                $base = Str::slug($student->name);
                $email = $base . '@' . (config('app.domain') ?: 'escola.local');
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = $base . '+' . $counter . '@' . (config('app.domain') ?: 'escola.local');
                    $counter++;
                }
            }

            $user = User::create([
                'name' => $student->name,
                'email' => $email,
                'password' => Hash::make(Str::random(12)),
                'active' => true,
                'cpf' => $student->cpf,
                'birth_date' => $student->birth_date,
                'gender' => $student->gender?->value ?? $student->gender,
                'address' => $student->address,
                'city' => $student->city,
                'state' => $student->state,
                'postal_code' => $student->postal_code,
                'cellphone' => $student->phone_number,
            ]);

            $user->syncRoles(['student']);
            $role = Role::where('name', 'student')->with('permissions')->first();
            if ($role) {
                $user->syncPermissions($role->permissions);
            }

            $student->update(['user_id' => $user->id]);
        });

        Notification::make()
            ->title('Usuário criado para o aluno')
            ->body('O aluno não possuía usuário de acesso. Foi criado um usuário. O aluno pode usar "Esqueci minha senha" para definir a senha.')
            ->success()
            ->send();
    }
}
