<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    /**
     * Parâmetros que não se aplicam ao fluxo de primeiro acesso por link.
     *
     * O sistema não cria senha inicial: o usuário recebe uma senha aleatória e
     * define a própria pelo convite, que já obriga essa definição.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $parameters = [
        [
            'key'         => 'security.initial_password_rule',
            'name'        => 'Regra da senha inicial',
            'type'        => 'select',
            'value'       => 'temporary',
            'options'     => ['temporary', 'registration', 'cpf_last_digits'],
            'description' => 'Define como a senha inicial de novos acessos será criada. Senhas temporárias são a opção mais segura.',
        ],
        [
            'key'         => 'security.force_password_change',
            'name'        => 'Exigir troca de senha no primeiro acesso',
            'type'        => 'boolean',
            'value'       => '1',
            'options'     => null,
            'description' => 'Obriga o usuário a trocar a senha inicial antes de utilizar o sistema.',
        ],
    ];

    /**
     * Remove os parâmetros (o histórico de alterações é apagado em cascata).
     *
     * @return void
     */
    public function up(): void {
        DB::table('system_parameters')
            ->whereIn('key', array_column($this->parameters, 'key'))
            ->delete();
    }

    /**
     * Recria os parâmetros com os valores padrão.
     *
     * @return void
     */
    public function down(): void {
        foreach ($this->parameters as $parameter) {
            DB::table('system_parameters')->insertOrIgnore([
                'key'           => $parameter['key'],
                'name'          => $parameter['name'],
                'category'      => 'Segurança e usuários',
                'type'          => $parameter['type'],
                'value'         => $parameter['value'],
                'default_value' => $parameter['value'],
                'options'       => $parameter['options'] ? json_encode($parameter['options']) : null,
                'description'   => $parameter['description'],
                'is_active'     => true,
                'is_system'     => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

};
