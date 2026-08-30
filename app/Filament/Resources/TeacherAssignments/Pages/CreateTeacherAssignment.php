<?php

namespace App\Filament\Resources\TeacherAssignments\Pages;

use App\Filament\Resources\TeacherAssignments\TeacherAssignmentResource;
use App\Models\Teacher;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTeacherAssignment extends CreateRecord {

    protected static string $resource = TeacherAssignmentResource::class;

    /**
     * Delega a criação da alocação ao serviço único do onboarding docente.
     *
     * @param array<string, mixed> $data
     *
     * @return Model
     */
    protected function handleRecordCreation(array $data): Model {
        $teacher = Teacher::findOrFail($data['teacher_id']);

        return app(TeacherOnboardingService::class)->createAssignment($teacher, $data);
    }
}
