<?php

namespace App\Filament\Resources\SchoolYears\Pages;

use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Models\SchoolYear;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolYear extends EditRecord {

    protected static string $resource = SchoolYearResource::class;

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
     * Desativa os demais anos letivos quando o registro salvo é definido como ativo.
     *
     * @return void
     */
    protected function afterSave(): void {
        if ($this->record->is_active) {
            SchoolYear::where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}
