# Permissões diretas excepcionais

As permissões padrão devem ser concedidas sempre por papéis. Permissões diretas em usuários são exceções temporárias e precisam de justificativa rastreável.

## Diagnóstico

Execute primeiro em simulação:

```bash
php artisan permissions:direct --dry-run
```

O status `Redundante` indica que a permissão direta já é herdada por algum papel do usuário. O status `Exceção direta` indica uma concessão que não vem dos papéis atuais.

## Limpeza

Depois de revisar a simulação, remova apenas as redundâncias:

```bash
php artisan permissions:direct --prune
```

O comando preserva exceções diretas e registra em log cada redundância removida.

## Concessão excepcional

Antes de conceder uma permissão direta, registre no chamado ou trilha de auditoria:

- usuário afetado;
- permissão concedida;
- justificativa de negócio;
- aprovador responsável;
- prazo ou condição de revisão.

Revise exceções regularmente com `php artisan permissions:direct --dry-run`. Quando a exceção se tornar padrão para um grupo, mova a permissão para o papel adequado e execute a limpeza de redundâncias.
