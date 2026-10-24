---
titulo: "Revisão da documentação — 24/10/2026"
status: historico
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# Revisão da documentação — 24/10/2026

> **Base:** arquivos de `docs/` e `README.md` em 24/10/2026 · **Escopo:** 16 documentos (≈3.800 linhas) · **Método:** leitura dos documentos e comparação com `composer.json` e `database/migrations`.

## Resumo

A documentação é rica em conteúdo técnico e honesta sobre limitações, mas estava organizada sem critério estável: arquivos soltos na raiz de `docs/`, nomes misturando idiomas e estilos, documentos históricos sem aviso no próprio arquivo e informações de estado (testes, versões) divergentes entre páginas. A reorganização em pastas numeradas, metadados e templates resolve a estrutura; os achados de conteúdo abaixo continuam pendentes.

| Gravidade | Quantidade |
| --- | ---: |
| Média | 6 |
| Leve | 6 |

## Achados

| ID | Gravidade | Achado | Evidência | Recomendação | Situação |
| --- | --- | --- | --- | --- | --- |
| A-01 | Média | README raiz dizia que a suíte não passava, mas a validação de 20/10 registra 297 testes aprovados | `README.md` × `validacao-demonstracao.md` | Manter o estado só no relatório de prontidão e apontar para ele | Corrigido no README |
| A-02 | Média | Modelo relacional desatualizado: diz 14 entidades, lista 13; faltam `school_years`, `lessons`, `school_holidays`, `enrollment_logs`, `enrollment_documents`, `class_subjects`, `grade_level_subject`, parâmetros do sistema | `database/migrations` (59 arquivos) | Completar ou gerar a partir do schema | Aviso adicionado; conteúdo pendente |
| A-03 | Média | `Assessment` sem `assessment_type`, `max_score`, `status`, `school_year_id` | `2026_05_02_000001_add_teacher_assessment_fields_to_assessments_table.php` | Atualizar a tabela da entidade | Pendente |
| A-04 | Média | Não há documento atual do portal do professor; o único (`teacher-portal.md`) é uma auditoria de 23/08 que descreve o portal dentro de `/lumina`, hoje em `/professor` | `teacher-portal.md`, `visao-geral.md` | Escrever `04-modulos/portal-do-professor.md` com o template de módulo | Pendente |
| A-05 | Média | Versões divergentes: análise geral cita PHP 8.2 e Filament 4; o projeto usa PHP ^8.4, Laravel ^12, Filament ^5 | `composer.json` | Versões só em `visao-geral.md`; demais documentos apontam para ela | Documento marcado como histórico |
| A-06 | Média | Validação e prontidão acumulam rodadas (06/10, 14/10, 15/10, 20/10) com números diferentes (281, 294, 297 testes); o leitor precisa descobrir qual vale | `validacao-demonstracao.md`, `relatorio-prontidao.md` | Bloco “Estado atual” fixo no topo e rodadas antigas abaixo, com o template `registro-validacao.md` | Pendente |
| A-07 | Leve | Frequência lista como “próximo passo” a interface de lançamento, que já existe | `sistema-de-frequencias.md` | Mover sugestões para backlog | Aviso adicionado |
| A-08 | Leve | Dias letivos usa exemplos e feriados de 2025 e uma seção “Via Interface (Futuro)” | `sistema-de-dias-letivos.md` | Atualizar exemplos e confirmar o que existe | Aviso adicionado |
| A-09 | Leve | `padrao_documentacao_github.md` é um padrão genérico de exercícios, não do Lumina; usa `<div style=…>`, que o GitHub remove | `padrao_documentacao_github.md` | Tirar do repositório (guardar em repositório pessoal) | Substituído por `_templates/` |
| A-10 | Leve | Índice não listava implantação da demo, release de homologação nem relatório de prontidão | `index.md` | Índice completo em `docs/README.md` | Corrigido |
| A-11 | Leve | Nomes inconsistentes (`teacher-portal`, `padrao_documentacao_github`, `sistema-de-…`) e pastas vazias (`figuras/`, `output/pdf/`) | `docs/` | `kebab-case` em português; `assets/` para imagens | Corrigido nos novos caminhos |
| A-12 | Leve | Sem metadados de status, data e responsável; histórico só sinalizado no índice | todos | Bloco YAML e avisos `> [!CAUTION]` | Corrigido |

## Fora do escopo

Não foi verificado se cada regra descrita corresponde ao código atual, nem se os comandos dos guias funcionam numa máquina limpa.

## Próximos passos

1. Apagar os arquivos antigos listados abaixo após conferir a nova estrutura.
2. Escrever o documento atual do portal do professor (A-04).
3. Atualizar o modelo relacional (A-02, A-03), de preferência com um diagrama Mermaid `erDiagram`.
4. Reestruturar validação e prontidão com “Estado atual” no topo (A-06).
5. Registrar as primeiras ADRs sugeridas em `03-arquitetura/adr/README.md`.
6. Opcional: checagem de links quebrados no workflow `source-check.yml` (por exemplo, `lychee` ou `markdown-link-check`).

## Migração de caminhos

| Antigo | Novo |
| --- | --- |
| `docs/index.md` | `docs/README.md` |
| `docs/visao-geral.md` | `docs/01-produto/visao-geral.md` |
| `docs/devops/ambiente-dev.md` | `docs/02-guias/ambiente-dev.md` |
| `docs/devops/implantacao-segura.md` | `docs/02-guias/implantacao-demo.md` |
| `docs/devops/release-homologacao.md` | `docs/02-guias/release-homologacao.md` |
| `docs/permissoes-diretas.md` | `docs/02-guias/permissoes-diretas.md` |
| `docs/banco-de-dados/modelo-relacional.md` | `docs/03-arquitetura/modelo-relacional.md` |
| `docs/regras-matricula.md` | `docs/04-modulos/matriculas.md` |
| `docs/portal-do-aluno.md` | `docs/04-modulos/portal-do-aluno.md` |
| `docs/sistema-de-frequencias.md` | `docs/04-modulos/frequencia.md` |
| `docs/sistema-de-dias-letivos.md` | `docs/04-modulos/dias-letivos.md` |
| `docs/validacao-demonstracao.md` | `docs/05-qualidade/validacao-demonstracao.md` |
| `docs/relatorio-final-prontidao.md` | `docs/05-qualidade/relatorio-prontidao.md` |
| `docs/teacher-portal.md` | `docs/06-historico/2026-08-23-auditoria-portal-professor.md` |
| `docs/analise-geral-e-fluxos-de-cadastro.md` | `docs/06-historico/2026-08-23-analise-geral-e-fluxos-de-cadastro.md` |
| `docs/padrao_documentacao_github.md` | removido (substituído por `docs/_templates/`) |
| `docs/figuras/` | `docs/assets/` |
