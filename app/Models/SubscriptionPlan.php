<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price_monthly', 'price_yearly', 'trial_days', 'max_users', 'max_stores', 'is_public', 'active', 'sort_order'];
    protected $casts = ['price_monthly' => 'decimal:2', 'price_yearly' => 'decimal:2', 'trial_days' => 'integer', 'max_users' => 'integer', 'max_stores' => 'integer', 'is_public' => 'boolean', 'active' => 'boolean'];
    public function modules() { return $this->belongsToMany(ServiceModule::class, 'plan_module')->orderBy('sort_order'); }
    public function subscriptions() { return $this->hasMany(CompanySubscription::class); }
}
