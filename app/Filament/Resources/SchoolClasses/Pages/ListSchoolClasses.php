<?php

namespace App\Filament\Resources\SchoolClasses\Pages;

use App\Filament\Resources\SchoolClasses\SchoolClassResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolClasses extends ListRecords {

    use HasResizableColumn;

    protected static string $resource = SchoolClassResource::class;

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
