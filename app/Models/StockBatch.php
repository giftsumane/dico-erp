<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StockBatch extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXHAUSTED = 'exhausted';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'company_id',
        'store_id',
        'product_id',
        'supplier_id',
        'code',
        'name',
        'initial_quantity',
        'quantity',
        'unit_cost',
        'received_at',
        'expiry_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'initial_quantity' => 'decimal:3',
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'received_at' => 'date',
        'expiry_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function saleItems(): BelongsToMany
    {
        return $this->belongsToMany(
            SaleItem::class,
            'sale_item_stock_batches',
            'stock_batch_id',
            'sale_item_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class,
            'stock_batch_id'
        );
    }



    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExhausted(): bool
    {
        return (float) $this->quantity <= 0;
    }

    public function saleReturnItems(): BelongsToMany
    {
        return $this->belongsToMany(
            SaleReturnItem::class,
            'sale_return_item_stock_batches',
            'stock_batch_id',
            'sale_return_item_id'
        )
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
