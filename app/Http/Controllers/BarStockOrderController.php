<?php

namespace App\Http\Controllers;

use App\Models\BarStockOrder;
use App\Models\BarStockSettlement;
use App\Models\Event;
use App\Models\EventCounter;
use App\Models\Product;
use App\Services\BarStockOrderService;
use App\Services\EventAuthorizer;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BarStockOrderController extends Controller
{
    public function index(Event $event, EventAuthorizer $authorizer)
    {
        $this->ensureEvent($event);
        $authorizer->authorize(auth()->user(), $event, [
            'event_manager', 'warehouse_manager', 'warehouse_operator',
            'bar_stock_manager', 'bar_supervisor', 'delivery_operator',
        ]);

        $ordersQuery = BarStockOrder::query()
            ->with(['bar', 'requester'])
            ->where('event_id', $event->id);

        if (! $this->hasGlobalEventAccess($event)) {
            $ordersQuery->whereIn('event_bar_id', $this->assignedBarIds($event));
        }

        $orders = $ordersQuery
            ->latest()
            ->paginate(25);

        return view('bar-stock-orders.index', compact('event', 'orders'));
    }

    public function create(Event $event, EventAuthorizer $authorizer)
    {
        $this->ensureEvent($event);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'bar_stock_manager', 'bar_supervisor']);

        if ($this->hasGlobalEventAccess($event)) {
            $event->load('bars.counters');
        } else {
            $barIds = $this->assignedBarIds($event);
            $event->load([
                'bars' => fn ($query) => $query->whereIn('id', $barIds),
                'bars.counters',
            ]);
        }
        $products = Product::query()
            ->where('company_id', $event->company_id)
            ->where('active', true)
            ->where('track_stock', true)
            ->where('inventory_mode', Product::INVENTORY_SIMPLE)
            ->whereIn('product_type', [Product::TYPE_SIMPLE, Product::TYPE_VARIANT])
            ->orderBy('name')
            ->get();

        return view('bar-stock-orders.create', [
            'event' => $event,
            'products' => $products,
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(Request $request, Event $event, EventAuthorizer $authorizer)
    {
        $this->ensureEvent($event);

        $data = $request->validate([
            'event_bar_id' => ['required', 'integer'],
            'idempotency_key' => ['required', 'uuid'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.event_counter_id' => ['required', 'integer'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.reserve_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bar = $event->bars()->findOrFail($data['event_bar_id']);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'bar_stock_manager', 'bar_supervisor'], $bar->id);

        $existing = BarStockOrder::query()
            ->where('company_id', $event->company_id)
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();
        if ($existing) {
            return redirect()->route('events.stock-orders.show', [$event, $existing]);
        }

        $order = DB::transaction(function () use ($data, $event, $bar) {
            $isInitial = ! BarStockOrder::query()
                ->where('event_bar_id', $bar->id)
                ->where('status', '!=', BarStockOrder::STATUS_CANCELLED)
                ->exists();
            $order = BarStockOrder::firstOrCreate(
                [
                    'company_id' => $event->company_id,
                    'idempotency_key' => $data['idempotency_key'],
                ],
                [
                    'event_id' => $event->id,
                    'event_bar_id' => $bar->id,
                    'warehouse_store_id' => $event->warehouse_store_id,
                    'requested_by' => auth()->id(),
                    'number' => 'E'.$event->id.'-B'.$bar->id.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                    'status' => BarStockOrder::STATUS_REQUESTED,
                    'initial_allocation' => $isInitial,
                    'notes' => $data['notes'] ?? null,
                ]
            );

            if (! $order->wasRecentlyCreated) {
                return $order;
            }

            foreach ($data['items'] as $row) {
                $counter = EventCounter::query()
                    ->where('event_bar_id', $bar->id)
                    ->findOrFail($row['event_counter_id']);
                $product = Product::query()
                    ->where('company_id', $event->company_id)
                    ->where('active', true)
                    ->where('track_stock', true)
                    ->where('inventory_mode', Product::INVENTORY_SIMPLE)
                    ->findOrFail($row['product_id']);

                $reserve = round((float) ($row['reserve_quantity'] ?? 0), 3);
                $quantity = round((float) $row['quantity'], 3);
                if ($reserve > $quantity) {
                    throw ValidationException::withMessages(['items' => 'A reserva não pode ser superior à quantidade pedida.']);
                }
                if (! $isInitial && $reserve > 0) {
                    throw ValidationException::withMessages(['items' => 'A reserva só pode ser definida na primeira encomenda do bar.']);
                }

                $order->items()->create([
                    'event_counter_id' => $counter->id,
                    'product_id' => $product->id,
                    'quantity_requested' => $quantity,
                    'reserve_quantity' => $reserve,
                ]);
            }

            return $order;
        });

        return redirect()->route('events.stock-orders.show', [$event, $order])
            ->with('success', 'Encomenda criada e enviada para aprovação.');
    }

    public function show(Event $event, BarStockOrder $stockOrder, EventAuthorizer $authorizer)
    {
        $this->ensureOrder($event, $stockOrder);
        $authorizer->authorize(auth()->user(), $event, [
            'event_manager', 'warehouse_manager', 'warehouse_operator',
            'bar_stock_manager', 'bar_supervisor', 'delivery_operator',
        ], $stockOrder->event_bar_id);

        $stockOrder->load([
            'bar', 'warehouseStore', 'requester', 'approver',
            'items.product', 'items.counter', 'settlements.payer', 'settlements.confirmer',
        ]);

        return view('bar-stock-orders.show', compact('event', 'stockOrder'));
    }

    public function approve(Event $event, BarStockOrder $stockOrder, EventAuthorizer $authorizer, BarStockOrderService $service)
    {
        $this->ensureOrder($event, $stockOrder);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'warehouse_manager']);
        $service->approve($stockOrder, auth()->id());

        return back()->with('success', 'Encomenda aprovada e valorizada.');
    }

    public function prepare(Event $event, BarStockOrder $stockOrder, EventAuthorizer $authorizer, BarStockOrderService $service)
    {
        $this->ensureOrder($event, $stockOrder);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'warehouse_manager', 'warehouse_operator']);
        $service->startPreparing($stockOrder, auth()->id());

        return back()->with('success', 'Preparação iniciada.');
    }

    public function dispatch(Event $event, BarStockOrder $stockOrder, EventAuthorizer $authorizer, BarStockOrderService $service, StockService $stockService)
    {
        $this->ensureOrder($event, $stockOrder);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'warehouse_manager', 'warehouse_operator']);
        $service->dispatch($stockOrder, auth()->id(), $stockService);

        return back()->with('success', 'Encomenda despachada e stock retirado do armazém.');
    }

    public function receive(Request $request, Event $event, BarStockOrder $stockOrder, EventAuthorizer $authorizer, BarStockOrderService $service, StockService $stockService)
    {
        $this->ensureOrder($event, $stockOrder);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'bar_stock_manager', 'bar_supervisor'], $stockOrder->event_bar_id);
        $data = $request->validate(['received' => ['required', 'array'], 'received.*' => ['nullable', 'numeric', 'min:0']]);
        $service->receive($stockOrder, $data['received'], auth()->id(), $stockService);

        return back()->with('success', 'Recepção registada nos stocks dos balcões.');
    }

    public function storeSettlement(Request $request, Event $event, BarStockOrder $stockOrder, EventAuthorizer $authorizer)
    {
        $this->ensureOrder($event, $stockOrder);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'bar_stock_manager'], $stockOrder->event_bar_id);

        if (! in_array($stockOrder->status, [
            BarStockOrder::STATUS_DISPATCHED,
            BarStockOrder::STATUS_PARTIALLY_RECEIVED,
            BarStockOrder::STATUS_RECEIVED,
        ], true)) {
            throw ValidationException::withMessages([
                'amount' => 'O pagamento só pode ser registado depois do despacho da mercadoria.',
            ]);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:'.$stockOrder->amount_due],
            'method' => ['required', Rule::in(['cash', 'mpesa', 'emola', 'bank_transfer', 'other'])],
            'reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $stockOrder->settlements()->create([
            ...$data,
            'paid_by' => auth()->id(),
            'status' => 'pending',
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Pagamento registado. Aguarda confirmação do armazém.');
    }

    public function confirmSettlement(Event $event, BarStockOrder $stockOrder, BarStockSettlement $settlement, EventAuthorizer $authorizer, BarStockOrderService $service)
    {
        $this->ensureOrder($event, $stockOrder);
        abort_unless((int) $settlement->bar_stock_order_id === (int) $stockOrder->id, 404);
        $authorizer->authorize(auth()->user(), $event, ['event_manager', 'warehouse_manager']);
        $service->confirmSettlement($settlement, auth()->id());

        return back()->with('success', 'Pagamento confirmado pelo armazém.');
    }

    private function ensureEvent(Event $event): void
    {
        abort_unless((int) $event->company_id === (int) app('activeCompany')->id, 403);
    }

    private function ensureOrder(Event $event, BarStockOrder $order): void
    {
        $this->ensureEvent($event);
        abort_unless((int) $order->event_id === (int) $event->id && (int) $order->company_id === (int) $event->company_id, 404);
    }

    private function hasGlobalEventAccess(Event $event): bool
    {
        if (in_array(app('activeRole'), ['owner', 'admin', 'manager'], true)) {
            return true;
        }

        return $event->assignments()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->whereNull('event_bar_id')
            ->whereIn('role', [
                'event_manager', 'warehouse_manager', 'warehouse_operator', 'delivery_operator',
            ])
            ->exists();
    }

    private function assignedBarIds(Event $event): array
    {
        return $event->assignments()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->whereIn('role', ['bar_stock_manager', 'bar_supervisor'])
            ->whereNotNull('event_bar_id')
            ->pluck('event_bar_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
