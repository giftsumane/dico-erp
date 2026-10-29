<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventBar extends Model
{
    protected $fillable = ['event_id', 'code', 'name', 'position', 'status', 'notes'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function counters(): HasMany
    {
        return $this->hasMany(EventCounter::class)->orderBy('position')->orderBy('name');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EventAssignment::class);
    }

    public function stockOrders(): HasMany
    {
        return $this->hasMany(BarStockOrder::class);
    }
}
