# Lumina ERP 

Sistema de Gestão Escolar desenvolvido em **Laravel + Filament + Docker**.

## Introdução do Sistema
Lumina ERP é um sistema de gestão acadêmica, tem a finalidade de simplificar os processos.

---

## 🚀 Do clone ao primeiro refresh no browser

**Pré-requisitos:** [Docker](https://docs.docker.com/get-docker) e Git. Para enviar código (push) sem senha: [SSH no GitHub](https://docs.github.com/en/authentication/connecting-to-github-with-ssh) ou [GitHub CLI](https://cli.github.com).

```bash
git clone git@github.com:SEU_ORG_OU_USUARIO/lumina-erp.git
cd lumina-erp
make bootstrap
```

Depois abra **http://lumina/** no browser.

Para abrir pelo nome local `lumina/` no Windows, adicione esta linha no arquivo `C:\Windows\System32\drivers\etc\hosts` executando o editor como administrador:

```text
127.0.0.1 lumina
```

O nginx publica a porta HTTP padrão `80`, então não é necessário informar `:8000` na URL.

O `make bootstrap` cria o `.env` (se não existir), sobe os containers (app, nginx, MySQL), instala dependências e roda as migrations com seeders.

**Sem Make?** Veja o passo a passo e alternativas em [Ambiente Dev](./docs/devops/ambiente-dev.md) (inclui **autenticação GitHub** e comandos PowerShell/Bash).

---

## Comandos úteis (Makefile)
| Comando | Descrição |
|---------|-----------|
| `make bootstrap` | Do zero: .env + up + install + migrate --seed |
| `make up` | Sobe os containers |
| `make down` | Para os containers |
| `make shell` | Entra no container (zsh) |
| `make migrate` | Roda migrations |
| `make seed` | Migrations + seeders |
| `make test` | PHPUnit |
| `make lint` | Laravel Pint (checagem) |

---

## Infraestrutura e DevOps

- **Docker**: `Dockerfile` (PHP 8.2-FPM, Composer, Node 18), `compose.yaml` (app, nginx, MySQL 8) com healthcheck no banco e variáveis via `.env`. Dentro do container o projeto fica em **`/dev/lumina-erp`**.
- **.dockerignore**: reduz tamanho do contexto de build e acelera o build.
- **CI (GitHub Actions)**: em cada push/PR em `main` e `develop` roda **Laravel Pint**, **PHPUnit** (com MySQL em serviço) e **build da imagem Docker** (`.github/workflows/ci.yaml`).
- **Makefile**: atalhos para bootstrap, build, up, down, shell, migrate, seed, test e lint.

Para adicionar workers de fila no futuro, use o perfil `workers` no `compose.yaml` (serviço `queue` comentado).

<div style="
    border: 1px solid #d39aadff;
    background-color: rgba(175, 9, 180, 1);
    padding: 10px 20px;
    text-align: center;
    font-weight: bold;
    color: white;
">
    📕 Documentação Oficial
</div>

---

[📕 Documentação Oficial - Clique Aqui](./docs/index.md)

