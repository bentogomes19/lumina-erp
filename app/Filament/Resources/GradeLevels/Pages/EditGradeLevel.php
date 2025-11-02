<?php

namespace App\Filament\Resources\GradeLevels\Pages;

use App\Filament\Resources\GradeLevels\GradeLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGradeLevel extends EditRecord
{
    protected static string $resource = GradeLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('roles.view') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('roles.create') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('roles.update') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('roles.delete') ?? false; }
}
