---
titulo: "Documentação do Lumina ERP"
status: vigente
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# Documentação do Lumina ERP

**Estado atual:** protótipo funcional para demonstração; não deve receber dados reais antes da homologação. A situação detalhada e os números de teste mais recentes ficam no [relatório de prontidão](./05-qualidade/relatorio-prontidao.md). Não há módulo financeiro operacional nem API pública de negócio.

## 🧭 Comece aqui

| Preciso… | Leia |
| --- | --- |
| Entender o produto | [Visão geral e escopo](./01-produto/visao-geral.md) |
| Aprender a usar o sistema | [Atores, perfis e portais](./04-modulos/atores-e-perfis.md) e os manuais de cada portal |
| Rodar o projeto localmente | [Ambiente de desenvolvimento](./02-guias/ambiente-dev.md) |
| Apresentar o sistema | [Validação e roteiro de demonstração](./05-qualidade/validacao-demonstracao.md) |
| Saber se está pronto para produção | [Relatório de prontidão](./05-qualidade/relatorio-prontidao.md) |

## 🛠️ Estrutura

```text
docs/
├── README.md            ← este índice
├── 01-produto/          o que é o sistema, perfis, escopo
├── 02-guias/            passo a passo: ambiente, implantação, operação
├── 03-arquitetura/      banco de dados e decisões técnicas (adr/)
├── 04-modulos/          regras e fluxos atuais de cada módulo
├── 05-qualidade/        validações, ensaios e prontidão
├── 06-historico/        auditorias datadas (não são atualizadas)
├── _templates/          modelos para novos documentos
└── assets/              imagens e diagramas
```

## 🗂️ Índice

### 01 · Produto

- [Visão geral e escopo](./01-produto/visao-geral.md)

### 02 · Guias

- [Ambiente de desenvolvimento](./02-guias/ambiente-dev.md)
- [Implantação da demonstração (GitHub Actions)](./02-guias/implantacao-demo.md)
- [Preparação do release de homologação](./02-guias/release-homologacao.md)
- [Permissões diretas excepcionais](./02-guias/permissoes-diretas.md)

### 03 · Arquitetura

- [Modelo relacional](./03-arquitetura/modelo-relacional.md) — ⚠️ revisar
- [Decisões de arquitetura (ADR)](./03-arquitetura/adr/README.md)

### 04 · Módulos

**Manuais de uso** (com prints das telas)

- [Atores, perfis e portais](./04-modulos/atores-e-perfis.md) — comece por aqui
- [Manual do portal administrativo](./04-modulos/manual-portal-administrativo.md)
- [Manual do portal do professor](./04-modulos/manual-portal-do-professor.md)
- [Manual do portal do aluno](./04-modulos/manual-portal-do-aluno.md)

**Regras técnicas**

- [Matrículas](./04-modulos/matriculas.md)
- [Portal do aluno](./04-modulos/portal-do-aluno.md) — ⚠️ revisar
- Portal do professor — *pendente; veja a [auditoria de 23/08/2026](./06-historico/2026-08-23-auditoria-portal-professor.md)*
- [Frequência](./04-modulos/frequencia.md) — ⚠️ revisar
- [Dias letivos](./04-modulos/dias-letivos.md) — ⚠️ revisar

### 05 · Qualidade

- [Validação e roteiro de demonstração](./05-qualidade/validacao-demonstracao.md)
- [Relatório de prontidão](./05-qualidade/relatorio-prontidao.md)

### 06 · Histórico

- [Auditoria do portal do professor — 23/08/2026](./06-historico/2026-08-23-auditoria-portal-professor.md)
- [Análise geral e fluxos de cadastro — 23/08/2026](./06-historico/2026-08-23-analise-geral-e-fluxos-de-cadastro.md)
- [Revisão da documentação — 24/10/2026](./06-historico/2026-10-24-revisao-da-documentacao.md)

## ✍️ Como contribuir com a documentação

Use os [templates](./_templates/README.md) e o [padrão de formatação](./_templates/padrao-markdown.md), seguindo as convenções descritas lá: metadados no topo, nomes em `kebab-case`, links relativos e separação entre o que existe e o que é proposta. Ao mudar uma regra no código, atualize o documento do módulo no mesmo pull request.
