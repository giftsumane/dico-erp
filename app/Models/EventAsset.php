<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAsset extends Model
{
    protected $fillable = [
        'event_id', 'event_bar_id', 'event_counter_id', 'responsible_user_id',
        'type', 'code', 'name', 'status', 'delivered_at', 'returned_at', 'notes',
    ];

    protected $casts = ['delivered_at' => 'datetime', 'returned_at' => 'datetime'];

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

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
