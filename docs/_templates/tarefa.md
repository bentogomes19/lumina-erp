---
titulo: "CODIGO — Título da tarefa"
status: rascunho
atualizado_em: AAAA-MM-DD
responsavel: usuario-github
---

# CODIGO — Título da tarefa

<!-- Status (opcional): cole uma das faixas abaixo no topo quando aplicável e apague as demais.
<div style="background-color: #c0392b; color: white; padding: 5px; text-align: center;"><b>TAREFA TRAVADA, PRECISA SANAR DÚVIDAS</b></div>
<div style="background: #8e44ad; color: white; padding: 5px; text-align: center; font-weight: bold;">ESTA TAREFA PRECISA DE DOCUMENTAÇÃO</div>
<div style="background: #2980b9; color: white; padding: 5px; text-align: center; font-weight: bold;">👍 DÚVIDAS SANADAS, OBRIGADO!</div>
-->

> **Módulo:** módulo · **Tipo:** funcionalidade | melhoria · **Prioridade:** alta | média | baixa

<div style="background: #f4dfe5; border-style: double; color: #b71540; padding: 5px; text-align: center; border-color: #b71540;">🎯 <b>OBJETIVO</b></div>

## 🎯 Objetivo

O que deve ser entregue e para quem, em uma ou duas frases.

<span style="color: #8e44ad;">**[ 📝 CONTEXTO ]**</span> Por que a tarefa existe agora (pedido, falha, limitação).

<div style="background: #f1e3ec; border-style: double; color: #6F1E51; padding: 5px; text-align: center; border-color: #6F1E51;">🗂️ <b>CRITÉRIOS PRELIMINARES</b></div>

## 🗂️ Critérios preliminares

- **Dentro do escopo:** …
- **Fora do escopo:** …
- **Dependências:** …

<div style="background: #dfe6e9; border-style: double; color: #2d3436; padding: 5px; text-align: center; border-color: #2d3436;">📒 <b>DETALHAMENTO TÉCNICO</b></div>

## 📒 Detalhamento técnico

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> **RN-XXX-001** — Enunciado verificável.

<span style="color: #2980b9;">**[ 🛠️ ESTRUTURA ]**</span>

| Camada | Arquivo | Alteração |
| --- | --- | --- |
| Serviço | `app/Services/...` | |
| Página Filament | `app/Filament/...` | |
| Teste | `tests/Feature/...` | |

<span style="color: #009432;">**[ 🖼️ VISUALIZAÇÃO ]**</span> O que muda na tela ou no fluxo.

```mermaid
flowchart LR
  A[Usuário] --> B[Ação] --> C[Resultado]
```

<div style="background: #f8e6ff; border-style: double; color: #8e44ad; padding: 5px; text-align: center; border-color: #8e44ad;">📋 <b>REFINAMENTO TÉCNICO</b></div>

## 📋 Refinamento técnico

| Dúvida / decisão | Resposta | Quem decidiu |
| --- | --- | --- |
| | | |

<div style="background: #e0f7fa; border-style: double; color: #00796b; padding: 5px; text-align: center; border-color: #00796b;">🧪 <b>CASOS DE TESTES</b></div>

## 🧪 Casos de testes

| ID | Cenário | Dado | Esperado | Tipo |
| --- | --- | --- | --- | --- |
| CT-01 | Caminho feliz | | | Feature |
| CT-02 | Sem permissão | | 403 / redirect | Authorization |
| CT-03 | Dado inválido | | mensagem de validação | Feature |

<div style="background: #e1efe6; border-style: double; color: #009432; padding: 5px; text-align: center; border-color: #009432;">✅ <b>CRITÉRIOS DE ACEITE</b></div>

## ✅ Critérios de aceite

- [ ] Critério observável 1.
- [ ] Testes CT-01 a CT-03 passando no contêiner.
- [ ] Documento do módulo atualizado.

<div style="background: #e8f4f8; border-style: double; color: #1e3a8a; padding: 5px; text-align: center; border-color: #1e3a8a;">🌐 <b>INFORMAÇÕES COMPLEMENTARES</b></div>

## 🌐 Informações complementares

- PR: …
- Documentação: `docs/04-modulos/...`
