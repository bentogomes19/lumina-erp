<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

class DiagnoseDirectPermissions extends Command {

    /**
     * Nome e opções do comando de diagnóstico.
     *
     * @var string
     */
    protected $signature = 'permissions:direct
        {--prune : Remove permissões diretas redundantes}
        {--dry-run : Executa apenas em modo de simulação}';

    /**
     * Descrição exibida na listagem de comandos Artisan.
     *
     * @var string
     */
    protected $description = 'Diagnostica permissões diretas e remove redundâncias herdadas por papéis.';

    /**
     * Executa o diagnóstico de permissões diretas.
     *
     * @return int
     */
    public function handle(): int {
        $rows       = $this->directPermissionRows();
        $redundants = $rows->where('status', 'Redundante');
        $exceptions = $rows->where('status', 'Exceção direta');
        $prune      = (bool) $this->option('prune');
        $dryRun     = !$prune || (bool) $this->option('dry-run');

        if ($rows->isEmpty()) {
            $this->info('Nenhuma permissão direta encontrada.');

            return self::SUCCESS;
        }

        $this->table(
            ['Usuário', 'E-mail', 'Permissão', 'Status', 'Papéis'],
            $rows->map(fn (array $row): array => [
                $row['user'],
                $row['email'],
                $row['permission'],
                $row['status'],
                $row['roles'],
            ])->all(),
        );

        $this->line("Redundantes: {$redundants->count()}");
        $this->line("Exceções diretas: {$exceptions->count()}");

        if ($dryRun) {
            $this->comment('Simulação concluída. Use --prune sem --dry-run para remover redundâncias.');

            return self::SUCCESS;
        }

        $this->prune($redundants);
        $this->info("Permissões diretas redundantes removidas: {$redundants->count()}");

        if ($exceptions->isNotEmpty()) {
            $this->warn('Permissões diretas excepcionais foram preservadas. Registre justificativa e auditoria conforme o procedimento documentado.');
        }

        return self::SUCCESS;
    }

    /**
     * Monta as linhas de permissões diretas com classificação de redundância.
     *
     * @return Collection<int, array<string, string|int>>
     */
    private function directPermissionRows(): Collection {
        return User::query()
            ->with(['permissions', 'roles.permissions'])
            ->whereHas('permissions')
            ->orderBy('id')
            ->get()
            ->flatMap(function (User $user): Collection {
                $rolePermissionIds = $user->roles
                    ->flatMap(fn ($role): Collection => $role->permissions->pluck('id'))
                    ->unique()
                    ->values();
                $roles = $user->roles->pluck('name')->implode(', ');

                return $user->permissions
                    ->sortBy('name')
                    ->map(fn ($permission): array => [
                        'user_id'       => $user->id,
                        'user'          => $user->name,
                        'email'         => $user->email,
                        'permission'    => $permission->name,
                        'permission_id' => $permission->id,
                        'status'        => $rolePermissionIds->containsStrict($permission->id)
                            ? 'Redundante'
                            : 'Exceção direta',
                        'roles'         => $roles !== '' ? $roles : 'Sem papel',
                    ]);
            })
            ->values();
    }

    /**
     * Remove permissões diretas classificadas como redundantes.
     *
     * @param Collection<int, array<string, string|int>> $redundants
     *
     * @return void
     */
    private function prune(Collection $redundants): void {
        $redundants->each(function (array $row): void {
            $user = User::query()->find($row['user_id']);

            if (!$user) {
                return;
            }

            $user->revokePermissionTo($row['permission']);
            Log::info('Permissão direta redundante removida.', [
                'user_id'    => $row['user_id'],
                'email'      => $row['email'],
                'permission' => $row['permission'],
                'roles'      => $row['roles'],
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
