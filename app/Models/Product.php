<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Tipos de produto
    |--------------------------------------------------------------------------
    */

    public const TYPE_SIMPLE = 'simple';
    public const TYPE_VARIABLE = 'variable';
    public const TYPE_VARIANT = 'variant';
    public const TYPE_SERVICE = 'service';
    public const TYPE_COMPOSITE = 'composite';

    /*
    |--------------------------------------------------------------------------
    | Modos de inventário
    |--------------------------------------------------------------------------
    */

    public const INVENTORY_NONE = 'none';
    public const INVENTORY_SIMPLE = 'simple';
    public const INVENTORY_BATCH = 'batch';
    public const INVENTORY_SERIAL = 'serial';

    protected $fillable = [
        'company_id',
        'parent_id',
        'product_type',
        'inventory_mode',
        'category_id',
        'brand_id',
        'unit_id',
        'name',
        'sku',
        'barcode',
        'description',
        'purchase_price',
        'sale_price',
        'tax_rate',
        'minimum_stock',
        'track_stock',
        'active',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'parent_id' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'unit_id' => 'integer',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'minimum_stock' => 'decimal:3',
        'track_stock' => 'boolean',
        'active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relações principais
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Produto pai
    |--------------------------------------------------------------------------
    */

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'parent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Variantes filhas
    |--------------------------------------------------------------------------
    */

    public function variants(): HasMany
    {
        return $this->hasMany(
            Product::class,
            'parent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Atributos configurados no produto variável
    |--------------------------------------------------------------------------
    */

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttribute::class,
            'product_attribute_product',
            'product_id',
            'product_attribute_id'
        )
            ->withPivot('position')
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /*
    |--------------------------------------------------------------------------
    | Valores permitidos para geração de variantes
    |--------------------------------------------------------------------------
    */

    public function variantOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_attribute_value_product',
            'product_id',
            'product_attribute_value_id'
        )
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Valores exactos atribuídos a uma variante
    |--------------------------------------------------------------------------
    */

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeValue::class,
            'product_variant_values',
            'product_id',
            'product_attribute_value_id'
        )
            ->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Stocks
    |--------------------------------------------------------------------------
    */

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Movimentos de stock
    |--------------------------------------------------------------------------
    */

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes - Produtos vendáveis
    |--------------------------------------------------------------------------
    */

    public function scopeSellable(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'product_type',
            [
                self::TYPE_SIMPLE,
                self::TYPE_VARIANT,
                self::TYPE_SERVICE,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes - Produtos com stock
    |--------------------------------------------------------------------------
    */

    public function scopeStockable(
        Builder $query
    ): Builder {
        return $query
            ->whereIn(
                'product_type',
                [
                    self::TYPE_SIMPLE,
                    self::TYPE_VARIANT,
                ]
            )
            ->where('track_stock', true)
            ->where(
                'inventory_mode',
                '!=',
                self::INVENTORY_NONE
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers - Tipo de produto
    |--------------------------------------------------------------------------
    */

    public function isSimple(): bool
    {
        return $this->product_type === self::TYPE_SIMPLE;
    }

    public function isVariable(): bool
    {
        return $this->product_type === self::TYPE_VARIABLE;
    }

    public function isVariant(): bool
    {
        return $this->product_type === self::TYPE_VARIANT;
    }

    public function isService(): bool
    {
        return $this->product_type === self::TYPE_SERVICE;
    }

    public function isComposite(): bool
    {
        return $this->product_type === self::TYPE_COMPOSITE;
    }

    /*
    |--------------------------------------------------------------------------
    | Helper - Tem variantes filhas
    |--------------------------------------------------------------------------
    */

    public function hasVariants(): bool
    {
        return $this->isVariable()
            && $this->variants()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers - Inventário
    |--------------------------------------------------------------------------
    */

    public function usesInventory(): bool
    {
        return $this->inventory_mode !== self::INVENTORY_NONE;
    }

    public function usesSimpleInventory(): bool
    {
        return $this->inventory_mode === self::INVENTORY_SIMPLE;
    }

    public function usesBatchInventory(): bool
    {
        return $this->inventory_mode === self::INVENTORY_BATCH;
    }

    public function usesSerialInventory(): bool
    {
        return $this->inventory_mode === self::INVENTORY_SERIAL;
    }

    /*
    |--------------------------------------------------------------------------
    | Vendável
    |--------------------------------------------------------------------------
    */

    public function isSellable(): bool
    {
        return in_array(
            $this->product_type,
            [
                self::TYPE_SIMPLE,
                self::TYPE_VARIANT,
                self::TYPE_SERVICE,
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Controla stock
    |--------------------------------------------------------------------------
    */

    public function isStockable(): bool
    {
        return in_array(
            $this->product_type,
            [
                self::TYPE_SIMPLE,
                self::TYPE_VARIANT,
            ],
            true
        )
            && $this->track_stock
            && $this->inventory_mode !== self::INVENTORY_NONE;
    }

    /*
    |--------------------------------------------------------------------------
    | Sincronizar track_stock
    |--------------------------------------------------------------------------
    */

    public function syncTrackStockFromInventoryMode(): void
    {
        $this->track_stock =
            $this->inventory_mode !== self::INVENTORY_NONE;
    }

    /*
    |--------------------------------------------------------------------------
    | Valores agrupados por atributo
    |--------------------------------------------------------------------------
    */

    public function attributeValuesGrouped(): array
    {
        return $this->attributeValues
            ->groupBy(
                fn ($value) =>
                    $value->product_attribute_id
            )
            ->map(
                fn ($values) =>
                    $values->pluck('value')->values()->all()
            )
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Tipos disponíveis
    |--------------------------------------------------------------------------
    */

    public static function productTypes(): array
    {
        return [
            self::TYPE_SIMPLE => 'Produto simples',
            self::TYPE_VARIABLE => 'Produto com variantes',
            self::TYPE_VARIANT => 'Variante',
            self::TYPE_SERVICE => 'Serviço',
            self::TYPE_COMPOSITE => 'Produto composto',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Tipos criáveis directamente
    |--------------------------------------------------------------------------
    */

    public static function creatableProductTypes(): array
    {
        return [
            self::TYPE_SIMPLE => 'Produto simples',
            self::TYPE_VARIABLE => 'Produto com variantes',
            self::TYPE_SERVICE => 'Serviço',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Modos de inventário disponíveis
    |--------------------------------------------------------------------------
    */

    public static function inventoryModes(): array
    {
        return [
            self::INVENTORY_NONE => 'Sem controlo de stock',
            self::INVENTORY_SIMPLE => 'Stock simples',
            self::INVENTORY_BATCH => 'Stock por lotes',
            self::INVENTORY_SERIAL => 'Stock por número de série',
        ];
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    
}
