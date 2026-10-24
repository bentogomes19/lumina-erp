# Relatório de prontidão do Lumina ERP

## Atualização técnica — 20/10/2026

Foi corrigida a exposição de notas em rascunho no portal do aluno e unificada a média entre dashboard, disciplinas, detalhe e boletim. A publicação de notas também invalida o cache do dashboard. A suíte completa passou com **297 testes e 1.939 asserções**. O percentual de 75% da revisão de 15/10 permanece como estimativa até a preparação e o aceite do release de homologação; ainda não há evidência de HTTPS, e-mail real, backup/restauração ou operação monitorada nesse ambiente.

**Próximo marco:** criar o release de homologação separado da base demo seguindo o [roteiro de preparação](devops/release-homologacao.md), validar a configuração de produção, e-mail, filas, armazenamento e restauração; depois repetir smoke test e regressão no ambiente implantado.

## Revisão de prontidão — 15/10/2026

**Estimativa atual: 75% concluído; cerca de 25% do trabalho de entrega permanece.** O avanço decorre da suíte integralmente verde e do roteiro autenticado concluído em uma base local recriada. É uma estimativa de maturidade, não a porcentagem de testes aprovados. A publicação com dados reais continua condicionada à homologação operacional.

| Área | Peso | Maturidade estimada | Contribuição |
| --- | ---: | ---: | ---: |
| Funcionalidades e integração dos três portais | 40% | 88% | 35,2 |
| Regras acadêmicas e controle de acesso | 25% | 72% | 18,0 |
| Testes e confiabilidade | 15% | 80% | 12,0 |
| Implantação, segurança operacional e recuperação | 15% | 35% | 5,3 |
| Documentação e demonstração | 5% | 90% | 4,5 |
| **Total arredondado** | **100%** | | **75%** |

Evidências novas:

- Base de demonstração recriada em 14/10 com 59 migrações e dados sintéticos. O [registro do ensaio autenticado](validacao-demonstracao.md) documenta operações administrativas, docentes e discentes, incluindo PDF, transferência, turma cheia, ano encerrado, notas publicadas e frequência.
- As falhas observadas no ensaio foram corrigidas e retestadas: criação de turma sem UUID, ausência de aviso na matrícula rejeitada, matrícula em ano encerrado, status do aluno após nova matrícula, seleção da turma ativa depois da transferência, cálculo de frequência e tipo de nota de uma avaliação `activity`.
- PHPUnit completo, após essas correções: **294 testes, 1.915 asserções, sem erro, falha ou teste arriscado** (`php -d memory_limit=512M ./vendor/bin/phpunit --no-progress` no contêiner). A base `testing` é separada da demonstração.
- Acesso anônimo ao PDF redirecionou para login (HTTP 302); o portal discente também negou o PDF administrativo de outra matrícula por redirecionamento. O ensaio não mediu envio/recebimento por e-mail real, HTTPS, backup/restauração nem desempenho de produção.

**Próxima etapa crítica:** preparar um release de homologação separado da base demo: HTTPS, `APP_DEBUG=false`, segredos e banco sem exposição pública, provedor de e-mail real, armazenamento persistente, worker/monitoramento e backup com restauração ensaiada. Depois, repetir smoke test e regressão nesse ambiente antes de autorizar produção.

## Histórico da revisão de 14/10/2026

**Data da revisão:** 14/10/2026  
**Escopo:** aplicação, regras acadêmicas, acesso, testes e implantação.  
**Estado:** demonstração local disponível; entrega técnica confiável e produção ainda bloqueadas.

## Estimativa de conclusão

**62% concluído; cerca de 38% do trabalho de entrega permanece.** É uma estimativa de maturidade, não a proporção de testes aprovados nem uma garantia matemática de ausência de defeitos. A nota considera o que está implementado, o que foi verificado e o trabalho operacional necessário para publicar o sistema com segurança.

| Área | Peso | Maturidade estimada | Contribuição |
| --- | ---: | ---: | ---: |
| Funcionalidades e integração dos três portais | 40% | 80% | 32,0 |
| Regras acadêmicas e controle de acesso | 25% | 55% | 13,8 |
| Testes e confiabilidade | 15% | 45% | 6,8 |
| Implantação, segurança operacional e recuperação | 15% | 35% | 5,3 |
| Documentação e demonstração | 5% | 75% | 3,8 |
| **Total arredondado** | **100%** | | **62%** |

Os percentuais por área são julgamento técnico baseado nas evidências abaixo. Não existe amostra suficiente para quantificar todas as falhas possíveis de regra de negócio; a meta de entrega deve ser uma suíte crítica aprovada e fluxos reais ensaiados, não a promessa de “zero erros”.

## Evidência verificada

- A pilha local `app`, `nginx` e `db` subiu; o MySQL ficou saudável e `/lumina/login` respondeu HTTP 200. As migrations existentes estão aplicadas na base local.
- PHPUnit no contêiner, com `memory_limit=512M`: **281 testes, 1.815 asserções, 9 erros, 30 falhas e 25 testes arriscados**. A suíte terminou com código de erro 2. Em execução anterior, houve 8 erros e 30 falhas; a variação reforça a necessidade de isolamento de dados de teste.
- Das 30 falhas, 25 são cenários de emissão autorizada de documentos de matrícula que receberam HTTP 403. O teste cria papéis sem lhes atribuir as permissões que a `EnrollmentPolicy` exige. Isso aponta primeiro para uma preparação inadequada do teste; a autorização real ainda precisa ser validada com permissões corretas e com usuários autenticados.
- Duas falhas envolvem recuperação/primeiro acesso; uma envolve a tela administrativa de capacidade; uma é um teste genérico que espera 200 na raiz, embora a aplicação redirecione; uma é um caso de duplicidade docente cujo dado de e-mail não reproduz o e-mail inicial.
- Os nove erros incluem cinco tentativas de criar permissões já existentes, dois cenários de capacidade/matrícula, um parâmetro sem o campo obrigatório `description` e uma colisão da chave normalizada de disciplina em teste de exclusão.

## Crítico — impede a liberação

| Pendência | Evidência | Critério de conclusão |
| --- | --- | --- |
| Validar matrícula, rematrícula, ocupação e liberação de vagas | `tests/Feature/Enrollments/EnrollmentCapacityTest.php` produz dois erros e uma falha de acesso à tela. Há regra dependente do ano corrente em `app/Models/SchoolYear.php`. | Corrigir o comportamento ou os dados de teste conforme a regra aprovada; testar turma cheia, cancelamento, reativação, transferência e mudança de ano; zero erros nesses cenários. |
| Concluir recuperação de senha e primeiro acesso | Duas falhas em `tests/Feature/Auth/FirstAccessTest.php`; `.env.example` usa `MAIL_MAILER=log`. | Testes aprovados e envio/recebimento de e-mail ensaiado com provedor real de homologação. |
| Comprovar a autorização dos PDFs para cada perfil | 25 falhas 403 em `tests/Feature/Pdf/EnrollmentPdfAuthorizationTest.php`; o fixture só atribui papéis, enquanto `app/Policies/EnrollmentPolicy.php` verifica permissões. | Refazer fixture com matriz de permissões esperada; aprovar os casos autorizados e os negados; gerar ao menos um PDF real por tipo em ensaio autenticado. |
| Criar uma implantação própria para produção | `compose.yaml` monta o código e `~/.ssh`, publica MySQL em todas as interfaces e expõe nginx apenas em HTTP; `.env.example` liga debug e usa credenciais de desenvolvimento. | Imagem e Compose/infra próprios, HTTPS, segredos externos, banco sem porta pública, `APP_ENV=production`, `APP_DEBUG=false`, e verificação de configuração antes do primeiro tráfego. |
| Definir recuperação dos dados operacionais | O repositório não apresenta rotina de backup e restauração ensaiada para MySQL e arquivos. | Backup automático com retenção definida e restauração testada em ambiente separado. |

## Médio — necessário para entrega estável

| Pendência | Evidência | Critério de conclusão |
| --- | --- | --- |
| Reparar isolamento e fixtures de teste | Cinco erros de permissões duplicadas, colisão de disciplina e parâmetro obrigatório; resultado variou entre execuções. | Suíte repetível em banco de teste limpo, sem dependência da ordem ou do ano em que roda. |
| Conferir o cadastro docente e unicidade de identidade | `test_onboarding_rejects_duplicate_teacher_identity` falha; o terceiro e-mail do teste não coincide com o criado em `teacherData()`. | Corrigir o caso de teste e ensaiar CPF, matrícula funcional e e-mail normalizados, inclusive registros excluídos logicamente. |
| Ensaiar manualmente os três portais | O HTTP 200 do login só comprova inicialização. As áreas administrativa, docente e discente precisam de navegação e operações reais. | Roteiro com criação de turma e matrícula, atribuição docente, lançamento/publicação de nota, frequência, consulta discente, documento e recuperação de acesso, com evidências e dados sintéticos. |
| Operacionalizar filas, e-mail, logs e monitoramento | `QUEUE_CONNECTION=database`; serviço de worker está comentado em `compose.yaml`; e-mail padrão vai para log. | Verificar se há tarefas enfileiradas, operar worker supervisionado quando necessário, configurar alertas, retenção de logs e monitoramento de disponibilidade. |
| Resolver exportações expostas mas ainda não implementadas | `app/Filament/Pages/Student/AcademicCalendar.php` apenas notifica “em breve” para PDF e iCalendar. | Implementar e testar ou remover as ações da interface e do escopo documentado. |
| Documentar implantação e resposta a incidentes | O `Makefile` `bootstrap` usa `migrate --seed --force`, mas `DatabaseSeeder` é restrito a `local`/`testing`. | Criar procedimento de produção com migrations, dados de referência mínimos, configuração, rollback e validação pós-implantação, sem carga escolar de demonstração. |

## Leve — acabamento e manutenção

| Pendência | Evidência | Critério de conclusão |
| --- | --- | --- |
| Ajustar teste genérico da rota inicial | `tests/Feature/ExampleTest.php` espera HTTP 200; `routes/web.php` redireciona para o login. | Verificar o redirecionamento esperado em vez de exigir 200. |
| Limpar testes arriscados de PDF | Os 25 casos com mock de PDF deixam handlers alterados segundo o PHPUnit. | Restaurar handlers e retirar os avisos após corrigir a autorização. |
| Padronizar estilo e textos auxiliares | Há pendências de formatação apontadas anteriormente pelo Pint e mensagens de interface ainda provisórias. | Pint aprovado e interface sem ações prometidas como disponíveis quando ainda não executam a função. |

## Ordem recomendada e marcos

1. **Regras e autenticação:** resolver matrícula/capacidade, primeiro acesso e a matriz de autorização dos PDFs; distinguir defeito de produto de fixture incorreto.
2. **Suíte confiável:** eliminar erros, falhas e testes arriscados relevantes; repetir a suíte em base limpa e executar o roteiro manual por perfil.
3. **Release de homologação:** preparar ambiente sem dados demo, com HTTPS, e-mail real, segredos, armazenamento, backup e monitoramento; testar restauração.
4. **Aceite:** executar smoke test e regressão após implantação, documentar resultados e somente então autorizar tráfego de produção.

**Estimativa de esforço:** aproximadamente **8 a 15 dias úteis** para uma pessoa familiarizada com Laravel/Filament, se a infraestrutura e o serviço de e-mail já estiverem disponíveis. Falhas novas descobertas ao corrigir os testes podem ampliar esse prazo. O percentual deve ser recalculado após o marco 2.

## Decisão

O Lumina ERP pode ser demonstrado em ambiente local controlado. **Não deve ser publicado com dados reais na configuração atual.** A condição mínima de liberação é a aprovação dos fluxos críticos de matrícula, acesso, notas/frequência e documentos, junto com uma implantação separada da configuração de desenvolvimento e uma restauração de backup comprovada.
