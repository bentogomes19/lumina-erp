<?php

namespace App\Filament\Resources\TeacherAssignments\Pages;

use App\Filament\Resources\TeacherAssignments\TeacherAssignmentResource;
use App\Services\Teachers\TeacherOnboardingService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTeacherAssignment extends EditRecord {

    protected static string $resource = TeacherAssignmentResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * Delega a atualização ao serviço único de alocações docentes.
     *
     * @param Model $record
     * @param array<string, mixed> $data
     *
     * @return Model
     */
    protected function handleRecordUpdate(Model $record, array $data): Model {
        return app(TeacherOnboardingService::class)->updateAssignment($record, $data);
    }
}
