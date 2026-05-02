<?php

namespace App\Filament\Pages\Admin;

use App\Models\Permission;
use App\Models\Role;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class PermissionMatrix extends Page
{
    protected static ?string $navigationLabel = 'Permissões de Acesso';
    protected static ?string $title = 'Permissões de Acesso';
    protected static ?string $slug = 'permission-matrix';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-user-shield';
    protected static string|null|\UnitEnum $navigationGroup = 'Segurança';
    protected static ?int $navigationSort = 99;

    public ?int $selectedRoleId = null;
    public string $search = '';
    public string $moduleFilter = 'all';
    public string $statusFilter = 'all';
    public array $permissionState = [];

    private const CRITICAL_PERMISSIONS = [
        'system.permissions.manage',
    ];

    public static function shouldRegisterNavigation(): bool
    {
        return static::canManagePermissions();
    }

    public static function canAccess(): bool
    {
        return static::canManagePermissions();
    }

    public static function canManagePermissions(): bool
    {
        return auth()->user()?->hasAnyRole(['ti', 'admin', 'super_admin']) ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->ensureConfiguredPermissionsExist();

        $preferredRoles = ['ti', 'teacher', 'student', 'secretaria', 'financeiro', 'admin', 'responsavel'];
        $this->selectedRoleId = Role::query()
            ->whereIn('name', $preferredRoles)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $preferredRoles, true))
            ->first()
            ?->id
            ?? Role::query()->orderBy('name')->value('id');

        $this->loadRolePermissions();
    }

    public function getView(): string
    {
        return 'filament.pages.admin.permission-matrix';
    }

    public function updatedSelectedRoleId(): void
    {
        $this->moduleFilter = 'all';
        $this->loadRolePermissions();
    }

    public function loadRolePermissions(): void
    {
        $role = $this->selectedRole();

        if (! $role) {
            $this->permissionState = [];
            return;
        }

        $active = $role->permissions()->pluck('name')->all();

        $this->permissionState = $this->permissionCatalog()
            ->pluck('name')
            ->mapWithKeys(fn (string $permission) => [
                $permission => in_array($permission, $active, true),
            ])
            ->all();

        foreach (self::CRITICAL_PERMISSIONS as $criticalPermission) {
            if ($this->mustKeepCriticalPermission($role, $criticalPermission)) {
                $this->permissionState[$criticalPermission] = true;
            }
        }
    }

    public function togglePermission(string $permission): void
    {
        if (! array_key_exists($permission, $this->permissionState)) {
            return;
        }

        $nextState = ! (bool) $this->permissionState[$permission];

        if (! $this->canSetPermission($permission, $nextState)) {
            return;
        }

        $this->permissionState[$permission] = $nextState;
    }

    public function enableModule(string $module): void
    {
        foreach ($this->permissionCatalog()->where('module', $module) as $permission) {
            if ($this->canSetPermission($permission['name'], true, notify: false)) {
                $this->permissionState[$permission['name']] = true;
            }
        }
    }

    public function disableModule(string $module): void
    {
        foreach ($this->permissionCatalog()->where('module', $module) as $permission) {
            if ($this->canSetPermission($permission['name'], false, notify: false)) {
                $this->permissionState[$permission['name']] = false;
            }
        }
    }

    public function makeModuleReadOnly(string $module): void
    {
        $readOnlyTypes = ['view', 'view_any', 'export', 'download'];

        foreach ($this->permissionCatalog()->where('module', $module) as $permission) {
            $enabled = in_array($permission['type'], $readOnlyTypes, true);

            if ($this->canSetPermission($permission['name'], $enabled, notify: false)) {
                $this->permissionState[$permission['name']] = $enabled;
            }
        }
    }

    public function save(): void
    {
        $role = $this->selectedRole();

        if (! $role) {
            return;
        }

        $validPermissions = [];
        $blocked = 0;

        foreach ($this->permissionState as $permission => $enabled) {
            if (! $enabled) {
                continue;
            }

            if (! $this->canSetPermission($permission, true, notify: false)) {
                $blocked++;
                continue;
            }

            $validPermissions[] = $permission;
        }

        foreach (self::CRITICAL_PERMISSIONS as $criticalPermission) {
            if ($this->mustKeepCriticalPermission($role, $criticalPermission) && ! in_array($criticalPermission, $validPermissions, true)) {
                $validPermissions[] = $criticalPermission;
                $this->permissionState[$criticalPermission] = true;
                $blocked++;
            }
        }

        $before = $role->permissions()->pluck('name')->all();

        $role->syncPermissions($validPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $after = $role->fresh()->permissions()->pluck('name')->all();
        $this->recordPermissionAuditPlaceholder($role, $before, $after);

        Notification::make()
            ->title('Permissões atualizadas')
            ->body($blocked > 0 ? 'Algumas alterações foram bloqueadas por regras de segurança.' : null)
            ->success()
            ->send();

        $this->loadRolePermissions();
    }

    public function getPageData(): array
    {
        $catalog = $this->filteredCatalog();
        $role = $this->selectedRole();
        $allCatalog = $this->coherentCatalog();
        $activeCount = $allCatalog
            ->filter(fn (array $permission) => (bool) ($this->permissionState[$permission['name']] ?? false))
            ->count();
        $totalCount = $allCatalog->count();

        return [
            'roles' => $this->roles(),
            'selectedRole' => $role,
            'modules' => $this->modules(),
            'moduleGroups' => $this->moduleGroups($catalog),
            'summary' => [
                'total' => $totalCount,
                'active' => $activeCount,
                'inactive' => max($totalCount - $activeCount, 0),
                'modules' => $this->modules()->count(),
            ],
        ];
    }

    private function ensureConfiguredPermissionsExist(): void
    {
        foreach (config('lumina-permissions', []) as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function roles(): Collection
    {
        return Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    private function selectedRole(): ?Role
    {
        if (! $this->selectedRoleId) {
            return null;
        }

        return Role::query()
            ->with(['permissions'])
            ->withCount('users')
            ->find($this->selectedRoleId);
    }

    private function permissionCatalog(): Collection
    {
        $configured = collect(config('lumina-permissions', []))
            ->map(fn (array $permission) => array_merge([
                'label' => $this->humanizePermissionName($permission['name'] ?? ''),
                'module' => 'Sistema',
                'type' => $this->inferType($permission['name'] ?? ''),
                'description' => null,
            ], $permission))
            ->keyBy('name');

        Permission::query()
            ->orderBy('name')
            ->pluck('name')
            ->each(function (string $name) use ($configured): void {
                if ($configured->has($name)) {
                    return;
                }

                $configured->put($name, [
                    'name' => $name,
                    'label' => $this->humanizePermissionName($name),
                    'module' => $this->inferModule($name),
                    'type' => $this->inferType($name),
                    'description' => 'Permissão existente mantida por compatibilidade.',
                ]);
            });

        return $configured
            ->values()
            ->sortBy([['module', 'asc'], ['label', 'asc']])
            ->values();
    }

    private function moduleGroups(Collection $catalog): Collection
    {
        return $catalog
            ->groupBy('module')
            ->map(function (Collection $permissions, string $module): array {
                return [
                    'name' => $module,
                    'total' => $permissions->count(),
                    'active' => $permissions
                        ->filter(fn (array $permission) => (bool) ($this->permissionState[$permission['name']] ?? false))
                        ->count(),
                    'permissions' => $permissions->values(),
                ];
            });
    }

    private function filteredCatalog(): Collection
    {
        return $this->coherentCatalog()
            ->filter(function (array $permission): bool {
                if ($this->moduleFilter !== 'all' && $permission['module'] !== $this->moduleFilter) {
                    return false;
                }

                $active = (bool) ($this->permissionState[$permission['name']] ?? false);

                if ($this->statusFilter === 'active' && ! $active) {
                    return false;
                }

                if ($this->statusFilter === 'inactive' && $active) {
                    return false;
                }

                if ($this->search !== '') {
                    $needle = Str::lower($this->search);

                    return Str::contains(Str::lower($permission['label']), $needle)
                        || Str::contains(Str::lower($permission['name']), $needle)
                        || Str::contains(Str::lower($permission['module']), $needle);
                }

                return true;
            })
            ->values();
    }

    private function modules(): Collection
    {
        return $this->coherentCatalog()
            ->pluck('module')
            ->unique()
            ->sort()
            ->values();
    }

    public function roleLabel(string $roleName): string
    {
        return [
            'admin' => 'Administrador',
            'ti' => 'TI',
            'super_admin' => 'Super administrador',
            'secretaria' => 'Secretaria',
            'financeiro' => 'Financeiro',
            'teacher' => 'Professor',
            'student' => 'Aluno',
            'responsavel' => 'Responsável',
            'guardian' => 'Responsável',
        ][$roleName] ?? Str::headline(str_replace(['_', '-'], ' ', $roleName));
    }

    private function coherentCatalog(): Collection
    {
        $role = $this->selectedRole();
        $catalog = $this->permissionCatalog();

        if (! $role) {
            return $catalog;
        }

        $modules = $this->coherentModulesForRole($role->name);

        if ($modules === null) {
            return $catalog;
        }

        return $catalog
            ->whereIn('module', $modules)
            ->values();
    }

    private function coherentModulesForRole(string $roleName): ?array
    {
        return match ($roleName) {
            'student' => ['Portal do Aluno'],
            'teacher' => ['Portal do Professor'],
            'secretaria' => ['Secretaria Acadêmica', 'Professores - Administrativo', 'Relatórios'],
            'financeiro' => ['Financeiro'],
            'responsavel', 'guardian' => ['Responsável'],
            'ti', 'admin', 'super_admin' => null,
            default => null,
        };
    }

    private function canSetPermission(string $permissionName, bool $enabled, bool $notify = true): bool
    {
        $role = $this->selectedRole();

        if (! $role) {
            return false;
        }

        if (! $enabled && $this->mustKeepCriticalPermission($role, $permissionName)) {
            $this->notifyBlocked('Esta permissão crítica não pode ser removida do perfil selecionado.', $notify);
            return false;
        }

        if ($enabled && ! $this->isPermissionAllowedForRole($role, $permissionName)) {
            $this->notifyBlocked('Esta permissão não é coerente com o perfil selecionado.', $notify);
            return false;
        }

        return true;
    }

    private function mustKeepCriticalPermission(Role $role, string $permissionName): bool
    {
        if (! in_array($permissionName, self::CRITICAL_PERMISSIONS, true)) {
            return false;
        }

        $user = auth()->user();

        if ($role->name === 'ti') {
            return true;
        }

        return $user?->roles()->whereKey($role->id)->exists() ?? false;
    }

    private function isPermissionAllowedForRole(Role $role, string $permissionName): bool
    {
        $permission = $this->permissionCatalog()->firstWhere('name', $permissionName);
        $module = $permission['module'] ?? $this->inferModule($permissionName);

        return match ($role->name) {
            'student' => in_array($module, ['Portal do Aluno'], true),
            'teacher' => (
                in_array($module, ['Portal do Professor', 'Relatórios'], true)
                && ! Str::startsWith($permissionName, ['reports.academic.export', 'reports.students', 'reports.teachers'])
            ) || Str::startsWith($permissionName, ['grades.view', 'grades.create', 'grades.edit']),
            'financeiro' => in_array($module, ['Financeiro'], true)
                || Str::startsWith($permissionName, ['reports.financial', 'financial.reports'])
                || ($module === 'Secretaria Acadêmica' && in_array($permission['type'] ?? null, ['view', 'view_any'], true)),
            'secretaria' => ! in_array($module, ['Sistema', 'Financeiro', 'Portal do Professor', 'Portal do Aluno', 'Responsável'], true),
            'responsavel', 'guardian' => $module === 'Responsável',
            default => true,
        };
    }

    private function notifyBlocked(string $message, bool $notify): void
    {
        if (! $notify) {
            return;
        }

        Notification::make()
            ->title('Alteração bloqueada')
            ->body($message)
            ->warning()
            ->send();
    }

    private function humanizePermissionName(string $name): string
    {
        $legacyLabels = [
            'grades.view.self' => 'Ver minhas notas',
            'subjects.view.self' => 'Ver minhas disciplinas',
            'grades.view.own' => 'Ver notas lançadas',
            'grades.create.own' => 'Lançar notas',
            'grades.update.own' => 'Corrigir notas',
            'attendance.mark.own' => 'Lançar frequência',
            'classes.view.own' => 'Ver minhas turmas',
            'subjects.view.own' => 'Ver minhas disciplinas',
        ];

        if (isset($legacyLabels[$name])) {
            return $legacyLabels[$name];
        }

        $translations = [
            'view_any' => 'Listar',
            'view' => 'Visualizar',
            'create' => 'Criar',
            'edit' => 'Editar',
            'update' => 'Atualizar',
            'delete' => 'Excluir',
            'export' => 'Exportar',
            'manage' => 'Gerenciar',
        ];

        $parts = explode('.', $name);
        $action = array_pop($parts);
        $subject = str_replace(['_', '-'], ' ', implode(' ', $parts));

        return trim(($translations[$action] ?? Str::headline($action)) . ' ' . Str::headline($subject));
    }

    private function inferModule(string $name): string
    {
        $legacyStudentPermissions = [
            'grades.view.self',
            'subjects.view.self',
        ];

        $legacyTeacherPermissions = [
            'grades.view.own',
            'grades.create.own',
            'grades.update.own',
            'attendance.mark.own',
            'classes.view.own',
            'subjects.view.own',
        ];

        if (in_array($name, $legacyStudentPermissions, true)) {
            return 'Portal do Aluno';
        }

        if (in_array($name, $legacyTeacherPermissions, true)) {
            return 'Portal do Professor';
        }

        return match (true) {
            Str::startsWith($name, 'student.') => 'Portal do Aluno',
            Str::startsWith($name, 'teacher.') => 'Portal do Professor',
            Str::startsWith($name, 'academic.') => 'Secretaria Acadêmica',
            Str::startsWith($name, ['students.', 'enrollments.', 'classes.', 'subjects.', 'school_years.', 'grade_levels.', 'grades.']) => 'Secretaria Acadêmica',
            Str::startsWith($name, ['teachers.', 'teacher_assignments.']) => 'Professores - Administrativo',
            Str::startsWith($name, 'financial.') => 'Financeiro',
            Str::startsWith($name, 'reports.') => 'Relatórios',
            Str::startsWith($name, 'guardian.') => 'Responsável',
            Str::startsWith($name, ['users.', 'roles.']) => 'Sistema',
            Str::startsWith($name, 'system.') => 'Sistema',
            default => 'Sistema',
        };
    }

    private function inferType(string $name): string
    {
        return Str::afterLast($name, '.');
    }

    private function recordPermissionAuditPlaceholder(Role $role, array $before, array $after): void
    {
        $granted = array_diff($after, $before);
        $revoked = array_diff($before, $after);

        if ($granted === [] && $revoked === []) {
            return;
        }

        /*
         * Ponto de integração futuro para auditoria de permissões:
         * operador_id: auth()->id()
         * role alterada: $role->name
         * permissão alterada: cada item de $granted / $revoked
         * ação: concedida ou revogada
         * data/hora: now()
         * IP: request()->ip()
         * user_agent: request()->userAgent()
         */
    }
}
