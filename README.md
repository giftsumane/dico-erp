# DICO ERP POS

Plataforma Laravel multiempresa e multiloja para catálogo, compras, stock, POS, pagamentos, caixa, clientes, contas a receber, devoluções, relatórios e auditoria.

## Requisitos

- PHP 8.3 com extensões Laravel, incluindo `intl`
- MariaDB 11 ou compatível
- Composer 2
- Node.js apenas quando for necessário recompilar recursos Vite

## Instalação local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Nunca guardar `.env`, palavras-passe ou chaves no repositório.

## Verificação

```bash
php artisan test
php artisan route:list
```

## Documentação operacional

- [Implantação no staging](docs/STAGING_DEPLOYMENT.md)
- [Aceitação operacional](docs/OPERATIONS_ACCEPTANCE.md)

O staging oficial utiliza o branch `feature/gestao-bares-csv`. As operações de bares/eventos são uma extensão separada do núcleo ERP.
