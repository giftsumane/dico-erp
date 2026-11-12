# Aceitação operacional do DICO ERP

Esta lista confirma o núcleo ERP sem bares/eventos. A release só deve ser promovida com evidência e sem defeitos bloqueantes.

## Organização e acesso

- [ ] Owner cria empresa e primeira loja.
- [ ] Empresa A não acede a dados da empresa B.
- [ ] Cashier não ajusta stock, cria movimento manual, autoriza devolução ou consulta auditoria.
- [ ] Manager executa apenas operações autorizadas.

## Compras e stock

- [ ] Compra confirmada aumenta stock uma única vez.
- [ ] Produto por lote preserva lote, validade e rastreabilidade.
- [ ] Venda da última unidade não permite saldo negativo.
- [ ] Ajuste exige motivo e deixa movimento.
- [ ] Exportação corresponde aos saldos visíveis.

## Vendas e pagamentos

- [ ] Venda cria itens, pagamento, baixa e recibo.
- [ ] Dinheiro e M-Pesa geram dois pagamentos cuja soma corresponde ao total.
- [ ] Troco incide apenas sobre dinheiro.
- [ ] Reenvio não duplica operações.
- [ ] Venda parcial exige cliente e vencimento.

## Caixa e recebíveis

- [ ] Operador possui apenas um caixa aberto por loja.
- [ ] Dinheiro e pagamentos electrónicos aparecem separados.
- [ ] Recebimento actualiza saldo uma única vez.
- [ ] Diferença no fecho exige justificação.
- [ ] Exportação mostra esperado, contado e diferença.

## Devoluções e auditoria

- [ ] Devolução parcial repõe stock uma única vez.
- [ ] Reembolso não ultrapassa o valor pago disponível.
- [ ] Operações críticas registam contexto, utilizador, data e referência.
- [ ] Auditoria não pode ser editada ou apagada pela aplicação.
- [ ] Apenas owner/admin consulta e exporta auditoria.

## Compatibilidade e recuperação

- [ ] Fluxos funcionam num telefone com 360 px.
- [ ] Não é necessário zoom horizontal.
- [ ] Suíte automatizada passa no commit entregue.
- [ ] Backup foi restaurado num ambiente isolado.
- [ ] Commit, ambiente, data, responsável e pendências estão registados.
