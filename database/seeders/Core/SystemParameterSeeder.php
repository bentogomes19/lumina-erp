<?php

namespace Database\Seeders\Core;

use App\Enums\SystemParameterType;
use App\Models\SystemParameter;
use Illuminate\Database\Seeder;

class SystemParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            ['key' => 'institution.name', 'name' => 'Nome da instituição', 'category' => 'Identidade e personalização', 'type' => SystemParameterType::TEXT, 'value' => 'Portal Lumina', 'description' => 'Nome exibido no painel administrativo, portais, PDFs e comunicações do sistema.'],
            ['key' => 'institution.logo', 'name' => 'Logo principal', 'category' => 'Identidade e personalização', 'type' => SystemParameterType::FILE, 'value' => null, 'description' => 'Imagem usada na identificação visual do painel administrativo e dos portais.'],
            ['key' => 'institution.document_logo', 'name' => 'Logo para documentos', 'category' => 'Identidade e personalização', 'type' => SystemParameterType::FILE, 'value' => null, 'description' => 'Versão da logo usada em boletins, declarações, relatórios e documentos impressos.'],
            ['key' => 'institution.primary_color', 'name' => 'Cor institucional', 'category' => 'Identidade e personalização', 'type' => SystemParameterType::TEXT, 'value' => '#3D5A80', 'description' => 'Cor principal usada nos elementos de destaque da identidade visual.'],
            ['key' => 'system.display_name', 'name' => 'Nome do sistema', 'category' => 'Identidade e personalização', 'type' => SystemParameterType::TEXT, 'value' => 'Lumina ERP', 'description' => 'Nome exibido no título das páginas, mensagens e documentos gerados.'],
            ['key' => 'security.initial_password_rule', 'name' => 'Regra da senha inicial', 'category' => 'Segurança e usuários', 'type' => SystemParameterType::SELECT, 'value' => 'temporary', 'options' => ['temporary', 'registration', 'cpf_last_digits'], 'description' => 'Define como a senha inicial de novos acessos será criada. Senhas temporárias são a opção mais segura.'],
            ['key' => 'security.force_password_change', 'name' => 'Exigir troca de senha no primeiro acesso', 'category' => 'Segurança e usuários', 'type' => SystemParameterType::BOOLEAN, 'value' => '1', 'description' => 'Obriga o usuário a trocar a senha inicial antes de utilizar o sistema.'],
            ['key' => 'security.password_min_length', 'name' => 'Tamanho mínimo da senha', 'category' => 'Segurança e usuários', 'type' => SystemParameterType::INTEGER, 'value' => '8', 'description' => 'Quantidade mínima de caracteres aceita para novas senhas.'],
            ['key' => 'security.password_expiration_days', 'name' => 'Expiração da senha', 'category' => 'Segurança e usuários', 'type' => SystemParameterType::INTEGER, 'value' => '0', 'description' => 'Número de dias para expirar a senha. Use zero para não expirar automaticamente.'],
            ['key' => 'security.login_attempts', 'name' => 'Tentativas inválidas antes do bloqueio', 'category' => 'Segurança e usuários', 'type' => SystemParameterType::INTEGER, 'value' => '5', 'description' => 'Quantidade de tentativas incorretas antes do bloqueio temporário do acesso.'],
            ['key' => 'security.session_minutes', 'name' => 'Tempo de sessão', 'category' => 'Segurança e usuários', 'type' => SystemParameterType::INTEGER, 'value' => '120', 'description' => 'Tempo, em minutos, que uma sessão pode permanecer ativa sem atividade.'],
            ['key' => 'enrollment.number_format', 'name' => 'Formato do número da matrícula', 'category' => 'Matrículas e cadastros', 'type' => SystemParameterType::SELECT, 'value' => 'MAT-{year}-{sequence}', 'options' => ['MAT-{year}-{sequence}', '{year}{sequence}', 'MAT-{sequence}'], 'description' => 'Máscara utilizada para gerar automaticamente números de matrícula.'],
            ['key' => 'student.number_format', 'name' => 'Formato do número do aluno', 'category' => 'Matrículas e cadastros', 'type' => SystemParameterType::SELECT, 'value' => 'ALU-{year}-{sequence}', 'options' => ['ALU-{year}-{sequence}', 'ALU-{sequence}', '{year}-{sequence}'], 'description' => 'Máscara usada para identificar alunos de forma padronizada.'],
            ['key' => 'class.number_format', 'name' => 'Formato do número da turma', 'category' => 'Matrículas e cadastros', 'type' => SystemParameterType::SELECT, 'value' => 'TUR-{year}-{grade}-{sequence}', 'options' => ['TUR-{year}-{grade}-{sequence}', 'TUR-{year}-{sequence}', '{grade}-{shift}-{sequence}'], 'description' => 'Regra que define o código automático de cada turma.'],
            ['key' => 'teacher.number_format', 'name' => 'Formato da matrícula funcional', 'category' => 'Matrículas e cadastros', 'type' => SystemParameterType::SELECT, 'value' => 'PROF-{sequence}', 'options' => ['PROF-{sequence}', 'FUNC-{year}-{sequence}', '{sequence}'], 'description' => 'Máscara usada para gerar a identificação funcional de professores.'],
            ['key' => 'enrollment.allow_without_guardian', 'name' => 'Permitir matrícula sem responsável', 'category' => 'Matrículas e cadastros', 'type' => SystemParameterType::BOOLEAN, 'value' => '0', 'description' => 'Define se alunos menores podem concluir o cadastro sem informações do responsável.'],
            ['key' => 'academic.minimum_grade', 'name' => 'Média mínima para aprovação', 'category' => 'Regras acadêmicas', 'type' => SystemParameterType::DECIMAL, 'value' => '6.0', 'description' => 'Nota mínima utilizada pelo cálculo de aprovação do aluno.'],
            ['key' => 'academic.minimum_attendance', 'name' => 'Frequência mínima obrigatória', 'category' => 'Regras acadêmicas', 'type' => SystemParameterType::DECIMAL, 'value' => '75.0', 'description' => 'Percentual mínimo de presença exigido para aprovação por frequência.'],
            ['key' => 'academic.term_model', 'name' => 'Modelo do período letivo', 'category' => 'Regras acadêmicas', 'type' => SystemParameterType::SELECT, 'value' => 'bimestre', 'options' => ['bimestre', 'trimestre', 'semestre'], 'description' => 'Define a organização dos períodos avaliativos e a nomenclatura exibida nos portais.'],
            ['key' => 'academic.default_class_capacity', 'name' => 'Capacidade padrão das turmas', 'category' => 'Regras acadêmicas', 'type' => SystemParameterType::INTEGER, 'value' => '35', 'description' => 'Quantidade padrão de vagas sugerida ao criar uma nova turma.'],
        ];

        foreach ($parameters as $parameter) {
            $parameter['type'] = $parameter['type']->value;
            $parameter['default_value'] = $parameter['value'];
            $parameter['is_active'] = true;
            $parameter['is_system'] = true;
            SystemParameter::firstOrCreate(['key' => $parameter['key']], $parameter);
        }
    }
}
