# Registo público e subscrições SaaS

## Situação actual

O DICO separa agora três conceitos:

1. O módulo comercial que a empresa contratou.
2. A funcionalidade técnica activada para a empresa.
3. A função e permissão de cada utilizador.

Empresas anteriores à migração de subscrições permanecem em modo legado e não são
bloqueadas. Empresas registadas em `/register` recebem uma subscrição experimental.

## Catálogo inicial

Os módulos iniciais são Faturação, Contas a receber, POS, Stock, Compras, Produtos
avançados, Eventos e bares e Contabilidade. Contabilidade está catalogada para a fase
seguinte, mas ainda não disponibiliza funcionalidades contabilísticas.

Os pacotes iniciais são Faturação, Comercial, Loja, Gestão e Eventos. Os preços mensal
e anual ficam vazios até serem definidos pela DICO Solutions. Também existe adesão
personalizada por módulos.

## Dependências automáticas

- POS inclui Faturação.
- Compras inclui Stock.
- Produtos avançados inclui Stock.
- Eventos e bares inclui Faturação, POS, Stock e Compras.

## Período experimental

Por omissão, o teste dura 14 dias. Quando termina, a área operacional é bloqueada, mas
owners e admins continuam a poder consultar a página de subscrição para regularizar o
serviço. Ainda não existe cobrança automática; preços, pagamentos e renovações serão
geridos pelo futuro portal da DICO Solutions.
