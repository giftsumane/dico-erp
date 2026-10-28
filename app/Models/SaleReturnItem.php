<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SaleReturnItem extends Model
{
    protected $fillable = [
        'sale_return_id',
        'sale_item_id',
        'product_id',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function saleReturn(): BelongsTo
    {
        return $this->belongsTo(
            SaleReturn::class
        );
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(
            SaleItem::class
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }

    public function stockBatches(): BelongsToMany
    {
        return $this->belongsToMany(
            StockBatch::class,
            'sale_return_item_stock_batches',
            'sale_return_item_id',
            'stock_batch_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }
}