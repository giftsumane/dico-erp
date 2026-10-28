<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\SaleItem;
use App\Models\SaleReturnItem;

class StockService
{
    /*
    |--------------------------------------------------------------------------
    | Entrada simples de stock
    |--------------------------------------------------------------------------
    |
    | Mantém exactamente o comportamento actual.
    | stock_batch_id fica null.
    |--------------------------------------------------------------------------
    */

    public function add(
        Product $product,
        Store $store,
        float $quantity,
        string $type = 'entry',
        ?string $notes = null,
        ?string $reference = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'A quantidade deve ser superior a zero.',
            ]);
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $type,
            $notes,
            $reference,
            $referenceType,
            $referenceId
        ) {
            $stock = $this->lockOrCreateStock(
                $product,
                $store
            );

            $before = (float) $stock->quantity;
            $after = $before + $quantity;

            $stock->update([
                'quantity' => $after,
            ]);

            $this->createMovement(
                product: $product,
                store: $store,
                quantity: $quantity,
                before: $before,
                after: $after,
                type: $type,
                notes: $notes,
                reference: $reference,
                stockBatch: null,
                referenceType: $referenceType,
                referenceId: $referenceId
            );

            return $stock->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Saída simples de stock
    |--------------------------------------------------------------------------
    |
    | Mantém exactamente o comportamento actual.
    | stock_batch_id fica null.
    |--------------------------------------------------------------------------
    */

    public function remove(
        Product $product,
        Store $store,
        float $quantity,
        string $type = 'exit',
        ?string $notes = null,
        ?string $reference = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'A quantidade deve ser superior a zero.',
            ]);
        }

        return DB::transaction(function () use (
            $product,
            $store,
            $quantity,
            $type,
            $notes,
            $reference,
            $referenceType,
            $referenceId
        ) {
            $stock = Stock::query()
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Este produto não possui stock nesta loja.',
                ]);
            }

            $before = (float) $stock->quantity;

            if ($before < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock insuficiente.',
                ]);
            }

            $after = $before - $quantity;

            $stock->update([
                'quantity' => $after,
            ]);

            $this->createMovement(
                product: $product,
                store: $store,
                quantity: $quantity,
                before: $before,
                after: $after,
                type: $type,
                notes: $notes,
                reference: $reference,
                stockBatch: null,
                referenceType: $referenceType,
                referenceId: $referenceId
            );

            return $stock->fresh();
        });
    }


    public function createBatchEntry(
        Product $product,
        Store $store,
        string $code,
        float $quantity,
        ?string $name = null,
        float $unitCost = 0,
        ?int $supplierId = null,
        ?string $receivedAt = null,
        ?string $expiryDate = null,
        ?string $notes = null,
        string $movementType = 'entry',
        ?string $reference = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockBatch {
    
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'A quantidade deve ser superior a zero.',
            ]);
        }
    
        if (
            $product->inventory_mode
            !== Product::INVENTORY_BATCH
        ) {
            throw ValidationException::withMessages([
                'product_id' =>
                    'Este produto não utiliza stock por lotes.',
            ]);
        }
    
        return DB::transaction(function () use (
            $product,
            $store,
            $code,
            $quantity,
            $name,
            $unitCost,
            $supplierId,
            $receivedAt,
            $expiryDate,
            $notes,
            $movementType,
            $reference,
            $referenceType,
            $referenceId
        ) {
    
            /*
            |--------------------------------------------------------------------------
            | Impedir código duplicado
            |--------------------------------------------------------------------------
            */
    
            $exists = StockBatch::query()
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->where('code', $code)
                ->exists();
    
            if ($exists) {
                throw ValidationException::withMessages([
                    'batch_code' =>
                        'Já existe um lote com este código '
                        . 'para este produto nesta loja.',
                ]);
            }
    
    
            /*
            |--------------------------------------------------------------------------
            | Criar lote
            |--------------------------------------------------------------------------
            */
    
            $batch = StockBatch::create([
                'company_id' => $product->company_id,
                'store_id' => $store->id,
                'product_id' => $product->id,
    
                'supplier_id' => $supplierId,
    
                'code' => trim($code),
                'name' => $name
                    ? trim($name)
                    : null,
    
                'initial_quantity' => $quantity,
    
                /*
                 * Começa em zero porque addToBatch()
                 * fará a entrada física.
                 */
                'quantity' => 0,
    
                'unit_cost' => $unitCost,
    
                'received_at' =>
                    $receivedAt ?: now()->toDateString(),
    
                'expiry_date' => $expiryDate,
    
                'status' => StockBatch::STATUS_ACTIVE,
    
                'notes' => $notes,
            ]);
    
    
            /*
            |--------------------------------------------------------------------------
            | Actualizar stock agregado + lote + movimento
            |--------------------------------------------------------------------------
            */
    
            $this->addToBatch(
                $product,
                $store,
                $batch,
                $quantity,
                $movementType,
                $notes ?: 'Entrada de stock por lote',
                $reference ?: $code,
                $referenceType,
                $referenceId
            );
    
    
            return $batch->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Entrada de stock por lote
    |--------------------------------------------------------------------------
    |
    | Actualiza:
    | 1. stock total do produto na loja;
    | 2. quantidade do lote;
    | 3. movimento associado ao lote.
    |--------------------------------------------------------------------------
    */

    public function addToBatch(
        Product $product,
        Store $store,
        StockBatch $batch,
        float $quantity,
        string $type = 'entry',
        ?string $notes = null,
        ?string $reference = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'A quantidade deve ser superior a zero.',
            ]);
        }

        $this->validateBatchContext(
            $product,
            $store,
            $batch
        );

        return DB::transaction(function () use (
            $product,
            $store,
            $batch,
            $quantity,
            $type,
            $notes,
            $reference,
            $referenceType,
            $referenceId
        ) {
            $lockedBatch = StockBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateBatchContext(
                $product,
                $store,
                $lockedBatch
            );

            if ($lockedBatch->status === StockBatch::STATUS_CLOSED) {
                throw ValidationException::withMessages([
                    'batch' => 'Este lote está encerrado.',
                ]);
            }

            $stock = $this->lockOrCreateStock(
                $product,
                $store
            );

            $stockBefore = (float) $stock->quantity;
            $stockAfter = $stockBefore + $quantity;

            $batchBefore = (float) $lockedBatch->quantity;
            $batchAfter = $batchBefore + $quantity;

            $stock->update([
                'quantity' => $stockAfter,
            ]);

            $lockedBatch->update([
                'quantity' => $batchAfter,
                'status' => StockBatch::STATUS_ACTIVE,
            ]);

            $this->createMovement(
                product: $product,
                store: $store,
                quantity: $quantity,
                before: $stockBefore,
                after: $stockAfter,
                type: $type,
                notes: $notes,
                reference: $reference,
                stockBatch: $lockedBatch,
                referenceType: $referenceType,
                referenceId: $referenceId
            );

            return $stock->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Saída de stock de um lote específico
    |--------------------------------------------------------------------------
    */

    public function removeFromBatch(
        Product $product,
        Store $store,
        StockBatch $batch,
        float $quantity,
        string $type = 'exit',
        ?string $notes = null,
        ?string $reference = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Stock {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'A quantidade deve ser superior a zero.',
            ]);
        }

        $this->validateBatchContext(
            $product,
            $store,
            $batch
        );

        return DB::transaction(function () use (
            $product,
            $store,
            $batch,
            $quantity,
            $type,
            $notes,
            $reference,
            $referenceType,
            $referenceId
        ) {
            $lockedBatch = StockBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateBatchContext(
                $product,
                $store,
                $lockedBatch
            );

            $batchBefore = (float) $lockedBatch->quantity;

            if ($batchBefore < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Quantidade insuficiente neste lote.',
                ]);
            }

            $stock = Stock::query()
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Este produto não possui stock nesta loja.',
                ]);
            }

            $stockBefore = (float) $stock->quantity;

            if ($stockBefore < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stock total insuficiente.',
                ]);
            }

            $stockAfter = $stockBefore - $quantity;
            $batchAfter = $batchBefore - $quantity;

            $stock->update([
                'quantity' => $stockAfter,
            ]);

            $lockedBatch->update([
                'quantity' => $batchAfter,
                'status' =>
                    $batchAfter <= 0
                        ? StockBatch::STATUS_EXHAUSTED
                        : StockBatch::STATUS_ACTIVE,
            ]);

            $this->createMovement(
                product: $product,
                store: $store,
                quantity: $quantity,
                before: $stockBefore,
                after: $stockAfter,
                type: $type,
                notes: $notes,
                reference: $reference,
                stockBatch: $lockedBatch,
                referenceType: $referenceType,
                referenceId: $referenceId
            );

            return $stock->fresh();
        });
    }


    public function removeForSale(
        Product $product,
        Store $store,
        float $quantity,
        SaleItem $saleItem,
        string $saleNumber,
        bool $useExpiryDates = false
    ): void {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'A quantidade deve ser superior a zero.',
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Produto sem controlo por lotes
        |--------------------------------------------------------------------------
        */
    
        if (
            $product->inventory_mode
            !== Product::INVENTORY_BATCH
        ) {
            $this->remove(
                $product,
                $store,
                $quantity,
                'sale',
                'Venda ' . $saleNumber,
                $saleNumber,
                'sale',
                $saleItem->sale_id
            );
    
            return;
        }
    
        /*
        |--------------------------------------------------------------------------
        | Stock por lotes
        |--------------------------------------------------------------------------
        */
    
        $remaining = $quantity;
    
        $query = StockBatch::query()
            ->where(
                'company_id',
                $product->company_id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'product_id',
                $product->id
            )
            ->where(
                'status',
                StockBatch::STATUS_ACTIVE
            )
            ->where(
                'quantity',
                '>',
                0
            );
    
        /*
        |--------------------------------------------------------------------------
        | FEFO
        |--------------------------------------------------------------------------
        |
        | Lotes com validade:
        | primeiro sai o que expira primeiro.
        |
        | Lotes sem validade ficam depois.
        |
        */
    
        if ($useExpiryDates) {
            $query
                ->orderByRaw(
                    'CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END'
                )
                ->orderBy('expiry_date')
                ->orderBy('received_at')
                ->orderBy('id');
        }
    
        /*
        |--------------------------------------------------------------------------
        | FIFO
        |--------------------------------------------------------------------------
        */
    
        else {
            $query
                ->orderBy('received_at')
                ->orderBy('id');
        }
    
        /*
        |--------------------------------------------------------------------------
        | Bloquear lotes durante a venda
        |--------------------------------------------------------------------------
        */
    
        $batches = $query
            ->lockForUpdate()
            ->get();
    
        /*
        |--------------------------------------------------------------------------
        | Confirmar disponibilidade real nos lotes
        |--------------------------------------------------------------------------
        */
    
        $availableInBatches = (float) $batches->sum(
            fn ($batch) => (float) $batch->quantity
        );
    
        if ($availableInBatches < $quantity) {
            throw ValidationException::withMessages([
                'items' =>
                    'Stock por lotes insuficiente para '
                    . $product->name
                    . '. Disponível nos lotes: '
                    . $availableInBatches,
            ]);
        }
    
        /*
        |--------------------------------------------------------------------------
        | Consumir lotes
        |--------------------------------------------------------------------------
        */
    
        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }
    
            $batchAvailable =
                (float) $batch->quantity;
    
            $quantityFromBatch = min(
                $remaining,
                $batchAvailable
            );
    
            /*
            |--------------------------------------------------------------------------
            | Baixar lote + stock agregado + criar StockMovement
            |--------------------------------------------------------------------------
            */
    
            $this->removeFromBatch(
                $product,
                $store,
                $batch,
                $quantityFromBatch,
                'sale',
                'Venda ' . $saleNumber,
                $saleNumber,
                'sale',
                $saleItem->sale_id
            );
    
            /*
            |--------------------------------------------------------------------------
            | Rastreabilidade SaleItem ↔ StockBatch
            |--------------------------------------------------------------------------
            */
    
            $saleItem
                ->stockBatches()
                ->attach(
                    $batch->id,
                    [
                        'quantity' =>
                            $quantityFromBatch,
                    ]
                );
    
            $remaining = round(
                $remaining - $quantityFromBatch,
                3
            );
        }
    
        /*
        |--------------------------------------------------------------------------
        | Segurança final
        |--------------------------------------------------------------------------
        */
    
        if ($remaining > 0.0005) {
            throw ValidationException::withMessages([
                'items' =>
                    'Não foi possível completar a baixa de stock por lotes para '
                    . $product->name
                    . '.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Repor stock através de uma devolução
    |--------------------------------------------------------------------------
    |
    | Stock simples:
    | - aumenta stock agregado.
    |
    | Stock por lotes:
    | - consulta os lotes originalmente consumidos pela SaleItem;
    | - considera devoluções anteriores;
    | - repõe exactamente nos lotes de origem;
    | - aumenta simultaneamente o stock agregado;
    | - cria StockMovement;
    | - grava SaleReturnItem ↔ StockBatch.
    |
    */

    public function restoreForSaleReturn(
        Product $product,
        Store $store,
        float $quantity,
        SaleItem $saleItem,
        SaleReturnItem $returnItem,
        string $returnNumber
    ): void {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'A quantidade a devolver deve ser superior a zero.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Segurança
        |--------------------------------------------------------------------------
        */

        if (
            (int) $saleItem->product_id
            !== (int) $product->id
        ) {
            throw ValidationException::withMessages([
                'items' =>
                    'O produto da devolução não corresponde ao produto vendido.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Quantidade já devolvida
        |--------------------------------------------------------------------------
        */

        $alreadyReturned = (float) $saleItem
            ->returnItems()
            ->whereHas(
                'saleReturn',
                function ($query) {
                    $query->where(
                        'status',
                        'completed'
                    );
                }
            )
            ->where(
                'id',
                '!=',
                $returnItem->id
            )
            ->sum('quantity');

        $soldQuantity =
            (float) $saleItem->quantity;

        $availableToReturn = round(
            $soldQuantity - $alreadyReturned,
            3
        );

        if (
            $quantity
            > $availableToReturn + 0.0005
        ) {
            throw ValidationException::withMessages([
                'items' =>
                    'A quantidade devolvida de '
                    . $product->name
                    . ' é superior à quantidade disponível para devolução. '
                    . 'Disponível: '
                    . $availableToReturn,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Produto sem controlo por lotes
        |--------------------------------------------------------------------------
        */

        if (
            $product->inventory_mode
            !== Product::INVENTORY_BATCH
        ) {
            $this->add(
                $product,
                $store,
                $quantity,
                'return',
                'Devolução ' . $returnNumber,
                $returnNumber,
                'sale_return',
                $returnItem->sale_return_id
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Lotes originalmente utilizados na venda
        |--------------------------------------------------------------------------
        */

        $saleBatches = $saleItem
            ->stockBatches()
            ->orderBy(
                'sale_item_stock_batches.id'
            )
            ->get();

        if ($saleBatches->isEmpty()) {
            throw ValidationException::withMessages([
                'items' =>
                    'Não foi encontrada a origem por lote da venda do produto '
                    . $product->name
                    . '.',
            ]);
        }

        $remaining =
            round($quantity, 3);

        /*
        |--------------------------------------------------------------------------
        | Repor exactamente nos lotes de origem
        |--------------------------------------------------------------------------
        */

        foreach ($saleBatches as $batch) {
            if ($remaining <= 0.0005) {
                break;
            }

            $soldFromBatch =
                (float) $batch->pivot->quantity;

            /*
            |--------------------------------------------------------------------------
            | Quanto deste lote já foi devolvido anteriormente
            |--------------------------------------------------------------------------
            */

            $returnedToBatch = (float) DB::table(
                'sale_return_item_stock_batches'
            )
                ->join(
                    'sale_return_items',
                    'sale_return_items.id',
                    '=',
                    'sale_return_item_stock_batches.sale_return_item_id'
                )
                ->join(
                    'sale_returns',
                    'sale_returns.id',
                    '=',
                    'sale_return_items.sale_return_id'
                )
                ->where(
                    'sale_return_items.sale_item_id',
                    $saleItem->id
                )
                ->where(
                    'sale_return_item_stock_batches.stock_batch_id',
                    $batch->id
                )
                ->where(
                    'sale_returns.status',
                    'completed'
                )
                ->where(
                    'sale_return_items.id',
                    '!=',
                    $returnItem->id
                )
                ->sum(
                    'sale_return_item_stock_batches.quantity'
                );

            $availableInBatch = round(
                $soldFromBatch - $returnedToBatch,
                3
            );

            if ($availableInBatch <= 0) {
                continue;
            }

            $quantityForBatch = min(
                $remaining,
                $availableInBatch
            );

            /*
            |--------------------------------------------------------------------------
            | Lote + stock agregado + StockMovement
            |--------------------------------------------------------------------------
            */

            $this->addToBatch(
                $product,
                $store,
                $batch,
                $quantityForBatch,
                'return',
                'Devolução ' . $returnNumber,
                $returnNumber,
                'sale_return',
                $returnItem->sale_return_id
            );

            /*
            |--------------------------------------------------------------------------
            | Rastreabilidade da devolução
            |--------------------------------------------------------------------------
            */

            $returnItem
                ->stockBatches()
                ->attach(
                    $batch->id,
                    [
                        'quantity' =>
                            $quantityForBatch,
                    ]
                );

            $remaining = round(
                $remaining - $quantityForBatch,
                3
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Segurança final
        |--------------------------------------------------------------------------
        */

        if ($remaining > 0.0005) {
            throw ValidationException::withMessages([
                'items' =>
                    'Não foi possível repor toda a quantidade devolvida de '
                    . $product->name
                    . ' nos lotes originais.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Ajustar stock de um lote
    |--------------------------------------------------------------------------
    |
    | Ajusta a quantidade real de um lote sem alterar initial_quantity.
    |
    | Mantém sincronizados:
    | - stock_batches.quantity
    | - stocks.quantity
    | - stock_movements
    |
    */

    public function adjustBatch(
        Product $product,
        Store $store,
        StockBatch $batch,
        float $newQuantity,
        string $notes
    ): Stock {
        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' =>
                    'A quantidade do lote não pode ser negativa.',
            ]);
        }

        if (trim($notes) === '') {
            throw ValidationException::withMessages([
                'notes' =>
                    'Indique o motivo do ajuste.',
            ]);
        }

        $this->validateBatchContext(
            $product,
            $store,
            $batch
        );

        return DB::transaction(function () use (
            $product,
            $store,
            $batch,
            $newQuantity,
            $notes
        ) {
            $lockedBatch = StockBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateBatchContext(
                $product,
                $store,
                $lockedBatch
            );

            if (
                $lockedBatch->status
                === StockBatch::STATUS_CLOSED
            ) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'Este lote está encerrado e não pode ser ajustado.',
                ]);
            }

            $batchBefore =
                (float) $lockedBatch->quantity;

            $difference = round(
                $newQuantity - $batchBefore,
                3
            );

            if (abs($difference) < 0.0005) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'A nova quantidade é igual ao saldo actual do lote.',
                ]);
            }

            $stock = Stock::query()
                ->where('store_id', $store->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'O stock agregado deste produto não foi encontrado.',
                ]);
            }

            $stockBefore =
                (float) $stock->quantity;

            $stockAfter = round(
                $stockBefore + $difference,
                3
            );

            if ($stockAfter < 0) {
                throw ValidationException::withMessages([
                    'quantity' =>
                        'O ajuste deixaria o stock total negativo.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Actualizar stock agregado
            |--------------------------------------------------------------------------
            */

            $stock->update([
                'quantity' => $stockAfter,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Actualizar lote
            |--------------------------------------------------------------------------
            */

            $lockedBatch->update([
                'quantity' => $newQuantity,

                'status' =>
                    $newQuantity <= 0
                        ? StockBatch::STATUS_EXHAUSTED
                        : StockBatch::STATUS_ACTIVE,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Movimento
            |--------------------------------------------------------------------------
            */

            $type =
                $difference > 0
                    ? 'adjustment_in'
                    : 'adjustment_out';

            $this->createMovement(
                product: $product,
                store: $store,
                quantity: abs($difference),
                before: $stockBefore,
                after: $stockAfter,
                type: $type,
                notes:
                    'Ajuste manual do lote '
                    . $lockedBatch->code
                    . ': '
                    . trim($notes),
                reference: $lockedBatch->code,
                stockBatch: $lockedBatch
            );

            return $stock->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Criar/bloquear stock agregado
    |--------------------------------------------------------------------------
    */

    private function lockOrCreateStock(
        Product $product,
        Store $store
    ): Stock {
        $stock = Stock::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            return $stock;
        }

        /*
        |--------------------------------------------------------------------------
        | Criação segura
        |--------------------------------------------------------------------------
        |
        | A aplicação já trabalha com um registo agregado por produto/loja.
        | Criamos o registo apenas quando ainda não existe.
        |--------------------------------------------------------------------------
        */

        Stock::create([
            'company_id' => $product->company_id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'quantity' => 0,
        ]);

        return Stock::query()
            ->where('store_id', $store->id)
            ->where('product_id', $product->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Validar coerência do lote
    |--------------------------------------------------------------------------
    */

    private function validateBatchContext(
        Product $product,
        Store $store,
        StockBatch $batch
    ): void {
        if (
            (int) $batch->company_id
                !== (int) $product->company_id
            || (int) $batch->store_id
                !== (int) $store->id
            || (int) $batch->product_id
                !== (int) $product->id
        ) {
            throw ValidationException::withMessages([
                'batch' =>
                    'O lote seleccionado não pertence a este produto e loja.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Registar movimento
    |--------------------------------------------------------------------------
    */

    private function createMovement(
        Product $product,
        Store $store,
        float $quantity,
        float $before,
        float $after,
        string $type,
        ?string $notes,
        ?string $reference,
        ?StockBatch $stockBatch,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): void {
        StockMovement::create([
            'company_id' => $product->company_id,
            'store_id' => $store->id,
            'product_id' => $product->id,
            'stock_batch_id' => $stockBatch?->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity' => $quantity,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference' => $reference,
            'notes' => $notes,
            'occurred_at' => now(),
        ]);
    }
}
