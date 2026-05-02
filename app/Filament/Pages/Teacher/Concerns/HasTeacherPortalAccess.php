<?php

namespace App\Filament\Pages\Teacher\Concerns;

use App\Support\PermissionAccess;

trait HasTeacherPortalAccess
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return PermissionAccess::can(static::teacherPortalPermission());
    }

    protected static function teacherPortalPermission(): string
    {
        return static::$teacherPortalPermission ?? 'teacher.dashboard.view';
    }
}
