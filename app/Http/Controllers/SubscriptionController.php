<?php

namespace App\Http\Controllers;

class SubscriptionController extends Controller
{
    public function show()
    {
        $company = app('activeCompany');
        $subscription = $company->currentSubscription()->with(['plan', 'modules'])->first();
        return view('settings.subscription', compact('company', 'subscription'));
    }
}
