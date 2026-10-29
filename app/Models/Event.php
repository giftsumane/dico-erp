<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PREPARED = 'prepared';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSING = 'closing';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'company_id', 'warehouse_store_id', 'code', 'name', 'venue',
        'starts_at', 'ends_at', 'timezone', 'status', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouseStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'warehouse_store_id');
    }

    public function bars(): HasMany
    {
        return $this->hasMany(EventBar::class)->orderBy('position')->orderBy('name');
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
