<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index()
    {
        $company = app('activeCompany');

        $events = Event::query()
            ->with('warehouseStore')
            ->withCount('bars')
            ->where('company_id', $company->id)
            ->latest('starts_at')
            ->paginate(20);

        return view('events.index', compact('events'));
    }

    public function create()
    {
        $company = app('activeCompany');
        $stores = Store::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('events.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'warehouse_store_id' => ['required', 'integer'],
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('events')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'name' => ['required', 'string', 'max:150'],
            'venue' => ['nullable', 'string', 'max:200'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'timezone' => ['required', 'timezone'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $warehouse = Store::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->findOrFail($data['warehouse_store_id']);

        $event = Event::create([
            ...$data,
            'company_id' => $company->id,
            'warehouse_store_id' => $warehouse->id,
            'status' => Event::STATUS_DRAFT,
        ]);

        return redirect()->route('events.show', $event)
            ->with('success', 'Evento criado. Configure agora os bares e balcões.');
    }

    public function show(Event $event)
    {
        $this->ensureAccess($event);

        $event->load([
            'warehouseStore',
            'bars.counters.store',
            'assignments.user',
            'assignments.bar',
            'assignments.counter',
        ]);

        $users = app('activeCompany')->users()
            ->wherePivot('status', 'active')
            ->orderBy('users.name')
            ->get();

        return view('events.show', compact('event', 'users'));
    }

    private function ensureAccess(Event $event): void
    {
        abort_unless((int) $event->company_id === (int) app('activeCompany')->id, 403);
    }
}
