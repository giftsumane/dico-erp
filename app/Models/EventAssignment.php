<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAssignment extends Model
{
    public const ROLES = [
        'event_manager', 'warehouse_manager', 'warehouse_operator',
        'bar_stock_manager', 'cashier', 'attendant', 'bar_supervisor', 'delivery_operator',
    ];

    protected $fillable = [
        'event_id', 'event_bar_id', 'event_counter_id', 'user_id',
        'role', 'status', 'assigned_at', 'ended_at',
    ];

    protected $casts = ['assigned_at' => 'datetime', 'ended_at' => 'datetime'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bar(): BelongsTo
    {
        return $this->belongsTo(EventBar::class, 'event_bar_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(EventCounter::class, 'event_counter_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
