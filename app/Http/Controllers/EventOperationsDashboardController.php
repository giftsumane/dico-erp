<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventOperationsDashboardController extends Controller
{
    public function index()
    {
        $company = app('activeCompany');
        $role = app('activeRole');
        $userId = auth()->id();

        $events = Event::query()
            ->with(['warehouseStore', 'bars'])
            ->where('company_id', $company->id)
            ->whereNotIn('status', [Event::STATUS_CLOSED, Event::STATUS_CANCELLED])
            ->when(! in_array($role, ['owner', 'admin', 'manager'], true), function ($query) use ($userId) {
                $query->whereHas('assignments', function ($assignment) use ($userId) {
                    $assignment
                        ->where('user_id', $userId)
                        ->where('status', 'active')
                        ->whereIn('role', [
                            'event_manager', 'warehouse_manager', 'warehouse_operator',
                            'bar_stock_manager', 'bar_supervisor', 'delivery_operator',
                        ]);
                });
            })
            ->orderBy('starts_at')
            ->get();

        return view('event-operations.index', compact('events'));
    }
}
