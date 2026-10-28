<?php

namespace App\Support;

class CompanyFeatures
{
    public const POS =
        'pos';

    public const SALES =
        'sales';

    public const INVENTORY =
        'inventory';

    public const PURCHASES =
        'purchases';

    public const RECEIVABLES =
        'receivables';

    public const BRANDS =
        'brands';

    public const VARIANTS =
        'variants';

    public const STOCK_BATCHES =
        'stock_batches';

    public const IMPORTS =
        'imports';

    public const EXPIRY_DATES =
        'expiry_dates';

    public const SERIAL_NUMBERS =
        'serial_numbers';

    public const RECIPES =
        'recipes';

    public const INGREDIENTS =
        'ingredients';

    public const TABLES =
        'tables';

    public const KITCHEN =
        'kitchen';


    public static function all(): array
    {
        return [
            self::POS,
            self::SALES,
            self::INVENTORY,
            self::PURCHASES,
            self::RECEIVABLES,
            self::BRANDS,
            self::VARIANTS,
            self::STOCK_BATCHES,
            self::IMPORTS,
            self::EXPIRY_DATES,
            self::SERIAL_NUMBERS,
            self::RECIPES,
            self::INGREDIENTS,
            self::TABLES,
            self::KITCHEN,
        ];
    }
}