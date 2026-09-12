<?php

namespace App\Filament\Resources\TeacherAssignments\Pages;

use App\Filament\Resources\TeacherAssignments\TeacherAssignmentResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeacherAssignments extends ListRecords {

    use HasResizableColumn;

    protected static string $resource = TeacherAssignmentResource::class;

    /**
     * Retorna as ações exibidas no cabeçalho.
     *
     * @return array
     */
    protected function getHeaderActions(): array {
        return [
            CreateAction::make(),
        ];
    }
}
