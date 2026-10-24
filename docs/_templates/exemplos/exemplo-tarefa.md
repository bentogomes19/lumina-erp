---
titulo: "LUM-123 — Exportar calendário acadêmico em iCalendar (exemplo)"
status: rascunho
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# LUM-123 — Exportar calendário acadêmico em iCalendar (exemplo)

<div style="background: #8e44ad; color: white; padding: 5px; text-align: center; font-weight: bold;">ESTA TAREFA PRECISA DE DOCUMENTAÇÃO</div>

> **Módulo:** Portal do aluno · **Tipo:** funcionalidade · **Prioridade:** média
> <sub>Exemplo do template de tarefa. Código e números fictícios; o contexto vem do relatório de prontidão.</sub>

<div style="background: #f4dfe5; border-style: double; color: #b71540; padding: 5px; text-align: center; border-color: #b71540;">🎯 <b>OBJETIVO</b></div>

## 🎯 Objetivo

Permitir que o aluno baixe o calendário acadêmico do ano ativo em formato `.ics` para importar no Google Agenda ou Outlook.

<span style="color: #8e44ad;">**[ 📝 CONTEXTO ]**</span> A página `AcademicCalendar` exibe as ações de PDF e iCalendar, mas hoje elas apenas notificam “em breve”. O relatório de prontidão pede implementar ou remover essas ações.

<div style="background: #f1e3ec; border-style: double; color: #6F1E51; padding: 5px; text-align: center; border-color: #6F1E51;">🗂️ <b>CRITÉRIOS PRELIMINARES</b></div>

## 🗂️ Critérios preliminares

- **Dentro do escopo:** exportação `.ics` do ano letivo ativo do aluno logado.
- **Fora do escopo:** exportação em PDF (tarefa separada), assinatura por URL.
- **Dependências:** dias não letivos em `school_holidays`; avaliações publicadas.

<div style="background: #dfe6e9; border-style: double; color: #2d3436; padding: 5px; text-align: center; border-color: #2d3436;">📒 <b>DETALHAMENTO TÉCNICO</b></div>

## 📒 Detalhamento técnico

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> **RN-ALU-013** — O arquivo contém apenas eventos do ano letivo ativo e da turma da matrícula ativa do aluno.

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> **RN-ALU-014** — Avaliações só entram no arquivo depois de publicadas.

<span style="color: #2980b9;">**[ 🛠️ ESTRUTURA ]**</span>

| Camada | Arquivo | Alteração |
| --- | --- | --- |
| Página | `app/Filament/Pages/Student/AcademicCalendar.php` | Ação chama o serviço e devolve download |
| Serviço | `app/Services/Calendar/IcsExporter.php` | Novo: monta VCALENDAR/VEVENT |
| Teste | `tests/Feature/Student/CalendarExportTest.php` | Novo |

<span style="color: #009432;">**[ 🖼️ VISUALIZAÇÃO ]**</span> Sequência da exportação.

```mermaid
sequenceDiagram
  actor Aluno
  participant P as AcademicCalendar
  participant S as IcsExporter
  participant DB as Banco
  Aluno->>P: Clica em "Exportar .ics"
  P->>S: export(matrícula ativa)
  S->>DB: feriados + avaliações publicadas
  DB-->>S: eventos
  S-->>P: conteúdo text/calendar
  P-->>Aluno: download calendario-2026.ics
```

<div style="background: #f8e6ff; border-style: double; color: #8e44ad; padding: 5px; text-align: center; border-color: #8e44ad;">📋 <b>REFINAMENTO TÉCNICO</b></div>

## 📋 Refinamento técnico

| Dúvida / decisão | Resposta | Quem decidiu |
| --- | --- | --- |
| Gerar com biblioteca ou à mão? | Biblioteca, para escapar texto e fuso corretamente | — |
| Fuso horário | `America/Sao_Paulo` | — |

<div style="background: #e0f7fa; border-style: double; color: #00796b; padding: 5px; text-align: center; border-color: #00796b;">🧪 <b>CASOS DE TESTES</b></div>

## 🧪 Casos de testes

| ID | Cenário | Dado | Esperado | Tipo |
| --- | --- | --- | --- | --- |
| CT-01 | Aluno com matrícula ativa | 2 feriados, 1 avaliação publicada | `.ics` com 3 eventos | Feature |
| CT-02 | Avaliação em rascunho | 1 avaliação sem `locked_at` | Não aparece no arquivo | Feature |
| CT-03 | Usuário sem papel de aluno | professor logado | Acesso negado | Authorization |

<div style="background: #e1efe6; border-style: double; color: #009432; padding: 5px; text-align: center; border-color: #009432;">✅ <b>CRITÉRIOS DE ACEITE</b></div>

## ✅ Critérios de aceite

- [ ] Arquivo importa sem erro no Google Agenda e no Outlook.
- [ ] CT-01 a CT-03 passando no contêiner.
- [ ] `docs/04-modulos/portal-do-aluno.md` atualizado com RN-ALU-013 e RN-ALU-014.

<div style="background: #e8f4f8; border-style: double; color: #1e3a8a; padding: 5px; text-align: center; border-color: #1e3a8a;">🌐 <b>INFORMAÇÕES COMPLEMENTARES</b></div>

## 🌐 Informações complementares

- [Relatório de prontidão](../../05-qualidade/relatorio-prontidao.md) — item “exportações expostas mas ainda não implementadas”.
- RFC 5545 (iCalendar).
