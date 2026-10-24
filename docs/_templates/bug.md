---
titulo: "CODIGO — Descrição curta do defeito"
status: rascunho
atualizado_em: AAAA-MM-DD
responsavel: usuario-github
---

# CODIGO — Descrição curta do defeito

<!-- Status (opcional): cole uma das faixas abaixo no topo quando aplicável e apague as demais.
<div style="background-color: #c0392b; color: white; padding: 5px; text-align: center;"><b>TAREFA TRAVADA, PRECISA SANAR DÚVIDAS</b></div>
<div style="background: #8e44ad; color: white; padding: 5px; text-align: center; font-weight: bold;">ESTA TAREFA PRECISA DE DOCUMENTAÇÃO</div>
<div style="background: #2980b9; color: white; padding: 5px; text-align: center; font-weight: bold;">👍 DÚVIDAS SANADAS, OBRIGADO!</div>
-->

> **Módulo:** módulo · **Gravidade:** crítica | média | leve · **Ambiente:** local | demo | homologação · **Commit:** `abc1234`

<div style="background: #e0f7fa; border-style: double; color: #00796b; padding: 5px; text-align: center; border-color: #00796b;">🤔 <b>DESCRITIVO DO PROBLEMA</b></div>

## 🤔 Descritivo do problema

**Esperado:** …
**Observado:** …

Passos para reproduzir:

1. Acessar `/rota` como perfil.
2. …

<details>
<summary>Log / stack trace</summary>

```text
cole aqui
```

</details>

<div style="background: #fcebbd; border-style: double; color: #d69a2e; padding: 5px; text-align: center; border-color: #d69a2e;">🔍 <b>ANÁLISE</b></div>

## 🔍 Análise

<span style="color: #8e44ad;">**[ 📝 CONTEXTO ]**</span> Causa identificada, com `arquivo:linha`.

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> Regra que foi violada (**RN-XXX-000**).

<div style="background: #f1e3ec; border-style: double; color: #6F1E51; padding: 5px; text-align: center; border-color: #6F1E51;">🫵 <b>PROPOSTAS DE DESENVOLVIMENTO</b></div>

## 🫵 Propostas de desenvolvimento

| Opção | Prós | Contras | Escolhida |
| --- | --- | --- | :---: |
| A | | | ✅ |
| B | | | |

<div style="background: #e0f7fa; border-style: double; color: #00796b; padding: 5px; text-align: center; border-color: #00796b;">🧪 <b>CASOS DE TESTES</b></div>

## 🧪 Casos de testes

| ID | Cenário | Esperado |
| --- | --- | --- |
| CT-01 | Reproduz o defeito (teste de regressão) | passa após a correção |

<div style="background: #e1efe6; border-style: double; color: #009432; padding: 5px; text-align: center; border-color: #009432;">✅ <b>CRITÉRIOS DE ACEITE</b></div>

## ✅ Critérios de aceite

- [ ] Defeito não se reproduz.
- [ ] Teste de regressão adicionado.
- [ ] Sem regressão na suíte completa.
