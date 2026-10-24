## 🧪 Tipo de validação — DD/MM/AAAA

> **Ambiente:** local | demo | homologação · **Commit:** `abc1234` · **Base:** recriada | existente · **Executado por:** usuario-github

### Resultado automatizado

| Verificação | Comando | Resultado |
| --- | --- | --- |
| PHPUnit | `docker compose exec -T app php -d memory_limit=512M ./vendor/bin/phpunit --no-progress` | N testes, N asserções, N falhas |
| Pint | `docker compose exec -T app ./vendor/bin/pint --test` | |
| Build | `docker compose exec -T app npm run build` | |

### Ensaio manual

| Etapa e perfil | Esperado | Observado | OK |
| --- | --- | --- | :---: |
| | | | ✅ / ❌ |

### Conclusão

O que ficou comprovado, o que falhou e o que não foi testado.
