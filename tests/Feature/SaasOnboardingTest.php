<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ServiceModule;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_creates_company_owner_store_and_trial_subscription(): void
    {
        $plan = SubscriptionPlan::where('slug', 'faturacao')->firstOrFail();

        $this->get(route('register.company.create'))
            ->assertOk()->assertSee('Crie a sua empresa no DICO')->assertSee('Faturação');

        $response = $this->post(route('register.company.store'), [
            'owner_name' => 'Ana Gestora', 'email' => 'ana@example.test', 'phone' => '841234567',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'company_name' => 'Empresa Ana', 'business_type' => 'services',
            'selection_type' => 'plan', 'subscription_plan_id' => $plan->id,
            'billing_cycle' => 'monthly', 'terms' => '1',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::where('email', 'ana@example.test')->firstOrFail();
        $company = Company::where('name', 'Empresa Ana')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($company->users()->whereKey($user->id)->exists());
        $this->assertTrue($company->stores()->where('is_main', true)->exists());
        $subscription = $company->currentSubscription()->with('modules')->firstOrFail();
        $this->assertSame('trial', $subscription->status);
        $this->assertSame('faturacao', $subscription->plan->slug);
        $this->assertTrue($subscription->modules->contains('key', 'invoicing'));
        $this->assertTrue($company->hasFeature('sales'));
        $this->assertFalse($company->hasFeature('pos'));
        $this->assertDatabaseHas('audit_logs', ['company_id' => $company->id, 'action' => 'company.registered']);
    }

    public function test_custom_pos_selection_adds_required_invoicing_dependency(): void
    {
        $pos = ServiceModule::where('key', 'pos')->firstOrFail();
        $this->post(route('register.company.store'), [
            'owner_name' => 'Paulo Loja', 'email' => 'paulo@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'company_name' => 'Loja Paulo', 'business_type' => 'retail',
            'selection_type' => 'custom', 'modules' => [$pos->id],
            'billing_cycle' => 'yearly', 'terms' => '1',
        ])->assertRedirect(route('dashboard'));

        $company = Company::where('name', 'Loja Paulo')->firstOrFail();
        $keys = $company->currentSubscription()->firstOrFail()->modules()->pluck('key')->all();
        $this->assertContains('pos', $keys);
        $this->assertContains('invoicing', $keys);
        $this->assertTrue($company->hasFeature('pos'));
        $this->assertTrue($company->hasFeature('sales'));
    }
}
