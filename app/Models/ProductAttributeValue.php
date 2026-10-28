<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductAttributeValue extends Model
{
    protected $fillable = [
        'product_attribute_id',
        'value',
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
    | Atributo
    |--------------------------------------------------------------------------
    |
    | Exemplo:
    |
    | Preto
    | ↓
    | Cor
    |
    */

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(
            ProductAttribute::class,
            'product_attribute_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Produtos / Variantes
    |--------------------------------------------------------------------------
    |
    | Liga o valor aos produtos do tipo variant através de:
    |
    | product_variant_values
    |
    */

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_variant_values',
            'product_attribute_value_id',
            'product_id'
        )
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}