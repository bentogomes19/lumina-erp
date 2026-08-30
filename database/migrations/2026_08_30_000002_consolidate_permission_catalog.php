<?php

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class () extends Migration {

    /**
     * Converte atribuições legadas para o catálogo canônico sem perda de vínculos.
     *
     * @return void
     */
    public function up(): void {
        DB::transaction(function (): void {
            $this->ensureCanonicalPermissions();

            foreach (PermissionCatalog::aliases() as $legacyName => $canonicalNames) {
                $legacy = Permission::query()
                    ->where('name', $legacyName)
                    ->where('guard_name', 'web')
                    ->first();

                if (!$legacy) {
                    continue;
                }

                foreach (Arr::wrap($canonicalNames) as $canonicalName) {
                    $canonical = Permission::query()->firstOrCreate([
                        'name'       => $canonicalName,
                        'guard_name' => 'web',
                    ]);

                    $this->copyAssignments($legacy->id, $canonical->id);
                }

                $this->deleteAssignments((int) $legacy->id);
                $legacy->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Restaura os aliases com base nas atribuições canônicas existentes.
     *
     * @return void
     */
    public function down(): void {
        DB::transaction(function (): void {
            foreach (PermissionCatalog::aliases() as $legacyName => $canonicalNames) {
                $legacy = Permission::query()->firstOrCreate([
                    'name'       => $legacyName,
                    'guard_name' => 'web',
                ]);

                foreach (Arr::wrap($canonicalNames) as $canonicalName) {
                    $canonical = Permission::query()
                        ->where('name', $canonicalName)
                        ->where('guard_name', 'web')
                        ->first();

                    if ($canonical) {
                        $this->copyAssignments((int) $canonical->id, (int) $legacy->id);
                    }
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Garante que todas as permissões canônicas existam antes da conversão.
     *
     * @return void
     */
    private function ensureCanonicalPermissions(): void {
        foreach (PermissionCatalog::names() as $permission) {
            Permission::query()->firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    /**
     * Copia atribuições de papel e de usuário entre duas permissões.
     *
     * @param int $sourcePermissionId
     * @param int $targetPermissionId
     *
     * @return void
     */
    private function copyAssignments(int $sourcePermissionId, int $targetPermissionId): void {
        $tables = config('permission.table_names');

        DB::table($tables['role_has_permissions'])
            ->where('permission_id', $sourcePermissionId)
            ->get(['role_id'])
            ->each(function (object $assignment) use ($tables, $targetPermissionId): void {
                DB::table($tables['role_has_permissions'])->insertOrIgnore([
                    'permission_id' => $targetPermissionId,
                    'role_id'       => $assignment->role_id,
                ]);
            });

        DB::table($tables['model_has_permissions'])
            ->where('permission_id', $sourcePermissionId)
            ->get(['model_type', 'model_id'])
            ->each(function (object $assignment) use ($tables, $targetPermissionId): void {
                DB::table($tables['model_has_permissions'])->insertOrIgnore([
                    'permission_id' => $targetPermissionId,
                    'model_type'    => $assignment->model_type,
                    'model_id'      => $assignment->model_id,
                ]);
            });
    }

    /**
     * Remove os vínculos da permissão legada antes de excluí-la.
     *
     * @param int $permissionId
     *
     * @return void
     */
    private function deleteAssignments(int $permissionId): void {
        $tables = config('permission.table_names');

        DB::table($tables['role_has_permissions'])
            ->where('permission_id', $permissionId)
            ->delete();
        DB::table($tables['model_has_permissions'])
            ->where('permission_id', $permissionId)
            ->delete();
    }
};
