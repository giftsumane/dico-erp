<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarStockOrderItem extends Model
{
    protected $fillable = [
        'bar_stock_order_id', 'event_counter_id', 'product_id',
        'quantity_requested', 'quantity_approved', 'quantity_dispatched',
        'quantity_received', 'reserve_quantity', 'unit_cost', 'total', 'difference_reason',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:3', 'quantity_approved' => 'decimal:3',
        'quantity_dispatched' => 'decimal:3', 'quantity_received' => 'decimal:3',
        'reserve_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BarStockOrder::class, 'bar_stock_order_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(EventCounter::class, 'event_counter_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
