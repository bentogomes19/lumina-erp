<?php

namespace App\Filament\Resources\Enrollments\Pages;

use App\Filament\Resources\Enrollments\EnrollmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEnrollment extends EditRecord
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;
        $hasGrades = $record && $record->grades()->exists();

        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can('delete', $record))
                ->disabled($hasGrades)
                ->tooltip($hasGrades ? 'Não é possível excluir matrícula com notas lançadas. Cancele a matrícula (status Cancelada) em vez de excluir.' : null),
        ];
    }
}
