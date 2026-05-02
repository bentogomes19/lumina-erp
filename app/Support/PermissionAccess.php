<?php

namespace App\Support;

use Illuminate\Support\Collection;

class PermissionAccess
{
    public static function can(string $permission): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->roles()
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))
            ->exists()) {
            return true;
        }

        $catalogPermission = self::catalog()->firstWhere('name', $permission);

        if ($catalogPermission) {
            $modulePermissionNames = self::catalog()
                ->where('module', $catalogPermission['module'])
                ->pluck('name')
                ->all();

            $roleAlreadyUsesMatrixForModule = $user->roles()
                ->whereHas('permissions', fn ($query) => $query->whereIn('name', $modulePermissionNames))
                ->exists();

            if ($roleAlreadyUsesMatrixForModule) {
                return false;
            }
        }

        return self::legacyRoleFallback($permission);
    }

    private static function catalog(): Collection
    {
        return collect(config('lumina-permissions', []));
    }

    private static function legacyRoleFallback(string $permission): bool
    {
        $role = match ($permission) {
            'student.dashboard.view',
            'student.grades.view',
            'student.attendance.view',
            'student.subjects.view',
            'student.calendar.view',
            'student.assessments.view',
            'student.profile.view',
            'student.documents.view',
            'student.report-card.download' => 'student',

            'teacher.dashboard.view',
            'teacher.classes.view',
            'teacher.subjects.view',
            'teacher.schedule.view',
            'teacher.attendance.view',
            'teacher.attendance.create',
            'teacher.attendance.update',
            'teacher.assessments.view',
            'teacher.assessments.create',
            'teacher.assessments.update',
            'teacher.assessments.close',
            'teacher.grades.view',
            'teacher.grades.create',
            'teacher.grades.update',
            'teacher.grades.publish',
            'teacher.announcements.view',
            'teacher.pending.view',
            'teacher.profile.view',
            'teacher.profile.update-basic' => 'teacher',

            default => null,
        };

        return $role !== null && (auth()->user()?->hasRole($role) ?? false);
    }
}
