<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ServiceModule;
use App\Models\SubscriptionPlan;
use App\Services\CompanyOnboardingService;
use App\Support\BusinessProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RegisterCompanyController extends Controller
{
    public function create()
    {
        $plans = SubscriptionPlan::with('modules')->where('active', true)->where('is_public', true)->orderBy('sort_order')->get();
        $modules = ServiceModule::where('active', true)->orderBy('sort_order')->get();
        $profiles = BusinessProfiles::labels();
        return view('auth.register-company', compact('plans', 'modules', 'profiles'));
    }

    public function store(Request $request, CompanyOnboardingService $service)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:150'],
            'nuit' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'business_type' => ['required', Rule::in(array_keys(BusinessProfiles::labels()))],
            'selection_type' => ['required', Rule::in(['plan', 'custom'])],
            'subscription_plan_id' => ['nullable', 'required_if:selection_type,plan', Rule::exists('subscription_plans', 'id')->where('active', true)->where('is_public', true)],
            'modules' => ['nullable', 'required_if:selection_type,custom', 'array', 'min:1'],
            'modules.*' => [Rule::exists('service_modules', 'id')->where('active', true)],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
            'terms' => ['accepted'],
        ]);

        $result = $service->register($data, $request->ip(), $request->userAgent());
        Auth::login($result['user']);
        $request->session()->regenerate();
        $request->session()->put([
            'active_company_id' => $result['company']->id,
            'active_store_id' => $result['store']->id,
        ]);

        return redirect()->route('dashboard')->with('success', 'A empresa foi criada. O período experimental já está activo.');
    }
}
