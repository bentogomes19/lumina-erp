---
titulo: "Preparação do release de homologação"
status: vigente
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# Preparação do release de homologação

**Estado em 20/10/2026:** correção de publicação e média validada pela suíte local. A implantação de homologação ainda depende de infraestrutura e configuração próprias; este roteiro não autoriza tráfego de produção.

## 📦 Dados necessários para provisionar o ambiente

| Item | Definição necessária |
| --- | --- |
| Host e domínio | Servidor/plataforma, domínio de homologação e emissão/renovação de certificado TLS |
| Banco | MySQL privado, credenciais exclusivas e local de backup fora do servidor da aplicação |
| E-mail | Provedor SMTP/API, remetente validado e caixa de teste para convite e recuperação |
| Arquivos | Volume persistente ou armazenamento de objetos para documentos e anexos |
| Segredos | Chave `APP_KEY` persistente, senhas e tokens armazenados fora da imagem e do Git |
| Operação | Responsável por alertas, monitoramento, retenção de logs e restauração |

## 🗂️ Condições para criar o candidato de release

1. Construir imagem imutável com dependências e assets, sem montar o código, o `.ssh` do host ou o `.env` de desenvolvimento. Manter MySQL acessível apenas pela rede privada.
2. Configurar `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, segredos próprios, cookies seguros e e-mail real. Verificar permissões de escrita em `storage` e `bootstrap/cache`.
3. Operar filas com worker supervisionado se `QUEUE_CONNECTION=database` for mantido. Configurar health check, logs e alertas.
4. Preparar backup automatizado do banco e dos arquivos com retenção definida. Restaurar uma cópia em ambiente separado e registrar tempo e integridade da restauração.
5. Aplicar migrações com `php artisan migrate --force` e dados de referência aprovados. **Não usar** `migrate:fresh`, `--seed` genérico ou as contas de demonstração em homologação.

## ✅ Aceite após implantação

- Confirmar HTTPS, redirecionamento de HTTP, debug desligado e ausência de porta pública do banco.
- Executar a suíte sobre um banco de teste separado e registrar versão da imagem/commit e resultado.
- Ensaiar convite, abertura do link, definição de senha e recuperação por e-mail real.
- Repetir o roteiro autenticado de secretaria, professor e aluno com dados sintéticos: matrícula, transferência, capacidade, avaliação, rascunho invisível, publicação, médias, frequência e PDFs autorizados/negados.
- Verificar persistência de arquivos, processamento de filas, logs/alertas e restauração do backup.
- Registrar aprovação de aceite e plano de retorno antes de qualquer liberação para produção.

## ⚠️ Situação atual

O `compose.yaml` e o `Dockerfile` existentes servem ao desenvolvimento: há bind mount de código e `.ssh`, MySQL publicado no host e nginx HTTP. Não devem ser reutilizados como artefatos de homologação. O candidato de release e o ambiente de destino ainda precisam ser criados e testados.
