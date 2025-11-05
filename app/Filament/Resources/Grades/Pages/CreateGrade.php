<?php

namespace App\Filament\Resources\Grades\Pages;

use App\Filament\Resources\Grades\GradeResource;
use App\Models\Enrollment;
use Filament\Resources\Pages\CreateRecord;

class CreateGrade extends CreateRecord
{
    protected static string $resource = GradeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se vier a matrícula, preenche o student_id
        if (! empty($data['enrollment_id'])) {
            $enrollment = Enrollment::find($data['enrollment_id']);

            if ($enrollment) {
                $data['student_id'] = $enrollment->student_id;
            }
        }

        return $data;
    }
}
