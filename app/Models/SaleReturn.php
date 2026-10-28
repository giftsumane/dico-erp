<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleReturn extends Model
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_PARTIAL = 'partial';
    public const TYPE_FULL = 'full';

    public const REFUND_NONE = 'none';
    public const REFUND_CASH = 'cash';
    public const REFUND_OTHER = 'other';

    protected $fillable = [
        'company_id',
        'store_id',
        'sale_id',
        'user_id',
        'cash_register_id',
        'number',
        'status',
        'return_type',
        'total',
        'refund_method',
        'refund_amount',
        'refund_reference',
        'reason',
        'notes',
        'returned_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'returned_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(
            Store::class
        );
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(
            Sale::class
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(
            CashRegister::class
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            SaleReturnItem::class
        );
    }
}