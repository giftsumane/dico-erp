<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'stock_batch_id',

        'batch_code',
        'batch_name',
        'batch_received_at',
        'batch_expiry_date',

        'quantity',
        'unit_cost',
        'tax_rate',
        'subtotal',
        'tax_total',
        'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',

        'batch_received_at' => 'date',
        'batch_expiry_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(
            StockBatch::class,
            'stock_batch_id'
        );
    }
}
