# Validação e roteiro de demonstração

## Evidência da validação — 06/10/2026

Ambiente: containers `lumina-app`, `lumina-nginx` e `lumina-db` ativos; PHP 8.4.26 e MySQL 8.4.11. Todas as 59 migrations constavam como aplicadas. O banco `lumina` continha, entre outros dados, 173 usuários, 156 alunos, 16 professores, 45 turmas, 540 matrículas e 1.800 avaliações. Esses números descrevem a base local examinada, não uma medição de produção.

| Verificação | Resultado |
| --- | --- |
| `npm run build` no container | Passou; Vite compilou 56 módulos |
| HTTP anônimo aos três painéis | `/lumina`, `/professor` e `/aluno` redirecionaram para login; as páginas de login responderam 200 |
| PHPUnit com `memory_limit=512M` | 281 testes, 1.826 asserções, 8 erros, 30 falhas e 25 testes arriscados |
| Laravel Pint `--test` | Falhou: 315 problemas de estilo em 381 arquivos examinados |

O comando `php artisan test` com o limite padrão de 128 MB encerrou durante a geração de PDF. Para obter o relatório completo, o PHPUnit foi executado diretamente com 512 MB. A memória necessária em uso real ainda precisa ser medida.

As falhas da suíte incluem 25 casos de PDF de matrícula que esperavam HTTP 200 e receberam 403, além de erros em preparação de permissões, capacidade/matrícula, parâmetro acadêmico e recuperação de senha. Uma falha pode decorrer de teste desatualizado ou defeito do produto; cada caso precisa ser investigado antes de declarar o fluxo aprovado. Os testes de PDF também geraram avisos de manipulação de handlers de erro/exception.

**Conclusão:** infraestrutura, assets, banco e páginas públicas de login foram comprovados. A demonstração ponta a ponta autenticada **não está validada** por esta rodada, e o sistema ainda não atende a um critério de suíte integralmente verde.

## Roteiro para ensaio manual

Use apenas dados sintéticos locais. Registre resultado e captura de cada etapa antes da banca.

1. Confirme `docker compose ps`, `migrate:status` e o build dos assets.
2. Em `/lumina/login`, entre com uma conta administrativa de demonstração; mostre indicadores, usuários, estrutura acadêmica, turma e matrícula. Crie uma matrícula em turma com vaga e confirme número, histórico e documento PDF.
3. Em `/professor/login`, entre com uma conta docente atribuída à turma. Abra a turma, crie uma avaliação, salve e publique notas e registre frequência de uma aula.
4. Em `/aluno/login`, entre com o aluno matriculado. Confira disciplinas, notas publicadas, frequência e calendário; tente acessar um registro de outro aluno para verificar isolamento.
5. Volte à secretaria e exerça uma transição de matrícula válida. Confirme reflexo no histórico e no portal do aluno.
6. Registre evidências das restrições: usuário sem permissão, turma lotada, ano fechado e acesso anônimo a documentos.

Contas previstas pelos seeders: `admin@lumina.com`, `professor@lumina.com` e `aluno@lumina.com`. A senha de demonstração é configurada por `SCHOOL_DEFAULT_PASSWORD` (padrão do código: `123456`); confirme o valor no seu `.env` sem divulgá-lo em capturas ou no TCC. Convites e redefinições usam `MAIL_MAILER=log` por padrão.

## Critério de conclusão para a banca

Antes de chamar o roteiro de aprovado, execute as seis etapas em uma base recriada para a apresentação, registre data, navegador, usuário/perfil, resultado esperado e observado, e trate as falhas que impedirem os fluxos centrais. Mantenha os resultados negativos documentados no TCC como limitações, sem atribuir aprovação a testes que falharam.
