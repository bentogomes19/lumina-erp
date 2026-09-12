<?php

namespace App\Filament\Resources\SchoolYears\Pages;

use App\Filament\Resources\SchoolYears\SchoolYearResource;
use Asmit\ResizedColumn\HasResizableColumn;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSchoolYears extends ListRecords {

    use HasResizableColumn;

    protected static string $resource = SchoolYearResource::class;

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
