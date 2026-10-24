# Lumina ERP

Sistema web de gestão escolar desenvolvido com **PHP 8.4, Laravel 12, Filament 5 e MySQL 8**. O projeto reúne administração acadêmica, portal do professor e portal do aluno.

## Instalação para demonstração local

Pré-requisitos no computador: Docker com Compose, Git e Make. PHP, Composer e Node são fornecidos pelo container; a imagem usa Node 22. A porta HTTP definida em `APP_PORT` (padrão 80) precisa estar livre.

```bash
git clone https://github.com/bentogomes19/lumina-erp.git
cd lumina-erp
make bootstrap
```

O bootstrap cria `.env`, sobe app/nginx/MySQL, instala dependências, compila os assets, gera `APP_KEY` e executa migrations com dados de demonstração. A carga escolar é permitida apenas em `APP_ENV=local` ou `testing`. Revise a configuração antes de usar uma base com dados que deseja preservar. Para contribuir via SSH, veja o [guia de ambiente](./docs/02-guias/ambiente-dev.md).

Abra `http://localhost/lumina/login` ou, após mapear `127.0.0.1 lumina` no arquivo hosts, `http://lumina/lumina/login`. Os outros portais ficam em `/professor/login` e `/aluno/login`. Se a porta 80 estiver ocupada, defina `APP_PORT` no `.env` e acesse `http://localhost:PORTA`.

As contas de demonstração são criadas pelos seeders; veja o [roteiro de validação](./docs/05-qualidade/validacao-demonstracao.md). O ambiente padrão grava e-mails no log (`MAIL_MAILER=log`), então convites e recuperação de senha não chegam a uma caixa de entrada real.

## Verificações no container

```bash
docker compose ps
docker compose exec -T app php artisan migrate:status
docker compose exec -T app npm run build
docker compose exec -T app php -d memory_limit=512M ./vendor/bin/phpunit --no-progress
docker compose exec -T app ./vendor/bin/pint --test
```

Na última validação registrada (20/10/2026), a suíte passou com 297 testes; o Pint não foi reavaliado desde 06/10/2026, quando falhou. Veja os [resultados e limitações](./docs/05-qualidade/validacao-demonstracao.md) e o [relatório de prontidão](./docs/05-qualidade/relatorio-prontidao.md).

## Documentação

- [Índice da documentação](./docs/README.md)
- [Ambiente e instalação detalhada](./docs/02-guias/ambiente-dev.md)
- [Validação e demonstração](./docs/05-qualidade/validacao-demonstracao.md)
- [Implantação da demonstração](./docs/02-guias/implantacao-demo.md)
- [Relatório de prontidão](./docs/05-qualidade/relatorio-prontidao.md)
