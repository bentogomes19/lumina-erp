---
titulo: "LUM-098 — Notas em rascunho visíveis no portal do aluno (exemplo)"
status: historico
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# LUM-098 — Notas em rascunho visíveis no portal do aluno (exemplo)

<div style="background: #2980b9; color: white; padding: 5px; text-align: center; font-weight: bold;">👍 DÚVIDAS SANADAS, OBRIGADO!</div>

> **Módulo:** Portal do aluno · **Gravidade:** crítica · **Ambiente:** local · **Corrigido em:** 20/10/2026
> <sub>Exemplo do template de bug baseado na correção registrada em validação e demonstração. Código da tarefa fictício.</sub>

<div style="background: #e0f7fa; border-style: double; color: #00796b; padding: 5px; text-align: center; border-color: #00796b;">🤔 <b>DESCRITIVO DO PROBLEMA</b></div>

## 🤔 Descritivo do problema

**Esperado:** o aluno vê apenas notas publicadas.
**Observado:** notas salvas como rascunho pelo professor apareciam no dashboard e em Minhas Notas, e as médias divergiam entre dashboard, disciplinas e boletim.

Passos para reproduzir:

1. Como professor, lançar notas de uma avaliação e salvar **sem publicar**.
2. Como aluno da turma, abrir o dashboard em `/aluno`.
3. A nota em rascunho aparece.

<div style="background: #fcebbd; border-style: double; color: #d69a2e; padding: 5px; text-align: center; border-color: #d69a2e;">🔍 <b>ANÁLISE</b></div>

## 🔍 Análise

<span style="color: #8e44ad;">**[ 📝 CONTEXTO ]**</span> As consultas do portal não filtravam `Grade.locked_at`, e cada tela calculava a média de um jeito. O cache do dashboard também não era invalidado na publicação.

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> Violava **RN-ALU-006 — Média oficial no serviço** e o isolamento entre rascunho e publicação.

<div style="background: #f1e3ec; border-style: double; color: #6F1E51; padding: 5px; text-align: center; border-color: #6F1E51;">🫵 <b>PROPOSTAS DE DESENVOLVIMENTO</b></div>

## 🫵 Propostas de desenvolvimento

| Opção | Prós | Contras | Escolhida |
| --- | --- | --- | :---: |
| Filtrar `locked_at` em cada tela | Rápido | Repete a regra, fácil esquecer | |
| Centralizar consulta e média no serviço do boletim | Uma regra para todas as telas | Refatoração maior | ✅ |

<div style="background: #e0f7fa; border-style: double; color: #00796b; padding: 5px; text-align: center; border-color: #00796b;">🧪 <b>CASOS DE TESTES</b></div>

## 🧪 Casos de testes

| ID | Cenário | Esperado |
| --- | --- | --- |
| CT-01 | Rascunho em todas as páginas e no PDF | Não aparece |
| CT-02 | Pesos e recuperação | Mesma média em todas as telas |
| CT-03 | Salvar rascunho → publicar | Dashboard atualiza (cache invalidado) |

<div style="background: #e1efe6; border-style: double; color: #009432; padding: 5px; text-align: center; border-color: #009432;">✅ <b>CRITÉRIOS DE ACEITE</b></div>

## ✅ Critérios de aceite

- [x] Rascunho invisível em dashboard, notas, disciplinas, detalhe, widgets e boletim.
- [x] Suíte completa: 297 testes, 1.939 asserções, sem falhas.
