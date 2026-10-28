@extends('layouts.app')

@section('title', $sale->number . ' | DICO')
@section('topbar-title', 'Venda')

@section('content')

@php
    $paymentLabels = [
        'cash' => 'Dinheiro',
        'mpesa' => 'M-Pesa',
        'emola' => 'e-Mola',
        'card' => 'Cartão',
        'bank_transfer' => 'Transferência',
        'other' => 'Outro',
    ];

    $isOverdue =
        $sale->due_date
        && $sale->amount_due > 0
        && $sale->due_date->isPast()
        && ! $sale->due_date->isToday();
@endphp


{{-- =========================================================================
     CABEÇALHO
============================================================================ --}}

<div class="page-heading">

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            flex-wrap:wrap;
        "
    >

        <div>

            <h1>
                {{ $sale->number }}
            </h1>

            <p>

                {{ $sale->completed_at
                    ?->format('d/m/Y H:i')
                    ?? $sale->created_at
                        ->format('d/m/Y H:i')
                }}

            </p>

        </div>


        <div
            style="
                display:flex;
                gap:10px;
                flex-wrap:wrap;
            "
        >

            <a
                href="{{ route('sales.index') }}"
                class="topbar-button"
            >
                Voltar
            </a>


            <a
                href="{{ route(
                    'pos.receipt',
                    $sale
                ) }}"
                class="topbar-button"
            >
                Ver recibo
            </a>


            @if (
                (float) $sale->amount_due > 0
            )

                <a
                    href="{{ route(
                        'receivables.show',
                        $sale
                    ) }}"
                    class="topbar-button receive-button"
                >
                    Receber pagamento
                </a>

            @endif

            @if (
                $sale->status === 'completed'
                && $sale->items->contains(
                    function ($item) {
                        $returned = (float) $item
                            ->returnItems
                            ->sum('quantity');

                        return
                            (float) $item->quantity
                            - $returned
                            > 0.0005;
                    }
                )
            )
                <button
                    type="button"
                    class="btn btn-primary"
                    onclick="openReturnModal()"
                >
                    Registar devolução
                </button>
            @endif

        </div>

    </div>

</div>


{{-- =========================================================================
     MÉTRICAS
============================================================================ --}}

<div class="sale-metric-grid">

    {{-- Total --}}

    <div class="metric-card">

        <div class="metric-label">
            Total
        </div>

        <div class="metric-value">

            {{ number_format(
                $sale->total,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">
            MT
        </div>

    </div>


    {{-- Pago --}}

    <div class="metric-card">

        <div class="metric-label">
            Valor pago
        </div>

        <div class="metric-value">

            {{ number_format(
                $sale->paid_total,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">
            MT recebidos
        </div>

    </div>


    {{-- Saldo --}}

    <div class="metric-card">

        <div class="metric-label">
            Saldo por receber
        </div>

        <div
            class="metric-value"
            @if ((float) $sale->amount_due > 0)
                style="color:#8a5c00;"
            @endif
        >

            {{ number_format(
                $sale->amount_due,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">

            @if (
                (float) $sale->amount_due <= 0
            )

                Venda liquidada

            @elseif ($sale->due_date)

                Vencimento:
                {{ $sale->due_date
                    ->format('d/m/Y')
                }}

            @else

                MT por liquidar

            @endif

        </div>

    </div>


    {{-- Estado pagamento --}}

    <div class="metric-card">

        <div class="metric-label">
            Estado do pagamento
        </div>

        <div
            class="metric-value"
            style="
                margin-top:20px;
                font-size:18px;
            "
        >

            @if (
                $sale->payment_status
                === 'paid'
            )

                <span class="status-badge">
                    Pago
                </span>

            @elseif (
                $sale->payment_status
                === 'partial'
            )

                <span
                    class="
                        status-badge
                        payment-status-partial
                    "
                >
                    Pagamento parcial
                </span>

            @elseif (
                $sale->payment_status
                === 'unpaid'
            )

                <span
                    class="
                        status-badge
                        payment-status-unpaid
                    "
                >
                    A crédito
                </span>

            @endif

        </div>


        <div class="metric-description">

            @if ($isOverdue)

                <span style="color:#b42318;">
                    Pagamento vencido
                </span>

            @elseif (
                $sale->due_date
                && $sale->amount_due > 0
            )

                Prazo:
                {{ $sale->due_date
                    ->format('d/m/Y')
                }}

            @else

                Estado financeiro da venda

            @endif

        </div>

    </div>

</div>


{{-- =========================================================================
     INFORMAÇÕES
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Informações da venda
            </h2>

        </div>

    </div>


    <div class="panel-body">

        <div class="sale-info-grid">

            <div>

                <span class="sale-info-label">
                    Cliente
                </span>

                <strong>
                    {{ $sale->customer?->name
                        ?? 'Consumidor final'
                    }}
                </strong>

                @if ($sale->customer?->nuit)

                    <div class="table-secondary">
                        NUIT:
                        {{ $sale->customer->nuit }}
                    </div>

                @endif

            </div>


            <div>

                <span class="sale-info-label">
                    Operador
                </span>

                <strong>
                    {{ $sale->user?->name ?? '—' }}
                </strong>

            </div>


            <div>

                <span class="sale-info-label">
                    Loja
                </span>

                <strong>
                    {{ $sale->store?->name ?? '—' }}
                </strong>

            </div>


            <div>

                <span class="sale-info-label">
                    Caixa original
                </span>

                <strong>

                    {{ $sale->cashRegister
                        ? '#' . $sale->cashRegister->id
                        : '—'
                    }}

                </strong>

            </div>


            <div>

                <span class="sale-info-label">
                    Estado da venda
                </span>

                <strong>

                    @if (
                        $sale->status
                        === 'completed'
                    )
                        Concluída
                    @elseif (
                        $sale->status
                        === 'draft'
                    )
                        Rascunho
                    @else
                        Cancelada
                    @endif

                </strong>

            </div>


            <div>

                <span class="sale-info-label">
                    Vencimento
                </span>

                <strong>

                    {{ $sale->due_date
                        ?->format('d/m/Y')
                        ?? '—'
                    }}

                </strong>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================================
     PRODUTOS
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Produtos
            </h2>

        </div>

    </div>


    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Lote / Origem</th>
                        <th>Preço</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>

                </thead>


                <tbody>

                    @foreach (
                        $sale->items
                        as $item
                    )

                        <tr>

                            <td>

                                <strong>
                                    {{ $item->product?->name
                                        ?? 'Produto removido'
                                    }}
                                </strong>

                            </td>


                            <td>

                                {{ rtrim(
                                    rtrim(
                                        number_format(
                                            (float) $item->quantity,
                                            3,
                                            ',',
                                            '.'
                                        ),
                                        '0'
                                    ),
                                    ','
                                ) }}

                                @if (
                                    $item->product
                                        ?->unit
                                        ?->symbol
                                )

                                    {{ $item->product
                                        ->unit
                                        ->symbol
                                    }}

                                @endif


                                @php
                                    $returnedQuantity =
                                        (float) $item
                                            ->returnItems
                                            ->sum('quantity');

                                    $availableReturnQuantity =
                                        max(
                                            0,
                                            (float) $item->quantity
                                            - $returnedQuantity
                                        );
                                @endphp


                                @if ($returnedQuantity > 0)

                                    <div
                                        class="table-secondary"
                                        style="
                                            margin-top:5px;
                                            color:#b42318;
                                        "
                                    >
                                        Devolvido:

                                        {{ rtrim(
                                            rtrim(
                                                number_format(
                                                    $returnedQuantity,
                                                    3,
                                                    ',',
                                                    '.'
                                                ),
                                                '0'
                                            ),
                                            ','
                                        ) }}

                                        @if (
                                            $item->product
                                                ?->unit
                                                ?->symbol
                                        )

                                            {{ $item->product
                                                ->unit
                                                ->symbol
                                            }}

                                        @endif

                                    </div>

                                @endif

                            </td>
                            <td>

                                @if ($item->stockBatches->isNotEmpty())

                                    <div class="sale-batch-list">

                                        @foreach (
                                            $item->stockBatches
                                            as $batch
                                        )

                                            <div class="sale-batch-item">

                                                <div>
                                                    <span class="status-badge">
                                                        Lote
                                                    </span>

                                                    <strong>
                                                        {{ $batch->code }}
                                                    </strong>

                                                    @if ($batch->name)
                                                        <div class="table-secondary">
                                                            {{ $batch->name }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="sale-batch-quantity">

                                                    {{ rtrim(
                                                        rtrim(
                                                            number_format(
                                                                (float) $batch->pivot->quantity,
                                                                3,
                                                                ',',
                                                                '.'
                                                            ),
                                                            '0'
                                                        ),
                                                        ','
                                                    ) }}

                                                    @if (
                                                        $item->product
                                                            ?->unit
                                                            ?->symbol
                                                    )
                                                        {{ $item->product
                                                            ->unit
                                                            ->symbol }}
                                                    @endif

                                                </div>

                                            </div>

                                        @endforeach

                                    </div>

                                @elseif (
                                    $item->product
                                    && $item->product->track_stock
                                )

                                    <span class="table-secondary">
                                        Stock simples
                                    </span>

                                @else

                                    <span class="table-secondary">
                                        Sem controlo de stock
                                    </span>

                                @endif

                            </td>


                            <td>

                                {{ number_format(
                                    $item->unit_price,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </td>


                            <td>

                                {{ number_format(
                                    $item->tax_total,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </td>


                            <td>

                                <strong>

                                    {{ number_format(
                                        $item->total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    MT

                                </strong>

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- =========================================================================
     PAGAMENTOS
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Pagamentos
            </h2>

            <p class="panel-subtitle">

                @if (
                    $sale->payment_status
                    === 'paid'
                )

                    Venda totalmente liquidada.

                @elseif (
                    $sale->payment_status
                    === 'partial'
                )

                    Existe saldo pendente desta venda.

                @else

                    Venda ainda sem pagamento.

                @endif

            </p>

        </div>


        @if (
            (float) $sale->amount_due > 0
        )

            <a
                href="{{ route(
                    'receivables.show',
                    $sale
                ) }}"
                class="topbar-button receive-button"
            >
                + Registar pagamento
            </a>

        @endif

    </div>


    <div class="panel-body">

        @forelse (
            $sale->payments
            as $payment
        )

            <div class="sale-payment-row">

                <div>

                    <strong>

                        {{ $paymentLabels[
                            $payment->method
                        ] ?? $payment->method }}

                    </strong>


                    <div class="table-secondary">

                        {{ $payment->paid_at
                            ?->format('d/m/Y H:i')
                            ?? $payment->created_at
                                ->format('d/m/Y H:i')
                        }}

                    </div>


                    @if ($payment->reference)

                        <div class="table-secondary">

                            Ref:
                            {{ $payment->reference }}

                        </div>

                    @endif

                </div>


                <strong>

                    {{ number_format(
                        $payment->amount,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>

            </div>

        @empty

            <div class="empty-state">

                Nenhum pagamento foi registado para esta venda.

            </div>

        @endforelse


        {{-- =============================================================
             TOTAIS
        ============================================================== --}}

        <div class="sale-total-summary">

            <div>

                <span>
                    Subtotal
                </span>

                <span>

                    {{ number_format(
                        $sale->subtotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </span>

            </div>


            @if (
                (float) $sale->discount_total > 0
            )

                <div>

                    <span>
                        Desconto
                    </span>

                    <span>

                        -
                        {{ number_format(
                            $sale->discount_total,
                            2,
                            ',',
                            '.'
                        ) }}

                        MT

                    </span>

                </div>

            @endif


            <div>

                <span>
                    IVA
                </span>

                <span>

                    {{ number_format(
                        $sale->tax_total,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </span>

            </div>


            <div class="sale-total-main">

                <span>
                    Total
                </span>

                <span>

                    {{ number_format(
                        $sale->total,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </span>

            </div>


            <div class="sale-total-paid">

                <span>
                    Total pago
                </span>

                <span>

                    {{ number_format(
                        $sale->paid_total,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </span>

            </div>


            @if (
                (float) $sale->amount_due > 0
            )

                <div class="sale-total-due">

                    <span>
                        Por receber
                    </span>

                    <span>

                        {{ number_format(
                            $sale->amount_due,
                            2,
                            ',',
                            '.'
                        ) }}

                        MT

                    </span>

                </div>

            @endif

        </div>

    </div>

</div>

{{-- =========================================================================
     DEVOLUÇÕES
============================================================================ --}}

@if ($sale->returns->isNotEmpty())

    <div
        class="panel"
        style="margin-top:22px;"
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Devoluções
                </h2>

                <p class="panel-subtitle">
                    Histórico de devoluções desta venda.
                </p>

            </div>

        </div>


        <div class="panel-body">

            <div class="sale-return-list">

                @foreach ($sale->returns as $return)

                    <div class="sale-return-card">

                        <div class="sale-return-header">

                            <div>

                                <strong>
                                    {{ $return->number }}
                                </strong>

                                <div class="table-secondary">

                                    {{ $return->returned_at
                                        ?->format('d/m/Y H:i')
                                        ?? '—'
                                    }}

                                </div>

                            </div>


                            <div class="sale-return-value">

                                {{ number_format(
                                    (float) $return->total,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </div>

                        </div>


                        <div class="sale-return-meta">

                            <div>

                                <span class="sale-info-label">
                                    Tipo
                                </span>

                                <strong>

                                    {{ $return->return_type === 'full'
                                        ? 'Total'
                                        : 'Parcial'
                                    }}

                                </strong>

                            </div>


                            <div>

                                <span class="sale-info-label">
                                    Reembolso
                                </span>

                                <strong>

                                    @if (
                                        $return->refund_method
                                        === 'cash'
                                    )

                                        Dinheiro

                                    @elseif (
                                        $return->refund_method
                                        === 'other'
                                    )

                                        Outro

                                    @else

                                        Sem reembolso

                                    @endif

                                </strong>

                            </div>


                            <div>

                                <span class="sale-info-label">
                                    Valor reembolsado
                                </span>

                                <strong>

                                    {{ number_format(
                                        (float) $return->refund_amount,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    MT

                                </strong>

                            </div>


                            <div>

                                <span class="sale-info-label">
                                    Registado por
                                </span>

                                <strong>
                                    {{ $return->user?->name ?? '—' }}
                                </strong>

                            </div>

                        </div>


                        @if ($return->reason)

                            <div class="sale-return-reason">

                                <span class="sale-info-label">
                                    Motivo
                                </span>

                                <div>
                                    {{ $return->reason }}
                                </div>

                            </div>

                        @endif


                        @if ($return->notes)

                            <div class="sale-return-reason">

                                <span class="sale-info-label">
                                    Observações
                                </span>

                                <div>
                                    {{ $return->notes }}
                                </div>

                            </div>

                        @endif


                        <div class="sale-return-products">

                            @foreach (
                                $return->items
                                as $returnItem
                            )

                                <div class="sale-return-product">

                                    <div>

                                        <strong>

                                            {{ $returnItem
                                                ->product
                                                ?->name
                                                ?? 'Produto removido'
                                            }}

                                        </strong>


                                        @if (
                                            $returnItem
                                                ->stockBatches
                                                ->isNotEmpty()
                                        )

                                            <div class="table-secondary">

                                                Lote:

                                                @foreach (
                                                    $returnItem
                                                        ->stockBatches
                                                    as $batch
                                                )

                                                    {{ $batch->code }}

                                                    (
                                                    {{ rtrim(
                                                        rtrim(
                                                            number_format(
                                                                (float) $batch
                                                                    ->pivot
                                                                    ->quantity,
                                                                3,
                                                                ',',
                                                                '.'
                                                            ),
                                                            '0'
                                                        ),
                                                        ','
                                                    ) }}
                                                    )

                                                    @if (! $loop->last)
                                                        •
                                                    @endif

                                                @endforeach

                                            </div>

                                        @endif

                                    </div>


                                    <div>

                                        <strong>

                                            {{ rtrim(
                                                rtrim(
                                                    number_format(
                                                        (float) $returnItem
                                                            ->quantity,
                                                        3,
                                                        ',',
                                                        '.'
                                                    ),
                                                    '0'
                                                ),
                                                ','
                                            ) }}

                                            @if (
                                                $returnItem
                                                    ->product
                                                    ?->unit
                                                    ?->symbol
                                            )

                                                {{ $returnItem
                                                    ->product
                                                    ->unit
                                                    ->symbol
                                                }}

                                            @endif

                                        </strong>

                                    </div>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

@endif

{{-- =========================================================================
     MODAL — REGISTAR DEVOLUÇÃO
============================================================================ --}}

<div
    id="returnModal"
    class="sale-return-modal"
>

    <div
        class="sale-return-modal-backdrop"
        onclick="closeReturnModal()"
    ></div>


    <div class="sale-return-modal-dialog">

        <div class="sale-return-modal-header">

            <div>

                <h2 class="panel-title">
                    Registar devolução
                </h2>

                <p class="panel-subtitle">
                    Venda {{ $sale->number }}
                </p>

            </div>


            <button
                type="button"
                class="sale-return-modal-close"
                onclick="closeReturnModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="{{ route(
                'sales.returns.store',
                $sale
            ) }}"
        >

            @csrf


            <div class="sale-return-modal-body">

                {{-- =========================================================
                     PRODUTOS
                ========================================================== --}}

                <div class="sale-return-form-section">

                    <h3>
                        Produtos
                    </h3>

                    <p class="table-secondary">
                        Indique a quantidade que o cliente
                        está a devolver.
                    </p>


                    <div class="return-items">

                        @foreach ($sale->items as $item)

                            @php
                                $alreadyReturned =
                                    (float) $item
                                        ->returnItems
                                        ->sum('quantity');

                                $available =
                                    max(
                                        0,
                                        (float) $item->quantity
                                        - $alreadyReturned
                                    );
                            @endphp


                            @if ($available > 0.0005)

                                <div class="return-item-row">

                                    <input
                                        type="hidden"
                                        name="items[{{ $loop->index }}][sale_item_id]"
                                        value="{{ $item->id }}"
                                    >


                                    <div class="return-item-info">

                                        <strong>

                                            {{ $item->product?->name
                                                ?? 'Produto removido'
                                            }}

                                        </strong>


                                        <div class="table-secondary">

                                            Vendido:

                                            {{ rtrim(
                                                rtrim(
                                                    number_format(
                                                        (float) $item->quantity,
                                                        3,
                                                        ',',
                                                        '.'
                                                    ),
                                                    '0'
                                                ),
                                                ','
                                            ) }}


                                            @if (
                                                $item->product
                                                    ?->unit
                                                    ?->symbol
                                            )

                                                {{ $item->product
                                                    ->unit
                                                    ->symbol
                                                }}

                                            @endif


                                            · Disponível:

                                            {{ rtrim(
                                                rtrim(
                                                    number_format(
                                                        $available,
                                                        3,
                                                        ',',
                                                        '.'
                                                    ),
                                                    '0'
                                                ),
                                                ','
                                            ) }}


                                            @if (
                                                $item->product
                                                    ?->unit
                                                    ?->symbol
                                            )

                                                {{ $item->product
                                                    ->unit
                                                    ->symbol
                                                }}

                                            @endif

                                        </div>

                                    </div>


                                    <div class="return-item-input">

                                        <label>
                                            Devolver
                                        </label>

                                        <input
                                            type="number"
                                            name="items[{{ $loop->index }}][quantity]"
                                            min="0"
                                            max="{{ $available }}"
                                            step="0.001"
                                            value="{{ old('items.' . $loop->index . '.quantity',0) }}"
                                            class="form-control return-quantity"
                                            data-max="{{ $available }}"
                                            data-line-total="{{ (float) $item->total }}"
                                            data-sold-quantity="{{ (float) $item->quantity }}"
                                        >

                                    </div>

                                </div>

                            @endif

                        @endforeach

                    </div>

                </div>


                {{-- =========================================================
                     REEMBOLSO
                ========================================================== --}}

                <div class="sale-return-form-section">

                    <h3>
                        Reembolso
                    </h3>


                    <div class="form-group">

                        <label class="form-label">
                            Método
                        </label>

                        <select
                            name="refund_method"
                            id="refundMethod"
                            class="form-control"
                            onchange="updateRefundFields()"
                        >

                            <option
                                value="none"
                                @selected(
                                    old(
                                        'refund_method',
                                        'none'
                                    ) === 'none'
                                )
                            >
                                Sem reembolso
                            </option>

                            <option
                                value="cash"
                                @selected(
                                    old('refund_method')
                                    === 'cash'
                                )
                            >
                                Dinheiro
                            </option>

                            <option
                                value="other"
                                @selected(
                                    old('refund_method')
                                    === 'other'
                                )
                            >
                                Outro
                            </option>

                        </select>

                    </div>


                    <div
                        class="form-group"
                        id="refundAmountGroup"
                        style="display:none;"
                    >

                        <label class="form-label">
                            Valor a reembolsar
                        </label>

                        <input
                            type="number"
                            name="refund_amount"
                            id="refundAmount"
                            class="form-control"
                            min="0"
                            step="0.01"
                            value="{{ old('refund_amount',0) }}"
                        >


                        <div
                            class="table-secondary"
                            style="margin-top:6px;"
                        >
                            Valor dos produtos seleccionados:

                            <strong id="returnEstimatedTotal">
                                0,00 MT
                            </strong>

                        </div>

                    </div>


                    <div
                        class="form-group"
                        id="refundReferenceGroup"
                        style="display:none;"
                    >

                        <label class="form-label">
                            Referência
                        </label>

                        <input
                            type="text"
                            name="refund_reference"
                            class="form-control"
                            maxlength="150"
                            value="{{ old('refund_reference') }}"
                            placeholder="Referência do reembolso"
                            
                        >

                    </div>

                </div>


                {{-- =========================================================
                     MOTIVO
                ========================================================== --}}

                <div class="sale-return-form-section">

                    <div class="form-group">

                        <label class="form-label">
                            Motivo
                        </label>

                        <input
                            type="text"
                            name="reason"
                            class="form-control"
                            maxlength="255"
                            value="{{ old('reason') }}"
                            placeholder="Ex.: Produto devolvido pelo cliente"
                        >

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Observações
                        </label>

                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                            maxlength="2000"
                        >{{ old('notes') }}
                        </textarea>

                    </div>

                </div>

            </div>


            <div class="sale-return-modal-footer">

                <button
                    type="button"
                    class="btn btn-secondary"
                    onclick="closeReturnModal()"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Confirmar devolução
                </button>

            </div>

        </form>

    </div>

</div>

@endsection


@section('styles')

.sale-metric-grid {
    display:grid;
    grid-template-columns:
        repeat(4,minmax(0,1fr));
    gap:18px;
}

.payment-status-partial {
    background:#fff7df;
    color:#8a5c00;
}

.payment-status-unpaid {
    background:#fff0f0;
    color:#b42318;
}

.receive-button {
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}

.sale-info-grid {
    display:grid;
    grid-template-columns:
        repeat(3,minmax(0,1fr));
    gap:25px;
}

.sale-info-grid > div {
    display:flex;
    flex-direction:column;
    gap:6px;
}

.sale-info-label {
    color:var(--muted);
    font-size:11px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
}

.sale-payment-row {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    padding:14px 0;
    border-bottom:1px solid var(--border);
}

.sale-payment-row:first-child {
    padding-top:0;
}

.sale-total-summary {
    max-width:420px;
    display:grid;
    gap:10px;
    margin-top:25px;
    margin-left:auto;
}

.sale-total-summary > div {
    display:flex;
    justify-content:space-between;
    gap:20px;
}

.sale-total-main {
    margin-top:5px;
    padding-top:14px;
    border-top:1px solid var(--border);
    font-size:20px;
    font-weight:850;
}

.sale-total-paid {
    padding-top:10px;
    color:var(--primary-dark);
    font-weight:800;
}

.sale-total-due {
    padding-top:10px;
    border-top:1px dashed var(--border);
    color:#8a5c00;
    font-size:16px;
    font-weight:850;
}

.sale-batch-list {
    display:grid;
    gap:7px;
    min-width:190px;
}

.sale-batch-item {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:15px;
    padding:7px 0;
    border-bottom:1px dashed var(--border);
}

.sale-batch-item:last-child {
    border-bottom:0;
}

.sale-batch-quantity {
    white-space:nowrap;
    font-weight:800;
}

@media (max-width:1100px) {

    .sale-metric-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:750px) {

    .sale-info-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:550px) {

    .sale-metric-grid,
    .sale-info-grid {
        grid-template-columns:1fr;
    }

}

/* ==========================================================================
   DEVOLUÇÕES
============================================================================ */

.sale-return-list {
    display:grid;
    gap:16px;
}

.sale-return-card {
    border:1px solid var(--border);
    border-radius:12px;
    padding:18px;
}

.sale-return-header {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    padding-bottom:15px;
    border-bottom:1px solid var(--border);
}

.sale-return-value {
    font-size:18px;
    font-weight:850;
    white-space:nowrap;
}

.sale-return-meta {
    display:grid;
    grid-template-columns:
        repeat(4,minmax(0,1fr));
    gap:18px;
    padding:16px 0;
}

.sale-return-meta > div {
    display:flex;
    flex-direction:column;
    gap:5px;
}

.sale-return-reason {
    display:grid;
    gap:5px;
    padding:10px 0;
}

.sale-return-products {
    margin-top:10px;
    border-top:1px solid var(--border);
}

.sale-return-product {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:20px;
    padding:12px 0;
    border-bottom:1px dashed var(--border);
}

.sale-return-product:last-child {
    border-bottom:0;
}


/* ==========================================================================
   MODAL DE DEVOLUÇÃO
============================================================================ */

.sale-return-modal {
    display:none;
    position:fixed;
    inset:0;
    z-index:9999;
}

.sale-return-modal.is-open {
    display:flex;
    align-items:center;
    justify-content:center;
    padding:25px;
}

.sale-return-modal-backdrop {
    position:absolute;
    inset:0;
    background:rgba(0,0,0,.55);
}

.sale-return-modal-dialog {
    position:relative;
    z-index:1;
    width:min(850px,100%);
    max-height:calc(100vh - 50px);
    overflow-y:auto;
    background:var(--card);
    border-radius:16px;
    box-shadow:0 20px 60px rgba(0,0,0,.25);
}

.sale-return-modal-header {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:20px;
    padding:20px 24px;
    border-bottom:1px solid var(--border);
}

.sale-return-modal-body {
    padding:24px;
}

.sale-return-modal-footer {
    display:flex;
    justify-content:flex-end;
    align-items:center;
    gap:10px;
    padding:18px 24px;
    border-top:1px solid var(--border);
}

.sale-return-modal-close {
    border:0;
    background:transparent;
    font-size:30px;
    line-height:1;
    cursor:pointer;
}

.sale-return-form-section + .sale-return-form-section {
    margin-top:25px;
    padding-top:25px;
    border-top:1px solid var(--border);
}

.return-items {
    display:grid;
    gap:10px;
    margin-top:15px;
}

.return-item-row {
    display:grid;
    grid-template-columns:minmax(0,1fr) 140px;
    align-items:center;
    gap:20px;
    padding:14px;
    border:1px solid var(--border);
    border-radius:10px;
}

.return-item-info {
    min-width:0;
}

.return-item-input label {
    display:block;
    margin-bottom:5px;
    font-size:12px;
    font-weight:700;
}


/* ==========================================================================
   RESPONSIVO — DEVOLUÇÕES
============================================================================ */

@media (max-width:750px) {

    .sale-return-meta {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

    .return-item-row {
        grid-template-columns:1fr;
    }

    .sale-return-modal.is-open {
        padding:10px;
    }

    .sale-return-modal-dialog {
        max-height:calc(100vh - 20px);
    }
}

@media (max-width:550px) {

    .sale-return-meta {
        grid-template-columns:1fr;
    }

    .sale-return-header,
    .sale-return-product {
        flex-direction:column;
    }
}

@endsection

@section('scripts')

<script>

function openReturnModal() {

    const modal =
        document.getElementById(
            'returnModal'
        );

    if (! modal) {
        return;
    }

    modal.classList.add(
        'is-open'
    );

    document.body.style.overflow =
        'hidden';

    calculateReturnTotal();
}


function closeReturnModal() {

    const modal =
        document.getElementById(
            'returnModal'
        );

    if (! modal) {
        return;
    }

    modal.classList.remove(
        'is-open'
    );

    document.body.style.overflow =
        '';
}


/*
|--------------------------------------------------------------------------
| Campos de reembolso
|--------------------------------------------------------------------------
*/

function updateRefundFields() {

    const method =
        document.getElementById(
            'refundMethod'
        );

    const amountGroup =
        document.getElementById(
            'refundAmountGroup'
        );

    const referenceGroup =
        document.getElementById(
            'refundReferenceGroup'
        );

    const amount =
        document.getElementById(
            'refundAmount'
        );

    if (
        ! method
        || ! amountGroup
        || ! referenceGroup
        || ! amount
    ) {
        return;
    }


    if (method.value === 'none') {

        amountGroup.style.display =
            'none';

        referenceGroup.style.display =
            'none';

        amount.value =
            '0';

        return;
    }


    amountGroup.style.display =
        'block';


    if (method.value === 'other') {

        referenceGroup.style.display =
            'block';

    } else {

        referenceGroup.style.display =
            'none';

    }
}


/*
|--------------------------------------------------------------------------
| Calcular valor estimado da devolução
|--------------------------------------------------------------------------
*/

function calculateReturnTotal() {

    let total = 0;

    const inputs =
        document.querySelectorAll(
            '.return-quantity'
        );


    inputs.forEach(
        function (input) {

            let quantity =
                parseFloat(
                    input.value || '0'
                );

            const max =
                parseFloat(
                    input.dataset.max || '0'
                );

            const lineTotal =
                parseFloat(
                    input.dataset.lineTotal || '0'
                );

            const soldQuantity =
                parseFloat(
                    input.dataset.soldQuantity || '0'
                );


            if (
                ! Number.isFinite(quantity)
                || quantity < 0
            ) {
                quantity = 0;
            }


            if (
                Number.isFinite(max)
                && quantity > max
            ) {
                quantity = max;

                input.value =
                    max;
            }


            if (
                quantity > 0
                && soldQuantity > 0
            ) {
                total +=
                    (
                        lineTotal
                        / soldQuantity
                    )
                    * quantity;
            }

        }
    );


    const totalElement =
        document.getElementById(
            'returnEstimatedTotal'
        );


    if (totalElement) {

        totalElement.textContent =
            total.toLocaleString(
                'pt-PT',
                {
                    minimumFractionDigits:2,
                    maximumFractionDigits:2
                }
            )
            + ' MT';

    }

}


/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        document
            .querySelectorAll(
                '.return-quantity'
            )
            .forEach(
                function (input) {

                    input.addEventListener(
                        'input',
                        calculateReturnTotal
                    );

                }
            );


        updateRefundFields();

        calculateReturnTotal();


        /*
        |--------------------------------------------------------------------------
        | Se Laravel devolveu erro de validação
        |--------------------------------------------------------------------------
        |
        | O modal volta a abrir automaticamente.
        |
        */

        @if ($errors->any())

            const hasReturnInput =
                @json(
                    old('items') !== null
                    || old('refund_method') !== null
                );

            if (hasReturnInput) {
                openReturnModal();
            }

        @endif

    }
);


/*
|--------------------------------------------------------------------------
| ESC fecha o modal
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (event) {

        if (event.key === 'Escape') {
            closeReturnModal();
        }

    }
);

</script>

@endsection