---
titulo: "Validação e roteiro de demonstração"
status: vigente
atualizado_em: 2026-10-20
responsavel: bentogomes19
---

# Validação e roteiro de demonstração

## Correção de publicação e média — 20/10/2026

O portal do aluno passou a consultar somente notas publicadas (`Grade.locked_at` preenchido) no dashboard, em Minhas Notas, Minhas Disciplinas, detalhe da disciplina, widgets e boletim em PDF. Todas as médias usam a regra do boletim: ponderação por peso no bimestre, recuperação pelo melhor resultado e média anual dos bimestres disponíveis; a média geral do aluno é a média das médias anuais das disciplinas com nota. Publicar notas invalida o cache do dashboard dos alunos envolvidos.

Os testes funcionais cobrem rascunho invisível em todas as páginas principais e no boletim, cálculo com pesos e recuperação, além do ciclo salvar rascunho → publicar → atualizar dashboard. A suíte completa no contêiner passou com **297 testes e 1.939 asserções**, sem erros ou falhas. Esta rodada foi automatizada; a navegação manual registrada abaixo ocorreu em 14 e 15/10.

## Ensaio autenticado em base recriada — 14 e 15/10/2026

Ambiente local de demonstração recriado com `migrate:fresh --seed --force` (59 migrações). Após a carga: 173 usuários, 156 alunos, 16 professores, 45 turmas e 540 matrículas. Os três contêineres (`app`, `nginx`, `db`) ficaram ativos, com MySQL saudável. O ensaio foi feito no navegador integrado, com contas sintéticas dos perfis administrativo, docente e discente. O banco `testing` da suíte é separado da base de demonstração.

| Etapa e perfil | Esperado | Observado |
| --- | --- | --- |
| Secretaria: criar matrícula em turma com vaga | Matrícula ativa e ocupação +1 | Matrícula `2026000541` criada; turma 9° ANO A passou de 12 para 13/35. PDF de comprovação gerado em A4, com aluno, turma, ano e número corretos. |
| Secretaria: trancar e reativar | Liberar e retomar a vaga | Ambas as ações retornaram sucesso. A matrícula original ficou trancada e depois ativa. |
| Secretaria: criar turma e atribuir docente | Turma utilizável no cadastro e no portal | Turma sintética de capacidade 1 criada; docente atribuído à disciplina Língua Portuguesa. A criação inicialmente falhou por ausência de UUID, corrigida no modelo e retestada. |
| Secretaria: transferir matrícula | Ocupação sai da origem e entra no destino; histórico preservado | Nova matrícula `2026000542` ativa na turma de destino (1/1); `2026000541` ficou transferida. A turma de origem voltou a 12/35. |
| Secretaria: matrícula em turma cheia | Rejeição sem criar outra matrícula | Rejeitada, com aviso visível da capacidade; total permaneceu em 542 matrículas. O aviso faltava na primeira tentativa e foi corrigido. |
| Secretaria: matrícula em ano encerrado | Rejeição sem criar matrícula | Rejeitada com mensagem explícita após adicionar a guarda no serviço; total permaneceu em 542. |
| Docente: turma, avaliação, notas e frequência | Publicação e presença/falta persistidas | Turma e lista de 12 alunos carregaram. Avaliação `1801` criada; 12 notas salvas em rascunho e publicadas; 12 frequências lançadas (11 presenças, 1 falta). O tipo da nota, antes salvo como `test` para avaliação `activity`, foi corrigido e retestado. |
| Discente: notas, frequência, disciplinas e calendário | Mostrar apenas dados da matrícula ativa | Aluno da turma docente viu nota publicada 8,0 e a nova frequência. Aluno transferido passou a ver a turma de destino e sua disciplina. O portal exibia a turma antiga e foi corrigido. |
| Discente: cálculo de frequência | Mesma regra nas telas: presente + atraso sobre total | Página de frequência e cartões de disciplinas passaram a concordar com o dashboard: 92,5% no aluno de demonstração (770 registros). Faltas justificadas contam no total, não como presença. |
| Restrição de documento | Exigir autenticação/autorização | Acesso anônimo ao PDF retornou HTTP 302 para `/login`; aluno tentando abrir PDF administrativo de outra matrícula também foi redirecionado para login. Não houve exposição do documento. Esses acessos não retornaram 403 no ensaio. |

Validação automatizada após as correções: `php -d memory_limit=512M ./vendor/bin/phpunit --no-progress` no contêiner, **294 testes, 1.915 asserções, sem erro, falha ou teste arriscado**. O ensaio usou `MAIL_MAILER=log`; envio e recebimento por provedor real, HTTPS e restauração de backup ainda pertencem à homologação. Dados e documentos deste ensaio são sintéticos.

**Resultado:** roteiro autenticado dos três portais concluído na base local recriada. As falhas observadas nos fluxos exercitados foram corrigidas e retestadas. A autorização por perfil dos PDFs permanece coberta pela suíte, enquanto o ensaio manual confirmou um PDF administrativo e os redirecionamentos de acesso negado.

## Atualização da validação — 14/10/2026

Uma nova execução da suíte dentro do contêiner (`docker compose exec -T app php -d memory_limit=512M ./vendor/bin/phpunit --no-progress`) registrou **281 testes, 1.815 asserções, 9 erros, 30 falhas e 25 testes arriscados**. A variação em relação à execução de 06/10/2026 exige corrigir o isolamento e as massas de dados dos testes antes de usar a suíte como evidência de aprovação. Entre as 30 falhas, 25 pertencem aos testes de autorização de PDF e usam perfis criados sem permissões correspondentes; esses casos precisam ser corrigidos e então validados contra a aplicação.

Os contêineres estavam ativos, o banco estava saudável, as migrações estavam aplicadas e `/lumina/login` respondeu HTTP 200. Esses sinais confirmam a disponibilidade do ambiente de demonstração, não a prontidão para produção. A análise por gravidade e os critérios de entrega constam em [Relatório de prontidão](./relatorio-prontidao.md).

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
