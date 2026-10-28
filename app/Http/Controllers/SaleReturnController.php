<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleReturnController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Criar devolução
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        Sale $sale,
        StockService $stockService
    ) {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless(
            $store
            && (int) $sale->company_id === (int) $company->id
            && (int) $sale->store_id === (int) $store->id,
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Validação inicial
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.sale_item_id' => [
                'required',
                'integer',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0',
            ],

            'refund_method' => [
                'required',
                'in:none,cash,other',
            ],

            'refund_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'refund_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $refundMethod =
            $data['refund_method'];

        $refundAmount =
            round(
                (float) ($data['refund_amount'] ?? 0),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Coerência do método de reembolso
        |--------------------------------------------------------------------------
        */

        if (
            $refundMethod === SaleReturn::REFUND_NONE
            && $refundAmount > 0
        ) {
            throw ValidationException::withMessages([
                'refund_amount' =>
                    'Não pode existir valor reembolsado quando o método é sem reembolso.',
            ]);
        }

        if (
            $refundMethod !== SaleReturn::REFUND_NONE
            && $refundAmount <= 0
        ) {
            throw ValidationException::withMessages([
                'refund_amount' =>
                    'Indique o valor que será reembolsado.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Devolução
        |--------------------------------------------------------------------------
        */

        $saleReturn = DB::transaction(
            function () use (
                $sale,
                $company,
                $store,
                $data,
                $refundMethod,
                $refundAmount,
                $stockService
            ) {
                /*
                |--------------------------------------------------------------------------
                | Bloquear venda
                |--------------------------------------------------------------------------
                */

                $lockedSale = Sale::query()
                    ->whereKey($sale->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $lockedSale->company_id
                        !== (int) $company->id
                    || (int) $lockedSale->store_id
                        !== (int) $store->id
                ) {
                    abort(403);
                }

                if (
                    $lockedSale->status
                    !== 'completed'
                ) {
                    throw ValidationException::withMessages([
                        'sale' =>
                            'Apenas vendas concluídas podem receber devoluções.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Carregar linhas da venda
                |--------------------------------------------------------------------------
                */

                $saleItems = SaleItem::query()
                    ->with([
                        'product',
                        'stockBatches',
                    ])
                    ->where(
                        'sale_id',
                        $lockedSale->id
                    )
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                /*
                |--------------------------------------------------------------------------
                | Preparar linhas solicitadas
                |--------------------------------------------------------------------------
                */

                $preparedItems = [];

                $returnTotal = 0;

                foreach (
                    $data['items']
                    as $requestedItem
                ) {
                    $saleItemId =
                        (int) $requestedItem[
                            'sale_item_id'
                        ];

                    $quantity =
                        round(
                            (float) $requestedItem[
                                'quantity'
                            ],
                            3
                        );

                    if ($quantity <= 0) {
                        continue;
                    }

                    $saleItem =
                        $saleItems->get(
                            $saleItemId
                        );

                    if (! $saleItem) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Foi seleccionado um produto que não pertence a esta venda.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Evitar a mesma SaleItem duas vezes no request
                    |--------------------------------------------------------------------------
                    */

                    if (
                        collect($preparedItems)
                            ->contains(
                                fn ($item) =>
                                    (int) $item['sale_item']->id
                                    === $saleItemId
                            )
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'O mesmo produto foi enviado mais de uma vez na devolução.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Quantidade já devolvida
                    |--------------------------------------------------------------------------
                    */

                    $alreadyReturned =
                        (float) $saleItem
                            ->returnItems()
                            ->whereHas(
                                'saleReturn',
                                function ($query) {
                                    $query->where(
                                        'status',
                                        SaleReturn::STATUS_COMPLETED
                                    );
                                }
                            )
                            ->sum('quantity');

                    $soldQuantity =
                        (float) $saleItem->quantity;

                    $availableToReturn =
                        round(
                            $soldQuantity
                            - $alreadyReturned,
                            3
                        );

                    if (
                        $quantity
                        > $availableToReturn + 0.0005
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'A quantidade devolvida de '
                                . $saleItem->product->name
                                . ' é superior à disponível. '
                                . 'Disponível para devolução: '
                                . $availableToReturn,
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Valor proporcional da linha
                    |--------------------------------------------------------------------------
                    |
                    | Usamos o total real da SaleItem.
                    | Assim respeitamos descontos e IVA já calculados na venda.
                    |
                    */

                    $lineTotal =
                        (float) $saleItem->total;

                    $returnLineTotal =
                        $soldQuantity > 0
                            ? round(
                                (
                                    $lineTotal
                                    / $soldQuantity
                                ) * $quantity,
                                2
                            )
                            : 0;

                    $returnTotal +=
                        $returnLineTotal;

                    $preparedItems[] = [
                        'sale_item' =>
                            $saleItem,

                        'product' =>
                            $saleItem->product,

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            (float) $saleItem->unit_price,

                        'total' =>
                            $returnLineTotal,
                    ];
                }

                if (empty($preparedItems)) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Seleccione pelo menos um produto para devolver.',
                    ]);
                }

                $returnTotal =
                    round(
                        $returnTotal,
                        2
                    );

                /*
                |--------------------------------------------------------------------------
                | Não permitir refund superior ao valor devolvido
                |--------------------------------------------------------------------------
                */

                if (
                    $refundAmount
                    > $returnTotal + 0.01
                ) {
                    throw ValidationException::withMessages([
                        'refund_amount' =>
                            'O valor reembolsado não pode ser superior ao valor dos produtos devolvidos.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Não devolver mais dinheiro do que efectivamente foi recebido
                |--------------------------------------------------------------------------
                */

                $previousRefunds =
                    (float) SaleReturn::query()
                        ->where(
                            'sale_id',
                            $lockedSale->id
                        )
                        ->where(
                            'status',
                            SaleReturn::STATUS_COMPLETED
                        )
                        ->sum(
                            'refund_amount'
                        );

                $maxFinancialRefund =
                    max(
                        0,
                        round(
                            (float) $lockedSale->paid_total
                            - $previousRefunds,
                            2
                        )
                    );

                if (
                    $refundAmount
                    > $maxFinancialRefund + 0.01
                ) {
                    throw ValidationException::withMessages([
                        'refund_amount' =>
                            'O reembolso ultrapassa o valor efectivamente pago e ainda disponível para devolução. '
                            . 'Máximo disponível: '
                            . number_format(
                                $maxFinancialRefund,
                                2,
                                ',',
                                '.'
                            )
                            . ' MT.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Determinar se esta devolução completa toda a venda
                |--------------------------------------------------------------------------
                */

                $totalSoldQuantity =
                    (float) $saleItems
                        ->sum(
                            fn ($item) =>
                                (float) $item->quantity
                        );

                $alreadyReturnedTotal =
                    (float) SaleReturnItem::query()
                        ->whereHas(
                            'saleReturn',
                            function ($query) use (
                                $lockedSale
                            ) {
                                $query
                                    ->where(
                                        'sale_id',
                                        $lockedSale->id
                                    )
                                    ->where(
                                        'status',
                                        SaleReturn::STATUS_COMPLETED
                                    );
                            }
                        )
                        ->sum('quantity');

                $currentReturnQuantity =
                    (float) collect(
                        $preparedItems
                    )->sum('quantity');

                $returnType =
                    abs(
                        $totalSoldQuantity
                        - (
                            $alreadyReturnedTotal
                            + $currentReturnQuantity
                        )
                    ) < 0.0005
                        ? SaleReturn::TYPE_FULL
                        : SaleReturn::TYPE_PARTIAL;

                /*
                |--------------------------------------------------------------------------
                | Criar cabeçalho
                |--------------------------------------------------------------------------
                */

                $saleReturn =
                    SaleReturn::create([
                        'company_id' =>
                            $company->id,

                        'store_id' =>
                            $store->id,

                        'sale_id' =>
                            $lockedSale->id,

                        'user_id' =>
                            auth()->id(),

                        'cash_register_id' =>
                            null,

                        'number' =>
                            null,

                        'status' =>
                            SaleReturn::STATUS_COMPLETED,

                        'return_type' =>
                            $returnType,

                        'total' =>
                            $returnTotal,

                        'refund_method' =>
                            $refundMethod,

                        'refund_amount' =>
                            $refundAmount,

                        'refund_reference' =>
                            $data[
                                'refund_reference'
                            ] ?? null,

                        'reason' =>
                            $data['reason']
                                ?? null,

                        'notes' =>
                            $data['notes']
                                ?? null,

                        'returned_at' =>
                            now(),
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Número definitivo
                |--------------------------------------------------------------------------
                */

                $returnNumber =
                    'DEV-'
                    . str_pad(
                        (string) $saleReturn->id,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );

                $saleReturn->update([
                    'number' =>
                        $returnNumber,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Criar linhas + devolver stock
                |--------------------------------------------------------------------------
                */

                foreach (
                    $preparedItems
                    as $prepared
                ) {
                    $returnItem =
                        SaleReturnItem::create([
                            'sale_return_id' =>
                                $saleReturn->id,

                            'sale_item_id' =>
                                $prepared[
                                    'sale_item'
                                ]->id,

                            'product_id' =>
                                $prepared[
                                    'product'
                                ]->id,

                            'quantity' =>
                                $prepared[
                                    'quantity'
                                ],

                            'unit_price' =>
                                $prepared[
                                    'unit_price'
                                ],

                            'total' =>
                                $prepared[
                                    'total'
                                ],
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Só mexer no stock se o produto controla stock
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $prepared[
                            'product'
                        ]->track_stock
                    ) {
                        $stockService
                            ->restoreForSaleReturn(
                                product:
                                    $prepared[
                                        'product'
                                    ],

                                store:
                                    $store,

                                quantity:
                                    $prepared[
                                        'quantity'
                                    ],

                                saleItem:
                                    $prepared[
                                        'sale_item'
                                    ],

                                returnItem:
                                    $returnItem,

                                returnNumber:
                                    $returnNumber
                            );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Reembolso em dinheiro
                |--------------------------------------------------------------------------
                */

                if (
                    $refundMethod
                        === SaleReturn::REFUND_CASH
                    && $refundAmount > 0
                ) {
                    $cashRegister =
                        CashRegister::query()
                            ->where(
                                'company_id',
                                $company->id
                            )
                            ->where(
                                'store_id',
                                $store->id
                            )
                            ->where(
                                'user_id',
                                auth()->id()
                            )
                            ->where(
                                'status',
                                'open'
                            )
                            ->lockForUpdate()
                            ->latest('id')
                            ->first();

                    if (! $cashRegister) {
                        throw ValidationException::withMessages([
                            'refund_method' =>
                                'Para efectuar um reembolso em dinheiro é necessário ter um caixa aberto.',
                        ]);
                    }

                    CashMovement::create([
                        'cash_register_id' =>
                            $cashRegister->id,

                        'user_id' =>
                            auth()->id(),

                        'type' =>
                            'out',

                        'amount' =>
                            $refundAmount,

                        'reason' =>
                            'Devolução '
                            . $returnNumber,

                        'notes' =>
                            'Venda '
                            . $lockedSale->number,
                    ]);

                    $saleReturn->update([
                        'cash_register_id' =>
                            $cashRegister->id,
                    ]);
                }

                return $saleReturn;
            }
        );

        return redirect()
            ->route(
                'sales.show',
                $sale->id
            )
            ->with(
                'success',
                'Devolução '
                . $saleReturn->number
                . ' registada com sucesso.'
            );
    }
}