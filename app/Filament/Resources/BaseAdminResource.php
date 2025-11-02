<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;

abstract class BaseAdminResource extends Resource
{
    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u?->hasRole('admin') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
