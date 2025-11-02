<?php

namespace App\Filament\Resources\SchoolYears\Pages;

use App\Filament\Resources\SchoolYears\SchoolYearResource;
use App\Models\SchoolYear;
use Filament\Resources\Pages\CreateRecord;

class CreateSchoolYear extends CreateRecord
{
    protected static string $resource = SchoolYearResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->is_active) {
            SchoolYear::where('id', '!=', $this->record->id)
                ->update(['is_active' => false]);
        }
    }
}
