<?php

namespace App\Filament\Resources\SystemParameters\Pages;

use App\Filament\Resources\SystemParameters\SystemParameterResource;
use App\Filament\Resources\SystemParameters\Schemas\SystemParameterForm;
use Filament\Resources\Pages\EditRecord;

class EditSystemParameter extends EditRecord
{
    protected static string $resource = SystemParameterResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return SystemParameterForm::normalizeData($data);
    }
}
