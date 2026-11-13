<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\ServiceModule;
use App\Models\Store;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyOnboardingService
{
    public function register(array $data, string $ipAddress, ?string $userAgent): array
    {
        return DB::transaction(function () use ($data, $ipAddress, $userAgent) {
            $plan = ! empty($data['subscription_plan_id'])
                ? SubscriptionPlan::with('modules')->where('active', true)->where('is_public', true)->findOrFail($data['subscription_plan_id'])
                : null;
            $modules = $plan
                ? $plan->modules
                : ServiceModule::where('active', true)->whereIn('id', $data['modules'] ?? [])->get();
            $modules = $this->withDependencies($modules);

            $user = User::create([
                'name' => $data['owner_name'], 'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? null, 'password' => $data['password'], 'status' => 'active',
            ]);
            $company = Company::create([
                'name' => $data['company_name'], 'legal_name' => $data['legal_name'] ?? null,
                'trade_name' => $data['trade_name'] ?? null, 'nuit' => $data['nuit'] ?? null,
                'email' => $data['company_email'] ?? $data['email'], 'phone' => $data['phone'] ?? null,
                'business_type' => $data['business_type'], 'currency' => 'MZN',
                'timezone' => 'Africa/Maputo', 'status' => 'active',
            ]);
            $company->users()->attach($user->id, ['is_owner' => true, 'role' => 'owner', 'status' => 'active']);
            $store = Store::create([
                'company_id' => $company->id, 'name' => 'Loja Principal', 'code' => 'MAIN',
                'is_main' => true, 'status' => 'active',
            ]);
            $store->users()->attach($user->id, ['status' => 'active']);

            $trialDays = $plan?->trial_days ?? 14;
            $subscription = CompanySubscription::create([
                'company_id' => $company->id, 'subscription_plan_id' => $plan?->id,
                'status' => 'trial', 'billing_cycle' => $data['billing_cycle'] ?? 'monthly',
                'starts_at' => now(), 'trial_ends_at' => now()->addDays($trialDays),
                'notes' => $plan ? null : 'Adesão personalizada por módulos.',
            ]);
            $subscription->modules()->sync($modules->pluck('id'));
            $this->activateTechnicalFeatures($company, $modules);

            AuditLog::create([
                'company_id' => $company->id, 'store_id' => $store->id, 'user_id' => $user->id,
                'action' => 'company.registered', 'subject_type' => Company::class, 'subject_id' => $company->id,
                'metadata' => ['plan' => $plan?->slug ?? 'custom', 'modules' => $modules->pluck('key')->all(), 'trial_days' => $trialDays],
                'ip_address' => $ipAddress, 'user_agent' => mb_substr((string) $userAgent, 0, 500),
            ]);

            return compact('user', 'company', 'store', 'subscription');
        });
    }

    private function withDependencies(Collection $modules): Collection
    {
        $keys = $modules->pluck('key')->all();
        if (in_array('pos', $keys, true)) $keys[] = 'invoicing';
        if (in_array('purchases', $keys, true)) $keys[] = 'inventory';
        if (in_array('advanced_products', $keys, true)) $keys[] = 'inventory';
        if (in_array('event_bars', $keys, true)) $keys = array_merge($keys, ['invoicing', 'pos', 'inventory', 'purchases']);
        return ServiceModule::where('active', true)->whereIn('key', array_unique($keys))->orderBy('sort_order')->get();
    }

    private function activateTechnicalFeatures(Company $company, Collection $modules): void
    {
        foreach ($modules->pluck('feature_key')->filter()->unique() as $feature) {
            $company->enableFeature($feature);
        }
        if ($modules->contains('key', 'advanced_products')) {
            foreach (['brands', 'variants', 'stock_batches', 'expiry_dates'] as $feature) $company->enableFeature($feature);
        }
    }
}
