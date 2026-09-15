<?php

namespace App\Filament\Resources\SystemParameters\Pages;

use App\Filament\Resources\SystemParameters\SystemParameterResource;
use App\Filament\Resources\SystemParameters\Schemas\SystemParameterForm;
use Filament\Resources\Pages\CreateRecord;

class CreateSystemParameter extends CreateRecord
{
    protected static string $resource = SystemParameterResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SystemParameterForm::normalizeData($data);
    }
}
