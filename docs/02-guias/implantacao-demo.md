---
titulo: "Implantação da demonstração"
status: vigente
atualizado_em: 2026-10-24
responsavel: bentogomes19
---

# Implantação da demonstração

## 🔄 Fluxo

1. O colaborador abre uma pull request para `master`.
2. O GitHub verifica os nomes dos arquivos versionados, o pacote de implantação, o `composer.json` e a compilação dos arquivos de interface.
3. O responsável revisa a alteração e faz o merge.
4. O responsável executa **Actions → Deploy demo → Run workflow** na branch `master` e aprova o ambiente `demo`.

O merge **não** altera a VM por si só. O botão envia apenas os caminhos listados em `scripts/check-deploy-source.sh`, extraídos do commit do Git. Não envia o conteúdo local fora do Git, `.env`, banco, `storage`, `vendor`, `node_modules`, arquivos da documentação ou testes. O script na VM conserva esses dados, faz um backup do MySQL antes das migrações, atualiza as dependências, compila os recursos e verifica a página de login. Ele não executa seeders nem `migrate:fresh`.

> [!WARNING]
> O repositório é público. Um arquivo de código também pode conter uma credencial ou ler dados da VM após ser executado. Portanto, a lista de arquivos e a checagem de padrões comuns são barreiras contra erros acidentais, não substituem a revisão humana de cada pull request.

## 📦 Configuração única

Faça esta configuração **depois** de incorporar os arquivos do fluxo ao repositório.

1. No computador de quem administra a VM, gere uma chave exclusiva para a implantação, **fora da pasta do projeto**:

   ```bash
   ssh-keygen -t ed25519 -a 64 -f "$HOME/.ssh/lumina_demo_actions" -N ''
   cat "$HOME/.ssh/lumina_demo_actions.pub"
   ```

2. No terminal SSH da VM, adicione **a chave pública** retornada pelo comando anterior. Troque `<CHAVE_PUBLICA>` pela linha inteira, iniciada em `ssh-ed25519`:

   ```bash
   install -d -m 700 "$HOME/.ssh"
   printf 'restrict %s\n' '<CHAVE_PUBLICA>' >> "$HOME/.ssh/authorized_keys"
   chmod 600 "$HOME/.ssh/authorized_keys"
   sudo apt-get install -y rsync curl
   sudo -n docker compose version
   sudo cat /etc/ssh/ssh_host_ed25519_key.pub
   ```

3. Em **GitHub → Settings → Environments**, crie o ambiente `demo`. Permita implantações apenas de `master` e defina `bentogomes19` como aprovador obrigatório. Se essa for a única pessoa que executará e aprovará a implantação, deixe desmarcada a opção que impede a aprovação da própria execução.

4. No ambiente `demo`, cadastre o conteúdo completo de `$HOME/.ssh/lumina_demo_actions` como **secret** `DEPLOY_SSH_KEY`. Cadastre estas **variables**:

   - `DEPLOY_HOST`: IP externo atual da VM.
   - `DEPLOY_USER`: usuário SSH da VM.
   - `DEPLOY_KNOWN_HOSTS`: uma linha no formato `<DEPLOY_HOST> ssh-ed25519 <CHAVE_DO_HOST>`, usando a chave pública do host obtida **dentro da VM** no passo 2.

   **Atenção:** nunca cole a chave privada em conversa, issue, pull request, arquivo do projeto ou captura de tela. Após cadastrá-la no GitHub, mantenha a cópia local protegida ou remova-a se não for necessária.

5. Em **GitHub → Settings → Branches**, proteja `master`: exija pull request, o status `validate` do fluxo **Check source** e, para alterações na implantação, revisão do proprietário dos arquivos. O arquivo `.github/CODEOWNERS` define esses arquivos. A revisão obrigatória por outro usuário exige que exista outro colaborador apto a aprovar; não ative essa exigência se ela impedir o primeiro merge.

## ✅ Antes de cada implantação

- Confira que a pull request aprovada passou em **Check source**.
- Verifique se o botão **Run workflow** usa a branch `master`.
- Confira o registro da execução e abra a página `/lumina/login` após a conclusão.
- Se o IP externo da VM mudar, atualize `DEPLOY_HOST` e o endereço público usado em `DEMO_HOST` na VM.

> [!CAUTION]
> O backup MySQL de cada implantação fica em `~/lumina-backups` na VM, fora da pasta sincronizada. Ele contém dados reais da demonstração e deve ser tratado como informação privada.
