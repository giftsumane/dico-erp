<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(403, 'Nenhuma loja activa.');
        }

        $search = trim((string) $request->get('search'));

        $purchases = Purchase::query()
            ->with([
                'supplier',
                'user',
            ])
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('number', 'like', "%{$search}%")
                        ->orWhere('supplier_reference', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                            $supplierQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'purchases.index',
            compact(
                'purchases',
                'search'
            )
        );
    }

    public function create()
    {
        $company = app('activeCompany');

        $suppliers = Supplier::query()
            ->where('company_id', $company->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view(
            'purchases.create',
            compact('suppliers')
        );
    }

    public function store(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(403, 'Nenhuma loja activa.');
        }

        $data = $request->validate([
            'supplier_id' => [
                'nullable',
                'integer',
            ],

            'supplier_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $supplierId = null;

        if (! empty($data['supplier_id'])) {
            $supplierId = Supplier::query()
                ->where('company_id', $company->id)
                ->where('id', $data['supplier_id'])
                ->where('active', true)
                ->value('id');

            if (! $supplierId) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Fornecedor inválido.',
                ]);
            }
        }

        $purchase = Purchase::create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'supplier_id' => $supplierId,
            'user_id' => auth()->id(),
            'number' => null,
            'supplier_reference' =>
                $data['supplier_reference'] ?? null,
            'purchase_date' => $data['purchase_date'],
            'status' => 'draft',
            'subtotal' => 0,
            'tax_total' => 0,
            'total' => 0,
            'notes' => $data['notes'] ?? null,
        ]);

        $purchase->update([
            'number' => 'CMP-' . str_pad(
                (string) $purchase->id,
                6,
                '0',
                STR_PAD_LEFT
            ),
        ]);

        return redirect()
            ->route('purchases.show', $purchase)
            ->with(
                'success',
                'Compra criada. Adicione os produtos.'
            );
    }

    public function show(Purchase $purchase)
    {
        $this->ensurePurchaseAccess($purchase);

        $company = app('activeCompany');

        $hasStockBatches =
            $company->hasFeature('stock_batches');

        $hasExpiryDates =
            $company->hasFeature('expiry_dates');

        $purchase->load([
            'supplier',
            'store',
            'user',
            'items.stockBatch',
            'items.product.unit',
            'items.product.parent',
            'items.product.attributeValues.attribute',
        ]);

        $products = Product::query()
            ->with([
                'parent',
                'unit',
                'attributeValues.attribute',
            ])
            ->where('company_id', $company->id)
            ->where('active', true)
            ->whereIn('product_type', [
                Product::TYPE_SIMPLE,
                Product::TYPE_VARIANT,
            ])
            ->where(function ($query) {
                $query
                    ->where('product_type', '!=', Product::TYPE_VARIANT)
                    ->orWhereHas('parent', function ($parentQuery) {
                        $parentQuery
                            ->where('product_type', Product::TYPE_VARIABLE)
                            ->where('active', true);
                    });
            })
            ->orderBy('name')
            ->get();

        return view(
            'purchases.show',
            compact(
                'purchase',
                'products',
                'hasStockBatches',
                'hasExpiryDates'
            )
        );
    }

    public function addItem(
        Request $request,
        Purchase $purchase
    ) {
        $this->ensurePurchaseAccess($purchase);

        if ($purchase->status !== 'draft') {
            abort(
                422,
                'Só é possível alterar uma compra em rascunho.'
            );
        }

        $company = app('activeCompany');

        $data = $request->validate([
            'product_id' => [
                'required',
                'integer',
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'unit_cost' => [
                'required',
                'numeric',
                'min:0',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'batch_code' => [
                'nullable',
                'string',
                'max:100',
            ],

            'batch_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'batch_received_at' => [
                'nullable',
                'date',
            ],

            'batch_expiry_date' => [
                'nullable',
                'date',
            ],
        ]);

        $product = Product::query()
            ->where('company_id', $company->id)
            ->where('id', $data['product_id'])
            ->where('active', true)
            ->whereIn('product_type', [
                Product::TYPE_SIMPLE,
                Product::TYPE_VARIANT,
            ])
            ->firstOrFail();

        if ($product->isVariant()) {
            $validParent = Product::query()
                ->where('company_id', $company->id)
                ->where('id', $product->parent_id)
                ->where('product_type', Product::TYPE_VARIABLE)
                ->where('active', true)
                ->exists();

            if (! $validParent) {
                throw ValidationException::withMessages([
                    'product_id' =>
                        'A variante seleccionada pertence a um produto principal indisponível.',
                ]);
            }
        }

        $isBatchProduct =
            $product->track_stock
            && $product->inventory_mode
                === Product::INVENTORY_BATCH;

        if ($isBatchProduct) {
            if (! $company->hasFeature('stock_batches')) {
                throw ValidationException::withMessages([
                    'product_id' =>
                        'O produto utiliza stock por lotes, mas esta funcionalidade não está activa.',
                ]);
            }

            $batchCode =
                trim((string) ($data['batch_code'] ?? ''));

            if ($batchCode === '') {
                throw ValidationException::withMessages([
                    'batch_code' =>
                        'Informe o código do lote.',
                ]);
            }

            $batchExists = StockBatch::query()
                ->where('company_id', $company->id)
                ->where('store_id', $purchase->store_id)
                ->where('product_id', $product->id)
                ->where('code', $batchCode)
                ->exists();

            if ($batchExists) {
                throw ValidationException::withMessages([
                    'batch_code' =>
                        'Já existe um lote com este código para este produto nesta loja.',
                ]);
            }

            $draftBatchExists = PurchaseItem::query()
                ->where('purchase_id', $purchase->id)
                ->where('product_id', $product->id)
                ->where('batch_code', $batchCode)
                ->exists();

            if ($draftBatchExists) {
                throw ValidationException::withMessages([
                    'batch_code' =>
                        'Este lote já foi adicionado a esta compra.',
                ]);
            }

            if (
                ! empty($data['batch_expiry_date'])
                && ! $company->hasFeature('expiry_dates')
            ) {
                throw ValidationException::withMessages([
                    'batch_expiry_date' =>
                        'O controlo de validade não está activo para esta empresa.',
                ]);
            }
        }

        $quantity = (float) $data['quantity'];
        $unitCost = (float) $data['unit_cost'];
        $taxRate = (float) ($data['tax_rate'] ?? 0);

        $subtotal = round(
            $quantity * $unitCost,
            2
        );

        $taxTotal = round(
            $subtotal * ($taxRate / 100),
            2
        );

        $total = $subtotal + $taxTotal;

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,

            'stock_batch_id' => null,

            'batch_code' =>
                $isBatchProduct
                    ? trim($data['batch_code'])
                    : null,

            'batch_name' =>
                $isBatchProduct
                && ! empty($data['batch_name'])
                    ? trim($data['batch_name'])
                    : null,

            'batch_received_at' =>
                $isBatchProduct
                    ? ($data['batch_received_at'] ?? null)
                    : null,

            'batch_expiry_date' =>
                $isBatchProduct
                    ? ($data['batch_expiry_date'] ?? null)
                    : null,

            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_rate' => $taxRate,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $total,
        ]);

        $this->recalculateTotals($purchase);

        return back()->with(
            'success',
            'Produto adicionado à compra.'
        );
    }

    public function removeItem(
        Purchase $purchase,
        PurchaseItem $item
    ) {
        $this->ensurePurchaseAccess($purchase);

        if ($purchase->status !== 'draft') {
            abort(
                422,
                'Só é possível alterar uma compra em rascunho.'
            );
        }

        abort_unless(
            $item->purchase_id === $purchase->id,
            404
        );

        $item->delete();

        $this->recalculateTotals($purchase);

        return back()->with(
            'success',
            'Produto removido da compra.'
        );
    }

    public function confirm(
        Purchase $purchase,
        StockService $stockService
    ) {
        $this->ensurePurchaseAccess($purchase);

        if ($purchase->status !== 'draft') {
            throw ValidationException::withMessages([
                'purchase' => 'Esta compra já foi processada.',
            ]);
        }

        $company = app('activeCompany');

        $purchase->load([
            'items.product',
            'store',
        ]);

        if ($purchase->items->isEmpty()) {
            throw ValidationException::withMessages([
                'purchase' =>
                    'Adicione pelo menos um produto antes de confirmar.',
            ]);
        }

        DB::transaction(function () use (
            $purchase,
            $stockService,
            $company
        ) {
            $lockedPurchase = Purchase::query()
                ->where('id', $purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPurchase->status !== 'draft') {
                throw ValidationException::withMessages([
                    'purchase' =>
                        'Esta compra já foi confirmada.',
                ]);
            }

            $lockedPurchase->load([
                'items.product',
                'store',
            ]);

            foreach ($lockedPurchase->items as $item) {
                $product = $item->product;

                if (! $product || ! $product->track_stock) {
                    continue;
                }

                if (
                    $product->inventory_mode
                    === Product::INVENTORY_BATCH
                ) {
                    if (! $company->hasFeature('stock_batches')) {
                        throw ValidationException::withMessages([
                            'purchase' =>
                                'A compra contém produtos por lotes, mas '
                                . 'a funcionalidade de lotes não está activa.',
                        ]);
                    }

                    if (empty($item->batch_code)) {
                        throw ValidationException::withMessages([
                            'purchase' =>
                                'A compra contém um produto por lotes sem código de lote.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Evitar lote duplicado no momento crítico da confirmação
                    |--------------------------------------------------------------------------
                    */

                    $batchAlreadyExists = StockBatch::query()
                        ->where('company_id', $company->id)
                        ->where('store_id', $lockedPurchase->store_id)
                        ->where('product_id', $product->id)
                        ->where('code', $item->batch_code)
                        ->exists();

                    if ($batchAlreadyExists) {
                        throw ValidationException::withMessages([
                            'purchase' =>
                                'Já existe o lote '
                                . $item->batch_code
                                . ' para o produto '
                                . $product->name
                                . ' nesta loja.',
                        ]);
                    }

                    $batch = $stockService->createBatchEntry(
                        product: $product,
                        store: $lockedPurchase->store,
                        code: $item->batch_code,
                        quantity: (float) $item->quantity,
                        name: $item->batch_name,
                        unitCost: (float) $item->unit_cost,
                        supplierId: $lockedPurchase->supplier_id,
                        receivedAt:
                            $item->batch_received_at
                                ? $item->batch_received_at->format('Y-m-d')
                                : $lockedPurchase
                                    ->purchase_date
                                    ?->format('Y-m-d'),
                        expiryDate:
                            $item->batch_expiry_date
                                ? $item->batch_expiry_date->format('Y-m-d')
                                : null,
                        notes:
                            'Entrada através da compra '
                            . $lockedPurchase->number,
                        movementType: 'purchase',
                        reference: $lockedPurchase->number,
                        referenceType: 'purchase',
                        referenceId: $lockedPurchase->id
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Ligar a linha da compra ao lote efectivamente criado
                    |--------------------------------------------------------------------------
                    */

                    $item->update([
                        'stock_batch_id' => $batch->id,
                    ]);

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Produto sem inventário
                |--------------------------------------------------------------------------
                */

                if (
                    $product->inventory_mode
                    === Product::INVENTORY_NONE
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Stock simples
                |--------------------------------------------------------------------------
                */

                $stockService->add(
                    $product,
                    $lockedPurchase->store,
                    (float) $item->quantity,
                    'purchase',
                    'Compra ' . $lockedPurchase->number,
                    $lockedPurchase->number,
                    'purchase',
                    $lockedPurchase->id
                );
            }

            $lockedPurchase->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);
        });

        return redirect()
            ->route('purchases.show', $purchase)
            ->with(
                'success',
                'Compra confirmada e stock actualizado.'
            );
    }

    private function recalculateTotals(
        Purchase $purchase
    ): void {
        $totals = PurchaseItem::query()
            ->where('purchase_id', $purchase->id)
            ->selectRaw('
                COALESCE(SUM(subtotal), 0) as subtotal,
                COALESCE(SUM(tax_total), 0) as tax_total,
                COALESCE(SUM(total), 0) as total
            ')
            ->first();

        $purchase->update([
            'subtotal' => $totals->subtotal,
            'tax_total' => $totals->tax_total,
            'total' => $totals->total,
        ]);
    }

    private function ensurePurchaseAccess(
        Purchase $purchase
    ): void {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless(
            $store
            && $purchase->company_id === $company->id
            && $purchase->store_id === $store->id,
            403
        );
    }
}
