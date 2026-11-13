<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceModule extends Model
{
    protected $fillable = ['key', 'name', 'feature_key', 'description', 'sort_order', 'active'];
    protected $casts = ['active' => 'boolean', 'sort_order' => 'integer'];
    public function plans() { return $this->belongsToMany(SubscriptionPlan::class, 'plan_module'); }
    public function subscriptions() { return $this->belongsToMany(CompanySubscription::class, 'company_subscription_module'); }
}
