<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Event;
use App\Models\EventBar;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventOperationsTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Store $warehouse;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'CSV Agência', 'status' => 'active']);
        $this->warehouse = Store::create([
            'company_id' => $this->company->id,
            'name' => 'Armazém Central',
            'code' => 'ARM',
            'is_main' => true,
            'status' => 'active',
        ]);
        $this->owner = User::factory()->create(['status' => 'active']);

        $this->company->users()->attach($this->owner->id, [
            'is_owner' => true,
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->warehouse->users()->attach($this->owner->id, ['status' => 'active']);
        $this->company->enableFeature('event_bars');
    }

    public function test_owner_can_create_event_for_company_warehouse(): void
    {
        $response = $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('events.store'), [
                'warehouse_store_id' => $this->warehouse->id,
                'code' => 'SONHO-2026',
                'name' => 'O Sonho Festival',
                'venue' => 'Maputo',
                'starts_at' => '2026-12-10 16:00:00',
                'ends_at' => '2026-12-11 05:00:00',
                'timezone' => 'Africa/Maputo',
            ]);

        $event = Event::firstOrFail();
        $response->assertRedirect(route('events.show', $event));
        $this->assertSame($this->company->id, $event->company_id);
        $this->assertSame($this->warehouse->id, $event->warehouse_store_id);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('O Sonho Festival');
    }

    public function test_counter_creation_creates_an_independent_stock_store(): void
    {
        $event = $this->createEvent();
        $bar = EventBar::create([
            'event_id' => $event->id,
            'code' => 'BAR-A',
            'name' => 'Bar A',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('events.counters.store', [$event, $bar]), [
                'code' => 'B1',
                'name' => 'Balcão 1',
                'position' => 1,
            ]);

        $response->assertSessionHasNoErrors();
        $counter = $bar->counters()->with('store')->firstOrFail();
        $this->assertSame('SONHO-2026-BAR-A-B1', $counter->store->code);
        $this->assertSame($this->company->id, $counter->store->company_id);
        $this->assertTrue($counter->store->users()->whereKey($this->owner->id)->exists());

        $cashier = User::factory()->create(['status' => 'active']);
        $this->company->users()->attach($cashier->id, [
            'is_owner' => false,
            'role' => 'cashier',
            'status' => 'active',
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->post(route('events.assignments.store', $event), [
                'user_id' => $cashier->id,
                'role' => 'cashier',
                'event_counter_id' => $counter->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($counter->store->users()->whereKey($cashier->id)->exists());
        $this->assertDatabaseHas('event_assignments', [
            'event_id' => $event->id,
            'event_bar_id' => $bar->id,
            'event_counter_id' => $counter->id,
            'user_id' => $cashier->id,
            'role' => 'cashier',
            'status' => 'active',
        ]);
    }

    public function test_event_from_another_company_is_forbidden(): void
    {
        $otherCompany = Company::create(['name' => 'Outra Empresa', 'status' => 'active']);
        $otherStore = Store::create([
            'company_id' => $otherCompany->id,
            'name' => 'Outro Armazém',
            'code' => 'OUTRO',
            'status' => 'active',
        ]);
        $otherEvent = Event::create([
            'company_id' => $otherCompany->id,
            'warehouse_store_id' => $otherStore->id,
            'code' => 'PRIVADO',
            'name' => 'Evento Privado',
            'starts_at' => now(),
            'timezone' => 'Africa/Maputo',
            'status' => 'draft',
        ]);

        $this->actingAs($this->owner)
            ->withSession($this->context())
            ->get(route('events.show', $otherEvent))
            ->assertForbidden();
    }

    private function createEvent(): Event
    {
        return Event::create([
            'company_id' => $this->company->id,
            'warehouse_store_id' => $this->warehouse->id,
            'code' => 'SONHO-2026',
            'name' => 'O Sonho Festival',
            'starts_at' => now(),
            'timezone' => 'Africa/Maputo',
            'status' => 'draft',
        ]);
    }

    private function context(): array
    {
        return [
            'active_company_id' => $this->company->id,
            'active_store_id' => $this->warehouse->id,
        ];
    }
}
