<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Store;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
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

        foreach (['inventory', 'purchases', 'pos', 'sales', 'receivables'] as $feature) {
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
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'purchase.confirmed',
            'subject_id' => $purchase->id,
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('reports.stock.export'))
            ->assertOk()
            ->assertDownload();

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

        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'payment_type' => 'full',
            'payment_method' => 'cash',
            'amount_received' => 500,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
            ]],
        ];

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Dividir por dois métodos');

        $response = $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), $payload);

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

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), $payload)
            ->assertRedirect(route('pos.receipt', $sale));

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('8.000', Stock::firstOrFail()->quantity);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sale.completed',
            'subject_id' => $sale->id,
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('reports.export', ['period' => 'today']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_receivable_payment_is_idempotent_and_audited(): void
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Cliente a Crédito',
            'active' => true,
        ]);
        $sale = Sale::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'customer_id' => $customer->id,
            'user_id' => $this->owner->id,
            'number' => 'VND-CREDITO',
            'status' => 'completed',
            'payment_status' => 'partial',
            'subtotal' => 300,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 300,
            'paid_total' => 100,
            'amount_due' => 200,
            'due_date' => now()->addWeek()->toDateString(),
            'change_total' => 0,
            'completed_at' => now(),
        ]);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'method' => 'mpesa',
            'amount' => 50,
            'reference' => 'MPESA-001',
        ];

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('receivables.payments.store', $sale), $payload)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('receivables.payments.store', $sale), $payload)
            ->assertSessionHasNoErrors();

        $sale->refresh();
        $this->assertSame('150.00', $sale->paid_total);
        $this->assertSame('150.00', $sale->amount_due);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'receivable.payment_recorded',
            'subject_id' => $sale->id,
        ]);
    }

    public function test_pos_accepts_two_reconciled_payment_methods(): void
    {
        $product = $this->product(['sale_price' => 300]);
        Stock::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
        CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'status' => 'open',
            'opening_amount' => 0,
            'opened_at' => now(),
        ]);
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'payment_type' => 'full',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'payment_reference' => 'CAIXA',
            'secondary_payment_method' => 'mpesa',
            'secondary_amount_received' => 200,
            'secondary_payment_reference' => 'MPESA-ABC',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ];

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), $payload)
            ->assertSessionHasNoErrors();

        $sale = Sale::firstOrFail();
        $this->assertSame('300.00', $sale->paid_total);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertCount(2, $sale->payments);
        $this->assertSame('100.00', $sale->payments->firstWhere('method', 'cash')->amount);
        $this->assertSame('200.00', $sale->payments->firstWhere('method', 'mpesa')->amount);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('payments', 2);
        $this->assertSame('4.000', Stock::firstOrFail()->quantity);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('cash-registers.index'))
            ->assertOk()
            ->assertSee('Reconciliação por método')
            ->assertSee('200,00');

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('reports.cash.export'))
            ->assertOk()
            ->assertDownload();
    }

    public function test_partial_return_restores_stock_only_once(): void
    {
        $product = $this->product(['sale_price' => 100]);
        Stock::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
        CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'status' => 'open',
            'opening_amount' => 0,
            'opened_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), [
                'idempotency_key' => (string) Str::uuid(),
                'payment_type' => 'full',
                'payment_method' => 'cash',
                'amount_received' => 200,
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]],
            ])
            ->assertSessionHasNoErrors();

        $sale = Sale::with('items')->firstOrFail();
        $payload = [
            'idempotency_key' => (string) Str::uuid(),
            'refund_method' => 'none',
            'refund_amount' => 0,
            'reason' => 'Produto devolvido',
            'items' => [[
                'sale_item_id' => $sale->items->first()->id,
                'quantity' => 1,
            ]],
        ];

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('sales.returns.store', $sale), $payload)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('sales.returns.store', $sale), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sale_returns', 1);
        $this->assertDatabaseCount('sale_return_items', 1);
        $this->assertSame('4.000', Stock::firstOrFail()->quantity);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sale.return_completed',
        ]);
    }

    public function test_last_stock_unit_cannot_be_sold_twice(): void
    {
        $product = $this->product(['sale_price' => 100]);
        Stock::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'status' => 'open',
            'opening_amount' => 0,
            'opened_at' => now(),
        ]);
        $payload = [
            'payment_type' => 'full',
            'payment_method' => 'cash',
            'amount_received' => 100,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
            ]],
        ];

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), $payload + [
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('pos.complete'), $payload + [
                'idempotency_key' => (string) Str::uuid(),
            ])
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame('0.000', Stock::firstOrFail()->quantity);
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

    public function test_cashier_cannot_create_manual_cash_movement(): void
    {
        $cashier = User::factory()->create(['status' => 'active']);
        $this->company->users()->attach($cashier->id, [
            'is_owner' => false,
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $this->store->users()->attach($cashier->id, ['status' => 'active']);
        $register = CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $cashier->id,
            'status' => 'open',
            'opening_amount' => 100,
            'opened_at' => now(),
        ]);

        $this->actingAs($cashier)
            ->withSession($this->context())
            ->post(route('cash-registers.movements.store', $register), [
                'type' => 'out',
                'amount' => 10,
                'reason' => 'Retirada',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_cash_difference_requires_justification(): void
    {
        $register = CashRegister::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'status' => 'open',
            'opening_amount' => 100,
            'opened_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('cash-registers.close', $register), [
                'closing_amount' => 90,
            ])
            ->assertSessionHasErrors('closing_notes');

        $this->assertSame('open', $register->fresh()->status);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('cash-registers.close', $register), [
                'closing_amount' => 90,
                'closing_notes' => 'Falta de numerário confirmada na contagem.',
            ])
            ->assertSessionHasNoErrors();

        $register->refresh();
        $this->assertSame('closed', $register->status);
        $this->assertSame('-10.00', $register->difference);
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

    public function test_audit_trail_is_admin_only_exportable_and_immutable(): void
    {
        $log = AuditLog::create([
            'company_id' => $this->company->id,
            'store_id' => $this->store->id,
            'user_id' => $this->owner->id,
            'action' => 'test.operation',
            'subject_type' => Sale::class,
            'subject_id' => 99,
            'metadata' => ['reference' => 'TEST-99'],
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('audit-logs.index'))
            ->assertOk()
            ->assertSee('test.operation');

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('audit-logs.export'))
            ->assertOk()
            ->assertDownload();

        $cashier = User::factory()->create(['status' => 'active']);
        $this->company->users()->attach($cashier->id, [
            'is_owner' => false,
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $this->store->users()->attach($cashier->id, ['status' => 'active']);

        $this->actingAs($cashier)
            ->withSession($this->context())
            ->get(route('audit-logs.index'))
            ->assertForbidden();

        $this->expectException(\LogicException::class);
        $log->update(['action' => 'tampered']);
    }

    public function test_owner_can_create_an_isolated_company_with_main_store(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('companies.store'), [
                'name' => 'Segunda Empresa',
                'business_type' => 'retail',
                'store_name' => 'Loja Central',
                'store_code' => 'LC',
            ]);

        $company = Company::query()->where('name', 'Segunda Empresa')->firstOrFail();
        $store = $company->stores()->firstOrFail();
        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($company->users()->whereKey($this->owner->id)->exists());
        $this->assertTrue($store->users()->whereKey($this->owner->id)->exists());
        $this->assertTrue($store->is_main);
        $this->assertTrue($company->hasFeature('pos'));
        $this->assertSame($company->id, session('active_company_id'));
        $this->assertSame($store->id, session('active_store_id'));
        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'action' => 'company.created',
            'subject_id' => $company->id,
        ]);
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
