<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', //nome usado dentro do Dico
        'legal_name', //denominação legal/fiscal
        'trade_name', //nome comercial/marca
        'nuit',
        'email',
        'phone',
        'address',
        'logo',
        'currency',
        'timezone',
        'default_tax_rate',
        'receipt_footer',
        'status',
        'business_type',
    ];


    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(
                'is_owner',
                'role',
                'status'
            )
            ->withTimestamps();
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    protected $casts = [
        'default_tax_rate' => 'decimal:2',
    ];

    public function features()
    {
        return $this->hasMany(
            CompanyFeature::class
        );
    }


    public function hasFeature(
        string $feature
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Se a relação já estiver carregada
        |--------------------------------------------------------------------------
        */

        if ($this->relationLoaded('features')) {
            return $this->features
                ->contains(
                    fn ($item) =>
                        $item->feature === $feature
                        && $item->enabled
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Caso contrário consultar BD
        |--------------------------------------------------------------------------
        */

        return $this->features()
            ->where(
                'feature',
                $feature
            )
            ->where(
                'enabled',
                true
            )
            ->exists();
        }


    public function enableFeature(
        string $feature
    ): void {
        $this->features()->updateOrCreate(
            [
                'feature' => $feature,
            ],
            [
                'enabled' => true,
            ]
        );

        /*
        * Forçar recarregamento posterior.
        */
        $this->unsetRelation(
            'features'
        );
    }


    public function disableFeature(
        string $feature
    ): void {
        $this->features()->updateOrCreate(
            [
                'feature' => $feature,
            ],
            [
                'enabled' => false,
            ]
        );

        $this->unsetRelation(
            'features'
        );
    }


    public function applyBusinessProfile(
        string $businessType
    ): void {
        $this->update([
            'business_type' =>
                $businessType,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Desactivar configuração anterior
        |--------------------------------------------------------------------------
        */

        foreach (
            \App\Support\CompanyFeatures::all()
            as $feature
        ) {
            $this->disableFeature(
                $feature
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Activar recomendadas
        |--------------------------------------------------------------------------
        */

        foreach (
            \App\Support\BusinessProfiles::defaults(
                $businessType
            )
            as $feature
        ) {
            $this->enableFeature(
                $feature
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Atributos de produtos
    |--------------------------------------------------------------------------
    */

    public function productAttributes(): HasMany
    {
        return $this->hasMany(
            ProductAttribute::class
        );
    }

    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

}