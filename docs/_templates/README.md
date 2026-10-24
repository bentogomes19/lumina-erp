# Templates de documentação

Copie o template adequado, renomeie e preencha. Remova as seções que não se aplicam em vez de deixá-las vazias.

| Template | Use para | Pasta de destino | Nome do arquivo |
| --- | --- | --- | --- |
| [modulo.md](./modulo.md) | Funcionamento e regras de um módulo (portal, matrícula, notas) | `04-modulos/` | `nome-do-modulo.md` |
| [guia.md](./guia.md) | Passo a passo de instalação, operação ou implantação | `02-guias/` | `verbo-ou-tema.md` |
| [adr.md](./adr.md) | Decisão técnica relevante e seus motivos | `03-arquitetura/adr/` | `NNNN-titulo-curto.md` |
| [auditoria.md](./auditoria.md) | Levantamento ou análise datada do código | `06-historico/` | `AAAA-MM-DD-tema.md` |
| [registro-validacao.md](./registro-validacao.md) | Rodada de testes ou ensaio manual | seção nova em `05-qualidade/validacao-demonstracao.md` | — |
| [tarefa.md](./tarefa.md) | Funcionalidade ou melhoria (faixas da empresa) | tarefas da empresa ou `.dev-tasks/` | `CODIGO-titulo.md` |
| [bug.md](./bug.md) | Defeito com análise e correção | tarefas da empresa ou `.dev-tasks/` | `CODIGO-titulo.md` |
| [reuniao.md](./reuniao.md) | Ata de reunião | tarefas da empresa ou `.dev-tasks/` | `AAAA-MM-DD-tema.md` |

**Formatação:** emojis, rótulos e faixas HTML, avisos e Mermaid seguem o [padrão de formatação Markdown](./padrao-markdown.md). Veja os templates preenchidos em [exemplos/](./exemplos/).

## Metadados obrigatórios

Todo documento em `docs/` começa com um bloco YAML (o GitHub exibe como tabela). O `README.md` da raiz é a exceção: é a página inicial do repositório e não leva metadados.

```yaml
---
titulo: "Título igual ao H1"
status: vigente        # vigente | revisar | rascunho | historico
atualizado_em: AAAA-MM-DD
responsavel: usuario-github
---
```

- **vigente**: descreve o comportamento atual do código.
- **revisar**: tem trechos desatualizados conhecidos; explique no aviso logo abaixo do título.
- **rascunho**: em elaboração, não usar como referência.
- **historico**: retrato datado; não é atualizado, apenas substituído por um documento novo.

## Convenções

- Nomes de arquivo em português, `kebab-case`, sem acentos: `portal-do-professor.md`.
- Um H1 por arquivo, igual a `titulo`. Seções numeradas só em documentos longos.
- Links sempre relativos (`../04-modulos/matriculas.md`), nunca para `github.com/.../blob`.
- Regras com identificador estável: `RN-<MOD>-001` (negócio), `RF-<MOD>-001` (funcional), `RNF-<MOD>-001` (não funcional). Siglas: `ALU`, `PROF`, `MAT`, `FREQ`, `NOTA`, `ADM`.
- Separe sempre **o que existe** de **o que é proposta**. Propostas vão para o backlog ou para a seção “Evolução” no fim do documento.
- Avisos com a sintaxe do GitHub: `> [!NOTE]`, `> [!WARNING]`, `> [!CAUTION]`. O GitHub remove o `style=` do HTML; veja no [padrão](./padrao-markdown.md) onde usar os blocos coloridos da empresa.
- Imagens em `docs/assets/<pasta-do-documento>/`, com texto alternativo.
- Nunca registre senhas, tokens, IPs de servidor ou dados reais de alunos.
- Ao alterar uma regra no código, atualize o documento do módulo no mesmo pull request e a data `atualizado_em`.
