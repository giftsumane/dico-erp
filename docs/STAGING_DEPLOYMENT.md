# Implantação do DICO ERP no staging

Este procedimento actualiza `staging.dicosolutions.co.mz`, aplica migrations e reconstrói os caches do Laravel. Execute primeiro um backup e registe o commit instalado.

## Ambiente

- Aplicação: `/home/dicosolu/dico_staging_app`
- PHP CLI: `/opt/alt/php83/usr/bin/php`
- Branch: `feature/gestao-bares-csv`

## Antes da actualização

1. Confirmar que o domínio está funcional.
2. Confirmar espaço e inodes com `df -h` e `df -i`.
3. Registar o commit com `git rev-parse HEAD`.
4. Criar backup da base pelo cPanel ou `mysqldump`, sem expor credenciais no terminal.
5. Guardar o `.env` e confirmar `APP_DEBUG=false`.

## Actualização

```bash
cd /home/dicosolu/dico_staging_app
git pull --ff-only origin feature/gestao-bares-csv

/opt/alt/php83/usr/bin/php artisan down --retry=30
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan config:cache
/opt/alt/php83/usr/bin/php artisan route:cache
/opt/alt/php83/usr/bin/php artisan view:cache
/opt/alt/php83/usr/bin/php artisan up
```

Se `git pull --ff-only` recusar, não force nem apague alterações. Inspeccione `git status` e resolva a divergência.

## Testes de fumo

Verificar no telefone e computador:

1. Login e selecção da empresa/loja.
2. Dashboard sem erro 500.
3. Abertura do caixa.
4. Venda simples e recibo.
5. Venda dividida entre dinheiro e M-Pesa.
6. Compra e confirmação da entrada em stock.
7. Recebimento de conta pendente.
8. Devolução parcial por manager/owner.
9. Fecho e reconciliação por método.
10. Exportações de vendas, stock, caixas e auditoria.

```bash
/opt/alt/php83/usr/bin/php artisan about
/opt/alt/php83/usr/bin/php artisan migrate:status
tail -n 100 storage/logs/laravel.log
```

## Rollback e recuperação

Rollback deve usar o commit e o backup guardados antes da actualização. Não executar `migrate:rollback` sem confirmar que a migration é reversível e não elimina dados.

1. Colocar a aplicação em manutenção.
2. Restaurar a base quando a release alterou esquema ou dados.
3. Repor o commit anterior por um mecanismo Git aprovado, preservando alterações locais.
4. Reconstruir caches, retirar manutenção e repetir os testes de fumo.

Um backup só é válido depois de um restauro testado num ambiente isolado. Registar data, ficheiro, responsável, duração e resultado.
