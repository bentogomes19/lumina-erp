# Ambiente de desenvolvimento – Lumina ERP

Este guia cobre **clone**, **autenticação com GitHub** e **primeiro refresh no browser** no menor tempo possível.

---

## 1. Autenticação e Acesso: O Jeito Moderno

Para clonar e **enviar alterações** (push) sem ficar digitando senha, use uma destas opções.

### Opção A: SSH (recomendado)

1. **Gerar chave SSH** (se ainda não tiver):
   ```bash
   ssh-keygen -t ed25519 -C "seu-email@exemplo.com" -f ~/.ssh/id_ed25519 -N ""
   ```

2. **Copiar a chave pública** para colar no GitHub:
   - **Windows (PowerShell):** `Get-Content $env:USERPROFILE\.ssh\id_ed25519.pub | Set-Clipboard`
   - **macOS:** `pbcopy < ~/.ssh/id_ed25519.pub`
   - **Linux:** `xclip -sel clip < ~/.ssh/id_ed25519.pub` ou copie o conteúdo do arquivo

3. **Adicionar no GitHub:** [GitHub → Settings → SSH and GPG keys → New SSH key](https://github.com/settings/keys). Cole a chave e salve.

4. **Clonar com SSH** (em vez de HTTPS):
   ```bash
   git clone git@github.com:SEU_ORG_OU_USUARIO/lumina-erp.git
   cd lumina-erp
   ```
   Assim, `git push` e `git pull` não pedem senha.

### Opção B: GitHub CLI

1. **Instalar:** [https://cli.github.com](https://cli.github.com) (Windows/macOS/Linux).

2. **Autenticar uma vez:**
   ```bash
   gh auth login
   ```
   Siga o assistente (escolha GitHub.com, HTTPS ou SSH, login no browser).

3. **Clonar** (o CLI já usa a sessão):
   ```bash
   gh repo clone SEU_ORG_OU_USUARIO/lumina-erp
   cd lumina-erp
   ```

### Opção C: HTTPS com credential helper

Se preferir HTTPS e não querer digitar senha a cada push:

- **Windows:** o [Git for Windows](https://git-scm.com/download/win) costuma integrar com o **Credential Manager**; ao dar o primeiro `git push`, a senha é guardada.
- **macOS:** use o keychain:
  ```bash
  git config --global credential.helper osxkeychain
  ```
- **Linux:** use um helper em disco (menos seguro) ou [Git Credential Manager](https://github.com/GitCredentialManager/git-credential-manager).

**Resumo:** para o dia a dia, **SSH** ou **GitHub CLI** são o jeito moderno e mais prático.

---

## 2. Pré-requisitos (uma vez na máquina)

- **Docker Desktop** (ou Docker Engine + Docker Compose): [https://docs.docker.com/get-docker](https://docs.docker.com/get-docker)
- **Git**
- **Make** (opcional; no Windows pode usar os comandos equivalentes em PowerShell ou o script abaixo)

---

## 3. Do clone ao primeiro refresh no browser

Objetivo: **mínimo de passos** entre clonar e abrir a aplicação no browser.

### Com Make (recomendado)

```bash
git clone git@github.com:SEU_ORG_OU_USUARIO/lumina-erp.git
cd lumina-erp
make bootstrap
```

O `make bootstrap`:

1. Copia `.env.example` → `.env` se não existir
2. Sobe os containers (app, nginx, MySQL)
3. Espera o banco ficar saudável
4. Roda `composer install`, `php artisan key:generate`, `migrate --seed`

Depois, abra no browser: **http://localhost:8000** (ou a porta em `APP_PORT` no `.env`).

### Sem Make (PowerShell / Bash)

Se não tiver Make, na raiz do projeto:

**PowerShell:**
```powershell
if (!(Test-Path .env)) { Copy-Item .env.example .env }
docker compose up -d --build
# Aguardar ~20s para o MySQL subir, depois:
docker exec lumina-app sh -c "composer install && php artisan key:generate && php artisan migrate --seed"
```

**Bash:**
```bash
[ -f .env ] || cp .env.example .env
docker compose up -d --build
sleep 25
docker exec lumina-app sh -c "composer install && php artisan key:generate && php artisan migrate --seed"
```

Em seguida: **http://localhost:8000**.

---

## 4. Comandos úteis após o bootstrap

| Comando       | Descrição                          |
|---------------|------------------------------------|
| `make up`     | Sobe os containers                 |
| `make down`   | Para os containers                |
| `make shell`  | Entra no container da app (zsh)   |
| `make seed`   | Roda migrations + seeders         |
| `make fresh`  | migrate:fresh --seed              |
| `make test`   | PHPUnit                           |
| `make lint`   | Laravel Pint (checagem)           |

---

## 5. Resumo do fluxo “clone → browser”

1. **Autenticação (uma vez):** SSH ou `gh auth login`.
2. **Clone:** `git clone ...` (de preferência via SSH).
3. **Subir e instalar:** `make bootstrap`.
4. **Abrir:** http://localhost:8000.

Isso reduz o tempo entre “clonagem” e “primeiro refresh no browser” ao mínimo possível, com um único comando após o clone.
