<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'tax_rate',
        'subtotal',
        'tax_total',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Lotes utilizados nesta linha de venda
    |--------------------------------------------------------------------------
    |
    | Uma linha pode consumir vários lotes.
    |
    | Exemplo:
    |
    | VendaItem: 15 unidades
    |
    | Lote A → 10
    | Lote B → 5
    |
    */

    public function stockBatches(): BelongsToMany
    {
        return $this->belongsToMany(
            StockBatch::class,
            'sale_item_stock_batches',
            'sale_item_id',
            'stock_batch_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function returnItems()
    {
        return $this->hasMany(
            SaleReturnItem::class
        );
    }
}