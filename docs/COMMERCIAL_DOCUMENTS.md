# Documentos comerciais

O módulo de faturação acrescenta ao DICO ERP cotações, facturas, facturas-recibo,
notas de crédito/débito e recibos, mantendo os documentos emitidos imutáveis.

## Fluxos

- Uma cotação é criada como rascunho, emitida e convertida numa factura.
- Uma factura admite pagamentos parciais e fica liquidada quando o saldo chega a zero.
- Cada pagamento gera um recibo numerado e uma alocação à factura.
- Uma venda concluída com cliente pode gerar uma factura ou factura-recibo.
- Notas de crédito e débito exigem a indicação da factura de origem.

## Numeração e controlo

Cada empresa, loja, tipo e ano possui uma série independente. Os prefixos usados são
`COT`, `FT`, `FR`, `NC`, `ND` e `RC`. A atribuição do número ocorre dentro de uma
transacção com bloqueio da série. Recibos aceitam uma chave de idempotência para
impedir duplicação em reenvios.

Depois de emitido, um documento não pode ser editado. A anulação exige justificação,
fica registada no trilho de auditoria e está limitada a owner, admin ou manager.

## Implantação

```bash
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan config:cache
/opt/alt/php83/usr/bin/php artisan route:cache
/opt/alt/php83/usr/bin/php artisan view:cache
```

Antes do uso fiscal em produção, a configuração documental, os campos obrigatórios,
as taxas e o formato de impressão devem ser homologados com o contabilista da empresa
e com os requisitos fiscais aplicáveis em Moçambique.
