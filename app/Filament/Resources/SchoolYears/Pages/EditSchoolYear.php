<?php

namespace App\Filament\Resources\SchoolYears\Pages;

use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Models\SchoolYear;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSchoolYear extends EditRecord
{
    protected static string $resource = SchoolYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->is_active) {
            SchoolYear::where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}
