<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\StockBatch;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(403, 'Seleccione uma loja antes de consultar o stock.');
        }

        $search = trim((string) $request->get('search'));

        $stocks = Stock::query()
            ->with([
                'product.category',
                'product.brand',
                'product.unit',
                'product.parent',
                'product.attributeValues.attribute',
            ])
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->whereHas('product', function ($productQuery) use ($company) {
                $productQuery
                    ->where('company_id', $company->id)
                    ->whereIn('product_type', [
                        Product::TYPE_SIMPLE,
                        Product::TYPE_VARIANT,
                    ]);
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('product', function ($productQuery) use ($search) {
                    $productQuery->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhere('barcode', 'like', "%{$search}%")
                            ->orWhereHas('parent', function ($parentQuery) use ($search) {
                                $parentQuery->where('name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('attributeValues', function ($valueQuery) use ($search) {
                                $valueQuery->where('value', 'like', "%{$search}%");
                            });
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('stocks.index', compact('stocks', 'search'));
    }

    public function create()
    {
        $company = app('activeCompany');

        $products = Product::query()
            ->with([
                'parent',
                'attributeValues.attribute',
            ])
            ->where('company_id', $company->id)
            ->where('active', true)
            ->where('track_stock', true)
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

        $suppliers = Supplier::query()
            ->where('company_id', $company->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $hasStockBatches = $company->hasFeature('stock_batches');
        $hasExpiryDates = $company->hasFeature('expiry_dates');

        return view('stocks.create', compact(
            'products',
            'suppliers',
            'hasStockBatches',
            'hasExpiryDates'
        ));
    }

    public function store(
        Request $request,
        StockService $stockService
    ) {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(403, 'Nenhuma loja activa.');
        }

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $product = Product::query()
            ->where('company_id', $company->id)
            ->where('id', $data['product_id'])
            ->where('active', true)
            ->where('track_stock', true)
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

        if ($product->inventory_mode === Product::INVENTORY_BATCH) {
            if (! $company->hasFeature('stock_batches')) {
                throw ValidationException::withMessages([
                    'product_id' =>
                        'A funcionalidade de stock por lotes não está activa para esta empresa.',
                ]);
            }

            $batchData = $request->validate([
                'batch_code' => ['required', 'string', 'max:100'],
                'batch_name' => ['nullable', 'string', 'max:200'],
                'supplier_id' => ['nullable', 'integer'],
                'unit_cost' => ['nullable', 'numeric', 'min:0'],
                'received_at' => ['nullable', 'date'],
                'expiry_date' => ['nullable', 'date'],
            ]);

            $supplierId = null;

            if (! empty($batchData['supplier_id'])) {
                $supplier = Supplier::query()
                    ->where('company_id', $company->id)
                    ->where('id', $batchData['supplier_id'])
                    ->where('active', true)
                    ->first();

                if (! $supplier) {
                    throw ValidationException::withMessages([
                        'supplier_id' => 'O fornecedor seleccionado não é válido.',
                    ]);
                }

                $supplierId = $supplier->id;
            }

            $expiryDate = null;

            if ($company->hasFeature('expiry_dates')) {
                $expiryDate = $batchData['expiry_date'] ?? null;
            }

            $stockService->createBatchEntry(
                product: $product,
                store: $store,
                code: trim($batchData['batch_code']),
                quantity: (float) $data['quantity'],
                name: $batchData['batch_name'] ?? null,
                unitCost: (float) ($batchData['unit_cost'] ?? 0),
                supplierId: $supplierId,
                receivedAt: $batchData['received_at'] ?? null,
                expiryDate: $expiryDate,
                notes: $data['notes'] ?? null
            );

            return redirect()
                ->route('stocks.index')
                ->with('success', 'Entrada de stock por lote registada com sucesso.');
        }

        $stockService->add(
            $product,
            $store,
            (float) $data['quantity'],
            'entry',
            $data['notes'] ?? 'Entrada manual de stock'
        );

        return redirect()
            ->route('stocks.index')
            ->with('success', 'Entrada de stock registada com sucesso.');
    }

    /*
    |--------------------------------------------------------------------------
    | Ajustar stock
    |--------------------------------------------------------------------------
    */

    public function edit(Stock $stock)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Seleccione uma loja antes de ajustar o stock.'
            );
        }

        abort_unless(
            (int) $stock->company_id === (int) $company->id
            && (int) $stock->store_id === (int) $store->id,
            403
        );

        $stock->load([
            'product.parent',
            'product.attributeValues.attribute',
            'product.unit',
        ]);

        abort_unless(
            $stock->product,
            404
        );

        /*
        |--------------------------------------------------------------------------
        | Stock por lotes
        |--------------------------------------------------------------------------
        |
        | Não podemos alterar apenas stocks.quantity num produto batch.
        | Na Fase 3.3 o ajuste será feito no lote concreto.
        |
        */

        if (
            $stock->product->inventory_mode
            === Product::INVENTORY_BATCH
        ) {
            return redirect()
                ->route('stocks.index')
                ->withErrors([
                    'stock' =>
                        'Este produto utiliza stock por lotes. '
                        . 'O ajuste deve ser feito no lote específico.',
                ]);
        }

        return view(
            'stocks.edit',
            compact('stock')
        );
    }


    public function update(
        Request $request,
        Stock $stock,
        StockService $stockService
    ) {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Seleccione uma loja antes de ajustar o stock.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Segurança empresa / loja
        |--------------------------------------------------------------------------
        */

        abort_unless(
            (int) $stock->company_id === (int) $company->id
            && (int) $stock->store_id === (int) $store->id,
            403
        );

        $stock->load('product');

        $product = $stock->product;

        abort_unless(
            $product
            && (int) $product->company_id === (int) $company->id
            && $product->track_stock
            && in_array(
                $product->product_type,
                [
                    Product::TYPE_SIMPLE,
                    Product::TYPE_VARIANT,
                ],
                true
            ),
            422
        );

        /*
        |--------------------------------------------------------------------------
        | Batch será ajustado pelo lote
        |--------------------------------------------------------------------------
        */

        if (
            $product->inventory_mode
            === Product::INVENTORY_BATCH
        ) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'Este produto utiliza stock por lotes. '
                    . 'Ajuste a quantidade no lote específico.',
            ]);
        }

        $data = $request->validate([
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Calcular diferença
        |--------------------------------------------------------------------------
        */

        $currentQuantity =
            (float) $stock->quantity;

        $newQuantity =
            (float) $data['quantity'];

        $difference = round(
            $newQuantity - $currentQuantity,
            3
        );

        /*
        |--------------------------------------------------------------------------
        | Nada mudou
        |--------------------------------------------------------------------------
        */

        if (abs($difference) < 0.0005) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'A nova quantidade é igual ao stock actual. '
                    . 'Não existe nenhum ajuste a registar.',
            ]);
        }

        $notes =
            'Ajuste manual de stock: '
            . trim($data['notes']);

        /*
        |--------------------------------------------------------------------------
        | Ajuste positivo
        |--------------------------------------------------------------------------
        */

        if ($difference > 0) {

            $stockService->add(
                $product,
                $store,
                $difference,
                'adjustment_in',
                $notes
            );

        /*
        |--------------------------------------------------------------------------
        | Ajuste negativo
        |--------------------------------------------------------------------------
        */

        } else {

            $stockService->remove(
                $product,
                $store,
                abs($difference),
                'adjustment_out',
                $notes
            );
        }

        return redirect()
            ->route('stocks.index')
            ->with(
                'success',
                'Stock ajustado com sucesso. '
                . 'O movimento ficou registado no histórico.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Lotes de um stock
    |--------------------------------------------------------------------------
    */

    public function batches(Stock $stock)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Seleccione uma loja antes de consultar os lotes.'
            );
        }

        abort_unless(
            (int) $stock->company_id
                === (int) $company->id
            && (int) $stock->store_id
                === (int) $store->id,
            403
        );

        $stock->load([
            'product.parent',
            'product.attributeValues.attribute',
            'product.unit',
        ]);

        $product = $stock->product;

        abort_unless(
            $product
            && $product->inventory_mode
                === Product::INVENTORY_BATCH,
            404
        );

        $batches = StockBatch::query()
            ->with('supplier')
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->paginate(20);

        /*
        |--------------------------------------------------------------------------
        | Data de validade
        |--------------------------------------------------------------------------
        |
        | A view precisa saber se esta empresa utiliza controlo
        | de datas de validade.
        |
        */

        $hasExpiryDates = $company->hasFeature('expiry_dates');

        return view(
            'stocks.batches.index',
            compact(
                'stock',
                'product',
                'batches',
                'hasExpiryDates'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Formulário de ajuste do lote
    |--------------------------------------------------------------------------
    */

    public function editBatch(
        Stock $stock,
        \App\Models\StockBatch $batch
    ) {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Seleccione uma loja antes de ajustar o lote.'
            );
        }

        abort_unless(
            (int) $stock->company_id
                === (int) $company->id
            && (int) $stock->store_id
                === (int) $store->id,
            403
        );

        $stock->load([
            'product.parent',
            'product.attributeValues.attribute',
            'product.unit',
        ]);

        $product = $stock->product;

        abort_unless(
            $product
            && $product->inventory_mode
                === Product::INVENTORY_BATCH,
            404
        );

        abort_unless(
            (int) $batch->company_id
                === (int) $company->id
            && (int) $batch->store_id
                === (int) $store->id
            && (int) $batch->product_id
                === (int) $product->id,
            404
        );

        $batch->load('supplier');

        return view(
            'stocks.batches.edit',
            compact(
                'stock',
                'product',
                'batch'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Executar ajuste do lote
    |--------------------------------------------------------------------------
    */

    public function updateBatch(
        Request $request,
        Stock $stock,
        \App\Models\StockBatch $batch,
        StockService $stockService
    ) {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Seleccione uma loja antes de ajustar o lote.'
            );
        }

        abort_unless(
            (int) $stock->company_id
                === (int) $company->id
            && (int) $stock->store_id
                === (int) $store->id,
            403
        );

        $stock->load('product');

        $product = $stock->product;

        abort_unless(
            $product
            && (int) $product->company_id
                === (int) $company->id
            && $product->track_stock
            && $product->inventory_mode
                === Product::INVENTORY_BATCH,
            422
        );

        abort_unless(
            (int) $batch->company_id
                === (int) $company->id
            && (int) $batch->store_id
                === (int) $store->id
            && (int) $batch->product_id
                === (int) $product->id,
            404
        );

        $data = $request->validate([
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $stockService->adjustBatch(
            $product,
            $store,
            $batch,
            (float) $data['quantity'],
            $data['notes']
        );

        return redirect()
            ->route(
                'stocks.batches.index',
                $stock
            )
            ->with(
                'success',
                'Stock do lote ajustado com sucesso.'
            );
    }

    public function movements()
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(403, 'Seleccione uma loja antes de consultar os movimentos.');
        }

        $movements = StockMovement::query()
            ->with([
                'product.parent',
                'product.attributeValues.attribute',
                'stockBatch',
                'user',
            ])
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->latest('occurred_at')
            ->paginate(30);

        return view('stocks.movements', compact('movements'));
    }
}
