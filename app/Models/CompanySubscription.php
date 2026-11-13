<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySubscription extends Model
{
    protected $fillable = ['company_id', 'subscription_plan_id', 'status', 'billing_cycle', 'agreed_price', 'starts_at', 'trial_ends_at', 'current_period_ends_at', 'cancelled_at', 'notes'];
    protected $casts = ['company_id' => 'integer', 'subscription_plan_id' => 'integer', 'agreed_price' => 'decimal:2', 'starts_at' => 'datetime', 'trial_ends_at' => 'datetime', 'current_period_ends_at' => 'datetime', 'cancelled_at' => 'datetime'];
    public function company() { return $this->belongsTo(Company::class); }
    public function plan() { return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id'); }
    public function modules() { return $this->belongsToMany(ServiceModule::class, 'company_subscription_module')->orderBy('sort_order'); }
    public function isUsable(): bool
    {
        return $this->status === 'active' || ($this->status === 'trial' && (! $this->trial_ends_at || $this->trial_ends_at->isFuture()));
    }
}
