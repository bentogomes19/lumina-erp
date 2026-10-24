---
titulo: "Matrículas (exemplo)"
status: vigente
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# Matrículas (exemplo)

> [!NOTE]
> Exemplo do template de módulo preenchido com as regras de [matrículas](../../04-modulos/matriculas.md). A fonte oficial continua sendo o documento do módulo.

## 🎯 Objetivo

Registrar o vínculo histórico de um aluno com uma turma em um ano letivo, sem apagar nem sobrescrever o histórico em retornos, transferências, cancelamentos ou rematrículas. **Fora do escopo:** cobrança e contratos (não há módulo financeiro).

## 👥 Perfis e acesso

| Perfil | Entrada | Pode | Não pode |
| --- | --- | --- | --- |
| Secretaria | `/lumina/login` | Criar, trancar, reativar, transferir, cancelar | Apagar histórico |
| Aluno | `/aluno/login` | Ver dados da matrícula ativa | Ver matrícula de outro aluno |

## 🔄 Fluxo

<span style="color: #009432;">**[ 🖼️ VISUALIZAÇÃO ]**</span> Estados da matrícula e as operações de `StudentEnrollmentService` que os alteram.

```mermaid
stateDiagram-v2
  [*] --> Ativa
  Ativa --> Trancada: lock
  Trancada --> Ativa: reactivate
  Suspensa --> Ativa: reactivate
  Ativa --> Cancelada: cancel
  Trancada --> Cancelada: cancel
  Cancelada --> Ativa: restoreCanceled
  Ativa --> TransferidaInterna: transfer
  Ativa --> TransferidaExterna: transferExternal
  Trancada --> TransferidaExterna: transferExternal
  Ativa --> Completa: fim do ano letivo
```

| Situação alternativa | Comportamento | Mensagem ao usuário |
| --- | --- | --- |
| Turma sem vaga | Rejeita, não cria matrícula | Aviso de capacidade |
| Ano letivo encerrado | Rejeita, não cria matrícula | Mensagem explícita de ano encerrado |

## 📢 Regras de negócio

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> **RN-MAT-001 — Matrícula ativa única**
Apenas uma matrícula `Ativa` por aluno e ano letivo. Matrículas históricas podem coexistir se não estiverem ativas ao mesmo tempo.

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> **RN-MAT-002 — Ocupação de vaga**
`Ativa`, `Suspensa` e `Trancada` ocupam vaga; os demais status liberam.

<span style="color: #c0392b;">**[ 📢 REGRA DE NEGÓCIO ]**</span> **RN-MAT-003 — Rastreabilidade**
Toda mudança de estado preenche `operated_by_user_id` (quando houver operador) e cria registro em `enrollment_logs`.
<sub>Onde: `App\Services\Enrollments\StudentEnrollmentService`</sub>

## 🗃️ Dados

<span style="color: #2980b9;">**[ 🛠️ ESTRUTURA ]**</span>

| Tabela | Uso no módulo | Restrições |
| --- | --- | --- |
| `enrollments` | Vínculo aluno–turma–ano | Uma ativa por aluno/ano |
| `enrollment_logs` | Histórico de operações | FK restrita (não apaga com a matrícula) |
| `enrollment_documents` | Documentos emitidos | — |

## ⚠️ Limitações conhecidas

- Envio real de e-mail ainda não ensaiado (`MAIL_MAILER=log`).

## 🚀 Evolução

> [!NOTE]
> Propostas, ainda não implementadas.

- Registrar as regras acima também como testes nomeados por ID (`test_rn_mat_001_...`).
