<?php

namespace App\Filament\Resources\GradeLevels\Pages;

use App\Filament\Resources\GradeLevels\GradeLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGradeLevels extends ListRecords {

    protected static string $resource = GradeLevelResource::class;

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
