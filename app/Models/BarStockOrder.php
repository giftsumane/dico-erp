<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarStockOrder extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_PARTIALLY_RECEIVED = 'partially_received';

    public const STATUS_RECEIVED = 'received';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'event_id', 'event_bar_id', 'warehouse_store_id',
        'requested_by', 'approved_by', 'prepared_by', 'dispatched_by', 'received_by',
        'number', 'idempotency_key', 'status', 'initial_allocation',
        'total', 'paid_total', 'amount_due', 'approved_at', 'prepared_at',
        'dispatched_at', 'received_at', 'closed_at', 'notes',
    ];

    protected $casts = [
        'initial_allocation' => 'boolean',
        'total' => 'decimal:2', 'paid_total' => 'decimal:2', 'amount_due' => 'decimal:2',
        'approved_at' => 'datetime', 'prepared_at' => 'datetime',
        'dispatched_at' => 'datetime', 'received_at' => 'datetime', 'closed_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bar(): BelongsTo
    {
        return $this->belongsTo(EventBar::class, 'event_bar_id');
    }

    public function warehouseStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'warehouse_store_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BarStockOrderItem::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(BarStockSettlement::class);
    }
}
