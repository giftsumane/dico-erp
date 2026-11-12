<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException(
            'Os registos de auditoria não podem ser alterados.'
        ));
        static::deleting(fn () => throw new LogicException(
            'Os registos de auditoria não podem ser eliminados.'
        ));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
