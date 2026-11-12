<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\Store;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CoreErpOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Store $store;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Empresa Principal',
            'status' => 'active',
        ]);
        $this->store = Store::create([
            'company_id' => $this->company->id,
            'name' => 'Loja Principal',
            'code' => 'LP',
            'is_main' => true,
            'status' => 'active',
        ]);
        $this->owner = User::factory()->create(['status' => 'active']);

        $this->company->users()->attach($this->owner->id, [
            'is_owner' => true,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->store->users()->attach($this->owner->id, ['status' => 'active']);

        foreach (['inventory', 'purchases', 'pos', 'sales'] as $feature) {
            $this->company->enableFeature($feature);
        }
    }

    public function test_confirming_purchase_updates_stock_once(): void
    {
        $product = $this->product();
        $purchase = Purchase::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'number' => 'CMP-000001',
            'purchase_date' => now()->toDateString(),
            'status' => 'draft',
            'subtotal' => 1000,
            'tax_total' => 0,
            'total' => 1000,
        ]);
        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'unit_cost' => 100,
            'tax_rate' => 0,
            'subtotal' => 1000,
            'tax_total' => 0,
            'total' => 1000,
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('purchases.confirm', $purchase))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('stocks', [
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
        $this->assertSame('confirmed', $purchase->fresh()->status);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('purchases.confirm', $purchase))
            ->assertSessionHasErrors('purchase');

        $this->assertSame('10.000', Stock::firstOrFail()->quantity);
    }

    public function test_paid_pos_sale_reduces_stock_and_enters_current_cash_register(): void
    {
        $product = $this->product(['sale_price' => 150]);
        Stock::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);
        $register = CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'status' => 'open',
            'opening_amount' => 500,
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), [
                'payment_type' => 'full',
                'payment_method' => 'cash',
                'amount_received' => 500,
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]],
            ]);

        $response->assertSessionHasNoErrors();
        $sale = $register->sales()->firstOrFail();
        $response->assertRedirect(route('pos.receipt', $sale));
        $this->assertSame('completed', $sale->status);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('8.000', Stock::firstOrFail()->quantity);
        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'cash_register_id' => $register->id,
            'method' => 'cash',
            'amount' => 300,
        ]);
    }

    public function test_operator_cannot_modify_another_operators_cash_register(): void
    {
        $other = User::factory()->create(['status' => 'active']);
        $this->company->users()->attach($other->id, [
            'is_owner' => false,
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $this->store->users()->attach($other->id, ['status' => 'active']);
        $register = CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $other->id,
            'status' => 'open',
            'opening_amount' => 0,
            'opened_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('cash-registers.close', $register), [
                'closing_amount' => 0,
            ])
            ->assertForbidden();

        $this->assertSame('open', $register->fresh()->status);
    }

    public function test_stock_service_rejects_product_from_another_company(): void
    {
        $otherCompany = Company::create(['name' => 'Outra', 'status' => 'active']);
        $foreignProduct = Product::create([
            'company_id' => $otherCompany->id,
            'product_type' => Product::TYPE_SIMPLE,
            'inventory_mode' => Product::INVENTORY_SIMPLE,
            'name' => 'Produto Externo',
            'purchase_price' => 10,
            'sale_price' => 20,
            'minimum_stock' => 0,
            'track_stock' => true,
            'active' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(StockService::class)->add($foreignProduct, $this->store, 1);
    }

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'company_id' => $this->company->id,
            'product_type' => Product::TYPE_SIMPLE,
            'inventory_mode' => Product::INVENTORY_SIMPLE,
            'name' => 'Produto Base',
            'purchase_price' => 100,
            'sale_price' => 150,
            'tax_rate' => 0,
            'minimum_stock' => 0,
            'track_stock' => true,
            'active' => true,
        ], $overrides));
    }

    private function context(): array
    {
        return [
            'active_company_id' => $this->company->id,
            'active_store_id' => $this->store->id,
        ];
    }
}
