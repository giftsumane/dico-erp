<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAssignment;
use App\Models\EventBar;
use App\Models\EventCounter;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventOperationController extends Controller
{
    public function storeBar(Request $request, Event $event)
    {
        $this->ensureEventAccess($event);

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('event_bars')->where(fn ($query) => $query->where('event_id', $event->id)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $event->bars()->create([...$data, 'position' => $data['position'] ?? 0, 'status' => 'active']);

        return back()->with('success', 'Bar adicionado ao evento.');
    }

    public function storeCounter(Request $request, Event $event, EventBar $bar)
    {
        $this->ensureEventAccess($event);
        abort_unless((int) $bar->event_id === (int) $event->id, 404);

        $company = app('activeCompany');
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('event_counters')->where(fn ($query) => $query->where('event_bar_id', $bar->id)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'position' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $company, $event, $bar) {
            $storeCode = strtoupper($event->code.'-'.$bar->code.'-'.$data['code']);

            if (Store::query()->where('company_id', $company->id)->where('code', $storeCode)->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Já existe uma loja de stock com o código operacional '.$storeCode.'.',
                ]);
            }

            $store = Store::create([
                'company_id' => $company->id,
                'name' => $event->name.' — '.$bar->name.' — '.$data['name'],
                'code' => $storeCode,
                'is_main' => false,
                'status' => 'active',
            ]);

            $store->users()->syncWithoutDetaching([
                auth()->id() => ['status' => 'active'],
            ]);

            $bar->counters()->create([
                ...$data,
                'store_id' => $store->id,
                'position' => $data['position'] ?? 0,
                'status' => 'active',
            ]);
        });

        return back()->with('success', 'Balcão criado com stock e caixa independentes.');
    }

    public function storeAssignment(Request $request, Event $event)
    {
        $this->ensureEventAccess($event);

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'role' => ['required', Rule::in(EventAssignment::ROLES)],
            'event_bar_id' => ['nullable', 'integer'],
            'event_counter_id' => ['nullable', 'integer'],
        ]);

        $company = app('activeCompany');
        $user = $company->users()
            ->where('users.id', $data['user_id'])
            ->wherePivot('status', 'active')
            ->firstOrFail();

        $bar = null;
        $counter = null;

        if (! empty($data['event_bar_id'])) {
            $bar = $event->bars()->findOrFail($data['event_bar_id']);
        }

        if (! empty($data['event_counter_id'])) {
            $counter = EventCounter::query()
                ->whereKey($data['event_counter_id'])
                ->whereHas('bar', fn ($query) => $query->where('event_id', $event->id))
                ->firstOrFail();
            $bar = $counter->bar;
        }

        $this->validateAssignmentScope($data['role'], $bar, $counter);

        $exists = EventAssignment::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('role', $data['role'])
            ->where('event_bar_id', $bar?->id)
            ->where('event_counter_id', $counter?->id)
            ->where('status', 'active')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['user_id' => 'Esta função já está atribuída ao utilizador.']);
        }

        EventAssignment::create([
            'event_id' => $event->id,
            'event_bar_id' => $bar?->id,
            'event_counter_id' => $counter?->id,
            'user_id' => $user->id,
            'role' => $data['role'],
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        if ($counter) {
            $counter->store->users()->syncWithoutDetaching([
                $user->id => ['status' => 'active'],
            ]);
        }

        if (in_array($data['role'], ['event_manager', 'warehouse_manager', 'warehouse_operator'], true)) {
            $event->warehouseStore->users()->syncWithoutDetaching([
                $user->id => ['status' => 'active'],
            ]);
        }

        return back()->with('success', 'Responsabilidade operacional atribuída.');
    }

    private function validateAssignmentScope(string $role, ?EventBar $bar, ?EventCounter $counter): void
    {
        $counterRoles = ['cashier', 'attendant'];
        $barRoles = ['bar_stock_manager', 'bar_supervisor'];

        if (in_array($role, $counterRoles, true) && ! $counter) {
            throw ValidationException::withMessages(['event_counter_id' => 'Esta função exige um balcão.']);
        }

        if (in_array($role, $barRoles, true) && ! $bar) {
            throw ValidationException::withMessages(['event_bar_id' => 'Esta função exige um bar.']);
        }

        if (in_array($role, ['warehouse_manager', 'warehouse_operator'], true) && ($bar || $counter)) {
            throw ValidationException::withMessages(['role' => 'A função do armazém pertence ao evento, não a um bar ou balcão.']);
        }
    }

    private function ensureEventAccess(Event $event): void
    {
        abort_unless((int) $event->company_id === (int) app('activeCompany')->id, 403);
    }
}
