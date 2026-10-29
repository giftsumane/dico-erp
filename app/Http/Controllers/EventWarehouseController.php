<?php

namespace App\Http\Controllers;

use App\Models\BarStockOrder;
use App\Models\BarStockSettlement;
use App\Models\Event;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\EventAuthorizer;
use App\Services\StockService;
use Illuminate\Http\Request;

class EventWarehouseController extends Controller
{
    public function index(Event $event, EventAuthorizer $authorizer)
    {
        $this->ensureEvent($event);
        $authorizer->authorize(auth()->user(), $event, [
            'event_manager', 'warehouse_manager', 'warehouse_operator',
        ]);

        $warehouse = $event->warehouseStore;

        $stocks = Stock::query()
            ->with(['product.unit'])
            ->where('company_id', $event->company_id)
            ->where('store_id', $warehouse->id)
            ->whereHas('product', fn ($query) => $query->where('active', true))
            ->orderByDesc('quantity')
            ->get();

        $products = Product::query()
            ->where('company_id', $event->company_id)
            ->where('active', true)
            ->where('track_stock', true)
            ->where('inventory_mode', Product::INVENTORY_SIMPLE)
            ->whereIn('product_type', [Product::TYPE_SIMPLE, Product::TYPE_VARIANT])
            ->orderBy('name')
            ->get();

        $orders = BarStockOrder::query()
            ->with(['bar', 'requester'])
            ->where('event_id', $event->id)
            ->whereNotIn('status', [BarStockOrder::STATUS_CLOSED, BarStockOrder::STATUS_CANCELLED])
            ->latest()
            ->get();

        $pendingSettlements = BarStockSettlement::query()
            ->with(['order.bar', 'payer'])
            ->where('status', 'pending')
            ->whereHas('order', fn ($query) => $query->where('event_id', $event->id))
            ->latest('paid_at')
            ->get();

        $movements = StockMovement::query()
            ->with(['product', 'user'])
            ->where('company_id', $event->company_id)
            ->where('store_id', $warehouse->id)
            ->latest('occurred_at')
            ->limit(20)
            ->get();

        $metrics = [
            'products' => $stocks->count(),
            'units' => (float) $stocks->sum('quantity'),
            'low_stock' => $stocks->filter(fn ($stock) => (float) $stock->quantity <= (float) $stock->product->minimum_stock)->count(),
            'orders_waiting' => $orders->whereIn('status', [
                BarStockOrder::STATUS_REQUESTED,
                BarStockOrder::STATUS_APPROVED,
                BarStockOrder::STATUS_PREPARING,
            ])->count(),
            'amount_due' => (float) BarStockOrder::query()
                ->where('event_id', $event->id)
                ->sum('amount_due'),
            'settlements_pending' => $pendingSettlements->count(),
        ];

        return view('event-warehouse.index', compact(
            'event', 'warehouse', 'stocks', 'products', 'orders',
            'pendingSettlements', 'movements', 'metrics'
        ));
    }

    public function receiveStock(
        Request $request,
        Event $event,
        EventAuthorizer $authorizer,
        StockService $stockService
    ) {
        $this->ensureEvent($event);
        $authorizer->authorize(auth()->user(), $event, [
            'event_manager', 'warehouse_manager', 'warehouse_operator',
        ]);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['required', 'string', 'max:1000'],
        ]);

        $product = Product::query()
            ->where('company_id', $event->company_id)
            ->where('active', true)
            ->where('track_stock', true)
            ->where('inventory_mode', Product::INVENTORY_SIMPLE)
            ->whereIn('product_type', [Product::TYPE_SIMPLE, Product::TYPE_VARIANT])
            ->findOrFail($data['product_id']);

        $stockService->add(
            $product,
            $event->warehouseStore,
            (float) $data['quantity'],
            'entry',
            'Entrada no armazém do evento: '.$data['notes'],
            $data['reference'] ?? null,
            Event::class,
            $event->id
        );

        return back()->with('success', 'Entrada registada no stock do armazém.');
    }

    private function ensureEvent(Event $event): void
    {
        abort_unless((int) $event->company_id === (int) app('activeCompany')->id, 403);
    }
}
