<?php

namespace App\Services;

use App\Models\BarStockOrder;
use App\Models\BarStockSettlement;
use App\Models\CounterStockPolicy;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BarStockOrderService
{
    public function approve(BarStockOrder $order, int $userId): BarStockOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = BarStockOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($order, BarStockOrder::STATUS_REQUESTED);

            if ((int) $order->requested_by === $userId) {
                throw ValidationException::withMessages(['order' => 'Quem pediu não pode aprovar a mesma encomenda.']);
            }

            $unpaidPrevious = BarStockOrder::query()
                ->where('event_bar_id', $order->event_bar_id)
                ->where('id', '!=', $order->id)
                ->whereNotNull('dispatched_at')
                ->where('amount_due', '>', 0)
                ->whereNotIn('status', [BarStockOrder::STATUS_CANCELLED])
                ->exists();

            if ($unpaidPrevious) {
                throw ValidationException::withMessages([
                    'order' => 'A entrega anterior deve ser integralmente liquidada antes desta aprovação.',
                ]);
            }

            $total = 0;
            foreach ($order->items()->with('product')->lockForUpdate()->get() as $item) {
                $quantity = (float) $item->quantity_requested;
                $unitCost = (float) $item->product->purchase_price;
                $lineTotal = round($quantity * $unitCost, 2);
                $item->update([
                    'quantity_approved' => $quantity,
                    'unit_cost' => $unitCost,
                    'total' => $lineTotal,
                ]);
                $total += $lineTotal;
            }

            $order->update([
                'approved_by' => $userId,
                'approved_at' => now(),
                'status' => BarStockOrder::STATUS_APPROVED,
                'total' => round($total, 2),
                'amount_due' => round($total, 2),
            ]);

            return $order->fresh('items');
        });
    }

    public function startPreparing(BarStockOrder $order, int $userId): BarStockOrder
    {
        return DB::transaction(function () use ($order, $userId) {
            $order = BarStockOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($order, BarStockOrder::STATUS_APPROVED);
            $order->update(['prepared_by' => $userId, 'prepared_at' => now(), 'status' => BarStockOrder::STATUS_PREPARING]);

            return $order->fresh();
        });
    }

    public function dispatch(BarStockOrder $order, int $userId, StockService $stockService): BarStockOrder
    {
        return DB::transaction(function () use ($order, $userId, $stockService) {
            $order = BarStockOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $this->requireStatus($order, BarStockOrder::STATUS_PREPARING);

            foreach ($order->items()->with('product')->lockForUpdate()->get() as $item) {
                $quantity = (float) $item->quantity_approved;
                $product = $item->product;

                if ($product->inventory_mode === Product::INVENTORY_BATCH) {
                    throw ValidationException::withMessages([
                        'order' => 'O despacho por lote será activado na etapa de lotes. Este produto não pode ser despachado neste fluxo ainda: '.$product->name,
                    ]);
                }

                $stockService->remove(
                    $product,
                    $order->warehouseStore,
                    $quantity,
                    'transfer_out',
                    'Despacho para '.$order->bar->name,
                    $order->number,
                    BarStockOrder::class,
                    $order->id
                );

                $item->update(['quantity_dispatched' => $quantity]);
            }

            $order->update([
                'dispatched_by' => $userId,
                'dispatched_at' => now(),
                'status' => BarStockOrder::STATUS_DISPATCHED,
            ]);

            return $order->fresh('items');
        });
    }

    public function receive(BarStockOrder $order, array $received, int $userId, StockService $stockService): BarStockOrder
    {
        return DB::transaction(function () use ($order, $received, $userId, $stockService) {
            $order = BarStockOrder::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (! in_array($order->status, [BarStockOrder::STATUS_DISPATCHED, BarStockOrder::STATUS_PARTIALLY_RECEIVED], true)) {
                throw ValidationException::withMessages(['order' => 'A encomenda não está disponível para recepção.']);
            }

            $receivedAnything = false;

            foreach ($order->items()->with(['product', 'counter.store'])->lockForUpdate()->get() as $item) {
                $quantity = round((float) ($received[$item->id] ?? 0), 3);
                $remaining = round((float) $item->quantity_dispatched - (float) $item->quantity_received, 3);

                if ($quantity < 0 || $quantity > $remaining + 0.0005) {
                    throw ValidationException::withMessages(['items' => 'Quantidade recebida inválida para '.$item->product->name.'.']);
                }

                if ($quantity <= 0) {
                    continue;
                }

                $receivedAnything = true;

                $stockService->add(
                    $item->product,
                    $item->counter->store,
                    $quantity,
                    'transfer_in',
                    'Recepção da encomenda '.$order->number,
                    $order->number,
                    BarStockOrder::class,
                    $order->id
                );

                $item->update(['quantity_received' => round((float) $item->quantity_received + $quantity, 3)]);

                if ((float) $item->reserve_quantity > 0) {
                    CounterStockPolicy::updateOrCreate(
                        ['event_counter_id' => $item->event_counter_id, 'product_id' => $item->product_id],
                        [
                            'reserve_quantity' => $item->reserve_quantity,
                            'reorder_level' => $item->reserve_quantity,
                        ]
                    );
                }
            }

            if (! $receivedAnything) {
                throw ValidationException::withMessages(['items' => 'Indique pelo menos uma quantidade efectivamente recebida.']);
            }

            $allReceived = $order->items()->get()->every(
                fn ($item) => (float) $item->quantity_received + 0.0005 >= (float) $item->quantity_dispatched
            );

            $close = $allReceived && (float) $order->amount_due <= 0;

            $order->update([
                'received_by' => $userId,
                'received_at' => $allReceived ? now() : null,
                'status' => $close
                    ? BarStockOrder::STATUS_CLOSED
                    : ($allReceived ? BarStockOrder::STATUS_RECEIVED : BarStockOrder::STATUS_PARTIALLY_RECEIVED),
                'closed_at' => $close ? now() : null,
            ]);

            return $order->fresh('items');
        });
    }

    public function confirmSettlement(BarStockSettlement $settlement, int $userId): BarStockSettlement
    {
        return DB::transaction(function () use ($settlement, $userId) {
            $settlement = BarStockSettlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();

            if ($settlement->status !== 'pending') {
                throw ValidationException::withMessages(['settlement' => 'Este pagamento já foi processado.']);
            }

            if ((int) $settlement->paid_by === $userId) {
                throw ValidationException::withMessages(['settlement' => 'Quem registou o pagamento não pode confirmar a recepção.']);
            }

            $order = BarStockOrder::query()->whereKey($settlement->bar_stock_order_id)->lockForUpdate()->firstOrFail();
            $amount = (float) $settlement->amount;
            $due = (float) $order->amount_due;

            if ($amount > $due + 0.005) {
                throw ValidationException::withMessages(['amount' => 'O pagamento é superior ao saldo da encomenda.']);
            }

            $newPaid = round((float) $order->paid_total + $amount, 2);
            $newDue = max(0, round((float) $order->total - $newPaid, 2));
            $close = $newDue <= 0 && $order->status === BarStockOrder::STATUS_RECEIVED;

            $settlement->update(['confirmed_by' => $userId, 'confirmed_at' => now(), 'status' => 'confirmed']);
            $order->update([
                'paid_total' => $newPaid,
                'amount_due' => $newDue,
                'status' => $close ? BarStockOrder::STATUS_CLOSED : $order->status,
                'closed_at' => $close ? now() : $order->closed_at,
            ]);

            return $settlement->fresh();
        });
    }

    private function requireStatus(BarStockOrder $order, string $status): void
    {
        if ($order->status !== $status) {
            throw ValidationException::withMessages(['order' => 'A encomenda mudou de estado. Actualize a página.']);
        }
    }
}
