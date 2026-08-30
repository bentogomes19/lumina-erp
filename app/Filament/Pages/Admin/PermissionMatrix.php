<?php

namespace App\Filament\Pages\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionAccess;
use App\Support\PermissionCatalog;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class PermissionMatrix extends Page {

    protected static ?string $navigationLabel                = 'Permissões de Acesso';
    protected static ?string $title                          = 'Permissões de Acesso';
    protected static ?string $slug                           = 'permission-matrix';
    protected static string|null|\BackedEnum $navigationIcon = 'fas-user-shield';
    protected static string|null|\UnitEnum $navigationGroup  = 'Segurança';
    protected static ?int $navigationSort                    = 99;

    public ?int $selectedRoleId   = null;
    public string $search         = '';
    public string $moduleFilter   = 'all';
    public string $statusFilter   = 'all';
    public array $permissionState = [];

    private const CRITICAL_PERMISSIONS = [
        'system.permissions.manage',
    ];

    /**
     * Determina se a página deve ser registrada na navegação.
     *
     * @return bool
     */
    public static function shouldRegisterNavigation(): bool {
        return static::canManagePermissions();
    }

    /**
     * Determina se o usuário atual pode acessar a página.
     *
     * @return bool
     */
    public static function canAccess(): bool {
        return static::canManagePermissions();
    }

    /**
     * Determina se o usuário atual pode administrar permissões.
     *
     * @return bool
     */
    public static function canManagePermissions(): bool {
        return PermissionAccess::can('system.permissions.manage');
    }

    /**
     * Inicializa o estado necessário para exibir a página.
     *
     * @return void
     */
    public function mount(): void {
        abort_unless(static::canAccess(), 403);

        $this->ensureConfiguredPermissionsExist();

        $preferredRoles       = ['ti', 'teacher', 'student', 'secretaria', 'financeiro', 'admin', 'responsavel'];
        $this->selectedRoleId = Role::query()
            ->whereIn('name', $preferredRoles)
            ->get()
            ->sortBy(fn (Role $role) => array_search($role->name, $preferredRoles, true))
            ->first()
            ?->id
            ?? Role::query()->orderBy('name')->value('id');

        $this->loadRolePermissions();
    }

    /**
     * Retorna o nome da visualização usada pela página.
     *
     * @return string
     */
    public function getView(): string {
        return 'filament.pages.admin.permission-matrix';
    }

    /**
     * Carrega as permissões quando o perfil selecionado é alterado.
     *
     * @return void
     */
    public function updatedSelectedRoleId(): void {
        $this->moduleFilter = 'all';
        $this->loadRolePermissions();
    }

    /**
     * Carrega as permissões atribuídas ao perfil selecionado.
     *
     * @return void
     */
    public function loadRolePermissions(): void {
        $role = $this->selectedRole();

        if (!$role) {
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

    /**
     * Concede ou revoga a permissão selecionada.
     *
     * @param string $permission
     *
     * @return void
     */
    public function togglePermission(string $permission): void {
        if (!array_key_exists($permission, $this->permissionState)) {
            return;
        }

        $nextState = !(bool) $this->permissionState[$permission];

        if (!$this->canSetPermission($permission, $nextState)) {
            return;
        }

        $this->permissionState[$permission] = $nextState;
    }

    /**
     * Concede as permissões editáveis do módulo selecionado.
     *
     * @param string $module
     *
     * @return void
     */
    public function enableModule(string $module): void {
        foreach ($this->permissionCatalog()->where('module', $module) as $permission) {
            if ($this->canSetPermission($permission['name'], true, notify: false)) {
                $this->permissionState[$permission['name']] = true;
            }
        }
    }

    /**
     * Revoga as permissões editáveis do módulo selecionado.
     *
     * @param string $module
     *
     * @return void
     */
    public function disableModule(string $module): void {
        foreach ($this->permissionCatalog()->where('module', $module) as $permission) {
            if ($this->canSetPermission($permission['name'], false, notify: false)) {
                $this->permissionState[$permission['name']] = false;
            }
        }
    }

    /**
     * Mantém somente as permissões de leitura do módulo selecionado.
     *
     * @param string $module
     *
     * @return void
     */
    public function makeModuleReadOnly(string $module): void {
        $readOnlyTypes = ['view', 'view_any', 'export', 'download'];

        foreach ($this->permissionCatalog()->where('module', $module) as $permission) {
            $enabled = in_array($permission['type'], $readOnlyTypes, true);

            if ($this->canSetPermission($permission['name'], $enabled, notify: false)) {
                $this->permissionState[$permission['name']] = $enabled;
            }
        }
    }

    /**
     * Salva as permissões configuradas para o perfil selecionado.
     *
     * @return void
     */
    public function save(): void {
        $role = $this->selectedRole();

        if (!$role) {
            return;
        }

        $validPermissions = [];
        $blocked          = 0;

        foreach ($this->permissionState as $permission => $enabled) {
            if (!$enabled) {
                continue;
            }

            if (!$this->canSetPermission($permission, true, notify: false)) {
                $blocked++;
                continue;
            }

            $validPermissions[] = $permission;
        }

        foreach (self::CRITICAL_PERMISSIONS as $criticalPermission) {
            if ($this->mustKeepCriticalPermission($role, $criticalPermission) && !in_array($criticalPermission, $validPermissions, true)) {
                $validPermissions[]                         = $criticalPermission;
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

    /**
     * Retorna os dados necessários para montar a página.
     *
     * @return array
     */
    public function getPageData(): array {
        $catalog     = $this->filteredCatalog();
        $role        = $this->selectedRole();
        $allCatalog  = $this->coherentCatalog();
        $activeCount = $allCatalog
            ->filter(fn (array $permission) => (bool) ($this->permissionState[$permission['name']] ?? false))
            ->count();
        $totalCount = $allCatalog->count();

        return [
            'roles'        => $this->roles(),
            'selectedRole' => $role,
            'modules'      => $this->modules(),
            'moduleGroups' => $this->moduleGroups($catalog),
            'summary'      => [
                'total'    => $totalCount,
                'active'   => $activeCount,
                'inactive' => max($totalCount - $activeCount, 0),
                'modules'  => $this->modules()->count(),
            ],
        ];
    }

    /**
     * Garante que as permissões configuradas existam no banco de dados.
     *
     * @return void
     */
    private function ensureConfiguredPermissionsExist(): void {
        foreach (PermissionCatalog::all() as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission['name'],
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Retorna os perfis disponíveis na matriz de permissões.
     *
     * @return Collection
     */
    private function roles(): Collection {
        return Role::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();
    }

    /**
     * Retorna o perfil atualmente selecionado.
     *
     * @return Role|null
     */
    private function selectedRole(): ?Role {
        if (!$this->selectedRoleId) {
            return null;
        }

        return Role::query()
            ->with(['permissions'])
            ->withCount('users')
            ->find($this->selectedRoleId);
    }

    /**
     * Retorna o catálogo completo de permissões.
     *
     * @return Collection
     */
    private function permissionCatalog(): Collection {
        return PermissionCatalog::all()
            ->map(fn (array $permission) => array_merge([
                'label'       => $this->humanizePermissionName($permission['name'] ?? ''),
                'module'      => 'Sistema',
                'type'        => $this->inferType($permission['name'] ?? ''),
                'description' => null,
            ], $permission))
            ->sortBy([['module', 'asc'], ['label', 'asc']])
            ->values();
    }

    /**
     * Retorna as permissões agrupadas por módulo.
     *
     * @param Collection $catalog
     *
     * @return Collection
     */
    private function moduleGroups(Collection $catalog): Collection {
        return $catalog
            ->groupBy('module')
            ->map(function (Collection $permissions, string $module): array {
                return [
                    'name'   => $module,
                    'total'  => $permissions->count(),
                    'active' => $permissions
                        ->filter(fn (array $permission) => (bool) ($this->permissionState[$permission['name']] ?? false))
                        ->count(),
                    'permissions' => $permissions->values(),
                ];
            });
    }

    /**
     * Retorna o catálogo de permissões filtrado pela pesquisa atual.
     *
     * @return Collection
     */
    private function filteredCatalog(): Collection {
        return $this->coherentCatalog()
            ->filter(function (array $permission): bool {
                if ($this->moduleFilter !== 'all' && $permission['module'] !== $this->moduleFilter) {
                    return false;
                }

                $active = (bool) ($this->permissionState[$permission['name']] ?? false);

                if ($this->statusFilter === 'active' && !$active) {
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

    /**
     * Retorna os módulos disponíveis na matriz de permissões.
     *
     * @return Collection
     */
    private function modules(): Collection {
        return $this->coherentCatalog()
            ->pluck('module')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Retorna o rótulo legível do perfil informado.
     *
     * @param string $roleName
     *
     * @return string
     */
    public function roleLabel(string $roleName): string {
        return [
            'admin'       => 'Administrador',
            'ti'          => 'TI',
            'super_admin' => 'Super administrador',
            'secretaria'  => 'Secretaria',
            'financeiro'  => 'Financeiro',
            'teacher'     => 'Professor',
            'student'     => 'Aluno',
            'responsavel' => 'Responsável',
            'guardian'    => 'Responsável',
        ][$roleName] ?? Str::headline(str_replace(['_', '-'], ' ', $roleName));
    }

    /**
     * Retorna o catálogo de permissões compatível com o perfil selecionado.
     *
     * @return Collection
     */
    private function coherentCatalog(): Collection {
        $role    = $this->selectedRole();
        $catalog = $this->permissionCatalog();

        if (!$role) {
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

    /**
     * Retorna os módulos compatíveis com o perfil selecionado.
     *
     * @param string $roleName
     *
     * @return array|null
     */
    private function coherentModulesForRole(string $roleName): ?array {
        return match ($roleName) {
            'student'    => ['Portal do Aluno'],
            'teacher'    => ['Portal do Professor'],
            'secretaria' => ['Secretaria Acadêmica', 'Professores - Administrativo', 'Relatórios'],
            'financeiro' => ['Financeiro', 'Secretaria Acadêmica'],
            'responsavel', 'guardian' => ['Responsável'],
            'ti', 'admin', 'super_admin' => null,
            default => null,
        };
    }

    /**
     * Determina se a permissão pode receber o estado solicitado.
     *
     * @param string $permissionName
     * @param bool $enabled
     * @param bool $notify
     *
     * @return bool
     */
    private function canSetPermission(string $permissionName, bool $enabled, bool $notify = true): bool {
        $role = $this->selectedRole();

        if (!$role) {
            return false;
        }

        if (!$enabled && $this->mustKeepCriticalPermission($role, $permissionName)) {
            $this->notifyBlocked('Esta permissão crítica não pode ser removida do perfil selecionado.', $notify);
            return false;
        }

        if ($enabled && !$this->isPermissionAllowedForRole($role, $permissionName)) {
            $this->notifyBlocked('Esta permissão não é coerente com o perfil selecionado.', $notify);
            return false;
        }

        return true;
    }

    /**
     * Determina se uma permissão crítica deve ser preservada.
     *
     * @param Role $role
     * @param string $permissionName
     *
     * @return bool
     */
    private function mustKeepCriticalPermission(Role $role, string $permissionName): bool {
        if (!in_array($permissionName, self::CRITICAL_PERMISSIONS, true)) {
            return false;
        }

        $user = auth()->user();

        if ($role->name === 'ti') {
            return true;
        }

        return $user?->roles()->whereKey($role->id)->exists() ?? false;
    }

    /**
     * Determina se a permissão é compatível com as regras do perfil informado.
     *
     * @param Role $role
     * @param string $permissionName
     *
     * @return bool
     */
    private function isPermissionAllowedForRole(Role $role, string $permissionName): bool {
        $permission = $this->permissionCatalog()->firstWhere('name', $permissionName);
        $module     = $permission['module'] ?? $this->inferModule($permissionName);

        return match ($role->name) {
            'student' => in_array($module, ['Portal do Aluno'], true),
            'teacher' => (
                in_array($module, ['Portal do Professor', 'Relatórios'], true)
                && !Str::startsWith($permissionName, ['reports.academic.export', 'reports.students', 'reports.teachers'])
            ),
            'financeiro' => in_array($module, ['Financeiro'], true)
                || Str::startsWith($permissionName, ['reports.financial', 'financial.reports'])
                || Str::startsWith($permissionName, ['academic.enrollments.'])
                || ($module === 'Secretaria Acadêmica' && in_array($permission['type'] ?? null, ['view', 'view_any'], true)),
            'secretaria' => !in_array($module, ['Sistema', 'Financeiro', 'Portal do Professor', 'Portal do Aluno', 'Responsável'], true),
            'responsavel', 'guardian' => $module === 'Responsável',
            default => true,
        };
    }

    /**
     * Notifica que a alteração de permissão foi bloqueada.
     *
     * @param string $message
     * @param bool $notify
     *
     * @return void
     */
    private function notifyBlocked(string $message, bool $notify): void {
        if (!$notify) {
            return;
        }

        Notification::make()
            ->title('Alteração bloqueada')
            ->body($message)
            ->warning()
            ->send();
    }

    /**
     * Converte o nome técnico da permissão em um rótulo legível.
     *
     * @param string $name
     *
     * @return string
     */
    private function humanizePermissionName(string $name): string {
        $translations = [
            'view_any' => 'Listar',
            'view'     => 'Visualizar',
            'create'   => 'Criar',
            'edit'     => 'Editar',
            'update'   => 'Atualizar',
            'delete'   => 'Excluir',
            'export'   => 'Exportar',
            'manage'   => 'Gerenciar',
        ];

        $parts   = explode('.', $name);
        $action  = array_pop($parts);
        $subject = str_replace(['_', '-'], ' ', implode(' ', $parts));

        return trim(($translations[$action] ?? Str::headline($action)) . ' ' . Str::headline($subject));
    }

    /**
     * Identifica o módulo ao qual uma permissão pertence.
     *
     * @param string $name
     *
     * @return string
     */
    private function inferModule(string $name): string {
        return match (true) {
            Str::startsWith($name, 'student.')  => 'Portal do Aluno',
            Str::startsWith($name, 'teacher.')  => 'Portal do Professor',
            Str::startsWith($name, 'academic.') => 'Secretaria Acadêmica',
            Str::startsWith($name, 'admin.')    => 'Professores - Administrativo',
            Str::startsWith($name, 'financial.') => 'Financeiro',
            Str::startsWith($name, 'reports.')   => 'Relatórios',
            Str::startsWith($name, 'guardian.')  => 'Responsável',
            default                              => 'Sistema',
        };
    }

    /**
     * Identifica o tipo de operação representado pela permissão.
     *
     * @param string $name
     *
     * @return string
     */
    private function inferType(string $name): string {
        return Str::afterLast($name, '.');
    }

    /**
     * Reserva o ponto de integração para auditoria de permissões.
     *
     * @param Role $role
     * @param array $before
     * @param array $after
     *
     * @return void
     */
    private function recordPermissionAuditPlaceholder(Role $role, array $before, array $after): void {
        $granted = array_diff($after, $before);
        $revoked = array_diff($before, $after);

        if ($granted === [] && $revoked === []) {
            return;
        }

        /* Ponto de integração futuro para auditoria de permissões: operador_id: auth()->id() role alterada: $role->name permissão alterada: cada item de $granted / $revoked ação: concedida ou revogada data/hora: now() IP: request()->ip() user_agent: request()->userAgent() */
    }
}
