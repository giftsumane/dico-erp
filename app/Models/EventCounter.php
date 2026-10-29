<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCounter extends Model
{
    protected $fillable = ['event_bar_id', 'store_id', 'code', 'name', 'position', 'status', 'notes'];

    public function bar(): BelongsTo
    {
        return $this->belongsTo(EventBar::class, 'event_bar_id');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EventAssignment::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EventAsset::class);
    }
}
