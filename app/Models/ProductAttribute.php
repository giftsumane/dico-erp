<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductAttribute extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'position',
        'active',
    ];

    protected $casts = [
        'position' => 'integer',
        'active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Empresa
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Valores
    |--------------------------------------------------------------------------
    |
    | Exemplo:
    |
    | Cor
    | ├── Preto
    | ├── Branco
    | └── Azul
    |
    */

    public function values(): HasMany
    {
        return $this->hasMany(
            ProductAttributeValue::class
        )
            ->orderBy('position')
            ->orderBy('value');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}