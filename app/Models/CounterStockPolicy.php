<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CounterStockPolicy extends Model
{
    protected $fillable = ['event_counter_id', 'product_id', 'reserve_quantity', 'reorder_level'];

    protected $casts = ['reserve_quantity' => 'decimal:3', 'reorder_level' => 'decimal:3'];

    public function counter(): BelongsTo
    {
        return $this->belongsTo(EventCounter::class, 'event_counter_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
