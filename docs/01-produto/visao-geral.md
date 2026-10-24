---
titulo: "Visão geral do Lumina ERP"
status: vigente
atualizado_em: 2026-10-06
responsavel: bentogomes19
---

# Visão geral do Lumina ERP

## Objetivo e escopo

O Lumina ERP centraliza cadastros e operações acadêmicas de uma escola em uma aplicação web. O recorte implementado cobre usuários e permissões, alunos e professores, estrutura acadêmica, turmas, matrículas, avaliações, notas, frequência, indicadores administrativos e documentos PDF. Gestão financeira e integrações externas não fazem parte do produto demonstrado.

## Perfis e entrada

| Perfil | Entrada | Uso principal |
| --- | --- | --- |
| Administração/secretaria | `/lumina/login` | Cadastros, matrículas, estrutura acadêmica e permissões conforme o papel |
| Professor | `/professor/login` | Turmas, avaliações, notas e frequência das atribuições permitidas |
| Aluno | `/aluno/login` | Consulta de disciplinas, notas, frequência e calendário |

Os painéis são configurados separadamente em `app/Providers/Filament`. O controle de acesso combina autenticação, papéis, permissões e verificações de contexto nos fluxos.

## Arquitetura

- PHP 8.4, Laravel 12 e Filament 5 no backend e na interface administrativa.
- MySQL 8 para persistência; migrations e seeders em `database/`.
- Vite, Tailwind CSS e JavaScript para assets.
- Docker Compose com serviços `app`, `nginx` e `db` para desenvolvimento local.
- DomPDF para documentos acadêmicos em PDF.

O projeto é um **protótipo acadêmico**. A [validação de demonstração](../05-qualidade/validacao-demonstracao.md) registra o que foi comprovado e os bloqueios conhecidos; ela não substitui uma homologação de produção.
