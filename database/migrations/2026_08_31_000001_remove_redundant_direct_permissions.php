<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class () extends Migration {

    /**
     * Remove permissões diretas que já são herdadas pelos papéis do usuário.
     *
     * @return void
     */
    public function up(): void {
        DB::transaction(function (): void {
            foreach ($this->redundantDirectPermissions() as $assignment) {
                DB::table($this->tables()['model_has_permissions'])
                    ->where('permission_id', $assignment->permission_id)
                    ->where('model_type', $assignment->model_type)
                    ->where($this->modelKey(), $assignment->model_id)
                    ->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Não recria permissões diretas redundantes removidas pela migração.
     *
     * @return void
     */
    public function down(): void {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Lista permissões diretas cobertas por algum papel do mesmo usuário.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function redundantDirectPermissions(): \Illuminate\Support\Collection {
        $tables = $this->tables();

        return DB::table("{$tables['model_has_permissions']} as direct")
            ->join("{$tables['model_has_roles']} as model_role", function ($join): void {
                $join->on('model_role.model_type', '=', 'direct.model_type')
                    ->on('model_role.'.$this->modelKey(), '=', 'direct.'.$this->modelKey());
            })
            ->join("{$tables['role_has_permissions']} as role_permission", function ($join): void {
                $join->on('role_permission.role_id', '=', 'model_role.role_id')
                    ->on('role_permission.permission_id', '=', 'direct.permission_id');
            })
            ->select([
                'direct.permission_id',
                'direct.model_type',
                'direct.'.$this->modelKey().' as model_id',
            ])
            ->distinct()
            ->get();
    }

    /**
     * Retorna os nomes das tabelas configuradas pelo Spatie Permission.
     *
     * @return array<string, string>
     */
    private function tables(): array {
        return config('permission.table_names');
    }

    /**
     * Retorna a coluna usada como chave morfológica do usuário.
     *
     * @return string
     */
    private function modelKey(): string {
        return config('permission.column_names.model_morph_key', 'model_id');
    }
};
