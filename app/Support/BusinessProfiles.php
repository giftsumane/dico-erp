<?php

namespace App\Support;

class BusinessProfiles
{
    public const RETAIL =
        'retail';

    public const FASHION =
        'fashion';

    public const SUPERMARKET =
        'supermarket';

    public const FOOD =
        'food';

    public const IMPORT =
        'import';

    public const SERVICES =
        'services';

    public const OTHER =
        'other';


    public static function labels(): array
    {
        return [
            self::RETAIL =>
                'Loja / Retalho',

            self::FASHION =>
                'Boutique / Moda',

            self::SUPERMARKET =>
                'Supermercado / Mercearia',

            self::FOOD =>
                'Restaurante / Pastelaria',

            self::IMPORT =>
                'Importação / Distribuição',

            self::SERVICES =>
                'Serviços',

            self::OTHER =>
                'Outro',
        ];
    }


    public static function defaults(
        string $type
    ): array {
        return match ($type) {

            self::FASHION => [
                CompanyFeatures::POS,
                CompanyFeatures::SALES,
                CompanyFeatures::INVENTORY,
                CompanyFeatures::PURCHASES,
                CompanyFeatures::RECEIVABLES,
                CompanyFeatures::BRANDS,
                CompanyFeatures::VARIANTS,
            ],

            self::SUPERMARKET => [
                CompanyFeatures::POS,
                CompanyFeatures::SALES,
                CompanyFeatures::INVENTORY,
                CompanyFeatures::PURCHASES,
                CompanyFeatures::RECEIVABLES,
                CompanyFeatures::BRANDS,
                CompanyFeatures::EXPIRY_DATES,
            ],

            self::FOOD => [
                CompanyFeatures::POS,
                CompanyFeatures::SALES,
                CompanyFeatures::INVENTORY,
                CompanyFeatures::PURCHASES,
                CompanyFeatures::RECEIVABLES,
                CompanyFeatures::RECIPES,
                CompanyFeatures::INGREDIENTS,
            ],

            self::IMPORT => [
                CompanyFeatures::POS,
                CompanyFeatures::SALES,
                CompanyFeatures::INVENTORY,
                CompanyFeatures::PURCHASES,
                CompanyFeatures::RECEIVABLES,
                CompanyFeatures::STOCK_BATCHES,
                CompanyFeatures::IMPORTS,
            ],

            self::SERVICES => [
                CompanyFeatures::SALES,
                CompanyFeatures::RECEIVABLES,
            ],

            default => [
                CompanyFeatures::POS,
                CompanyFeatures::SALES,
                CompanyFeatures::INVENTORY,
                CompanyFeatures::PURCHASES,
                CompanyFeatures::RECEIVABLES,
            ],
        };
    }
}