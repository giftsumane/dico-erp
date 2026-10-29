<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarStockSettlement extends Model
{
    protected $fillable = [
        'bar_stock_order_id', 'paid_by', 'confirmed_by', 'amount', 'method',
        'reference', 'status', 'paid_at', 'confirmed_at', 'notes',
    ];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'confirmed_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BarStockOrder::class, 'bar_stock_order_id');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
