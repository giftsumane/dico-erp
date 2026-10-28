@extends('layouts.app')

@section('title', 'Lotes de Stock | DICO')
@section('topbar-title', 'Stock')

@section('content')

@php

    $variantLabel = null;

    if ($product?->isVariant()) {

        $variantLabel = $product
            ->attributeValues
            ->sortBy(
                fn ($value) =>
                    $value->attribute?->position ?? 0
            )
            ->pluck('value')
            ->implode(' / ');
    }

    /*
    |--------------------------------------------------------------------------
    | Reconciliação
    |--------------------------------------------------------------------------
    */

    $batchTotal = (float) $product
        ->stockBatches()
        ->where('store_id', $stock->store_id)
        ->sum('quantity');

    $stockTotal = (float) $stock->quantity;

    $difference = round(
        $stockTotal - $batchTotal,
        3
    );

    $isReconciled =
        abs($difference) < 0.0005;


    /*
    |--------------------------------------------------------------------------
    | Formatação
    |--------------------------------------------------------------------------
    */

    $formatQuantity = function ($value) {

        return rtrim(
            rtrim(
                number_format(
                    (float) $value,
                    3,
                    ',',
                    '.'
                ),
                '0'
            ),
            ','
        );
    };

@endphp


<div class="page-heading">

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            flex-wrap:wrap;
            gap:20px;
        "
    >

        <div>

            <h1>
                Lotes de stock
            </h1>

            <p>

                <strong>
                    {{ $product?->name }}
                </strong>

                @if ($variantLabel)

                    · {{ $variantLabel }}

                @endif


                @if ($product?->sku)

                    · SKU:
                    {{ $product->sku }}

                @endif

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
                href="{{ route('stocks.index') }}"
                class="topbar-button"
            >
                ← Voltar ao stock
            </a>


            <a
                href="{{ route('stocks.create') }}"
                class="topbar-button"
                style="
                    background:var(--primary);
                    color:white;
                    border-color:var(--primary);
                "
            >
                + Entrada de stock
            </a>

        </div>

    </div>

</div>


{{-- ============================================================
     SUCESSO
============================================================= --}}

@if (session('success'))

    <div
        style="
            margin-bottom:18px;
            padding:14px 16px;
            border:1px solid #abefc6;
            border-radius:10px;
            background:#ecfdf3;
            color:#067647;
        "
    >
        {{ session('success') }}
    </div>

@endif


{{-- ============================================================
     ERROS
============================================================= --}}

@if ($errors->any())

    <div
        style="
            margin-bottom:18px;
            padding:14px 16px;
            border:1px solid #fecdca;
            border-radius:10px;
            background:#fff0f0;
            color:#b42318;
        "
    >

        @foreach ($errors->all() as $error)

            <div>
                {{ $error }}
            </div>

        @endforeach

    </div>

@endif


{{-- ============================================================
     RESUMO
============================================================= --}}

<div
    style="
        display:grid;
        grid-template-columns:
            repeat(
                auto-fit,
                minmax(200px, 1fr)
            );
        gap:12px;
        margin-bottom:22px;
    "
>

    {{-- STOCK TOTAL --}}

    <div
        style="
            padding:16px;
            border:1px solid var(--border);
            border-radius:12px;
            background:white;
        "
    >

        <div class="table-secondary">
            Stock total
        </div>

        <strong
            style="
                display:block;
                margin-top:5px;
                font-size:22px;
            "
        >

            {{ $formatQuantity($stockTotal) }}

            {{ $product?->unit?->symbol }}

        </strong>

    </div>


    {{-- SOMA DOS LOTES --}}

    <div
        style="
            padding:16px;
            border:1px solid var(--border);
            border-radius:12px;
            background:white;
        "
    >

        <div class="table-secondary">
            Soma dos lotes
        </div>

        <strong
            style="
                display:block;
                margin-top:5px;
                font-size:22px;
            "
        >

            {{ $formatQuantity($batchTotal) }}

            {{ $product?->unit?->symbol }}

        </strong>

    </div>


    {{-- RECONCILIAÇÃO --}}

    <div
        style="
            padding:16px;
            border:1px solid var(--border);
            border-radius:12px;
            background:white;
        "
    >

        <div class="table-secondary">
            Reconciliação
        </div>


        <div style="margin-top:7px;">

            @if ($isReconciled)

                <span class="status-badge">
                    Correcto
                </span>

            @else

                <span
                    class="status-badge"
                    style="
                        background:#fff0f0;
                        color:#b42318;
                    "
                >
                    Diferença detectada
                </span>

            @endif

        </div>


        @if (! $isReconciled)

            <div
                style="
                    margin-top:7px;
                    color:var(--muted);
                    font-size:12px;
                "
            >
                Diferença:
                {{ $formatQuantity($difference) }}
                {{ $product?->unit?->symbol }}
            </div>

        @endif

    </div>

</div>


{{-- ============================================================
     TABELA DE LOTES
============================================================= --}}

<div class="panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Lotes
            </h2>

            <p class="panel-subtitle">
                Consulte o saldo individual de cada lote
                deste produto.
            </p>

        </div>

    </div>


    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>

                        <th>Lote</th>

                        <th>Fornecedor</th>

                        <th>Recepção</th>

                        <th>Inicial</th>

                        <th>Disponível</th>

                        <th>Custo Unit.</th>

                        @if ($hasExpiryDates)

                            <th>Validade</th>

                        @endif

                        <th>Estado</th>

                        <th>Acção</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse ($batches as $batch)

                        <tr>

                            {{-- ==========================================
                                 LOTE
                            =========================================== --}}

                            <td>

                                <strong>
                                    {{ $batch->code }}
                                </strong>


                                @if ($batch->name)

                                    <div
                                        class="table-secondary"
                                        style="margin-top:5px;"
                                    >
                                        {{ $batch->name }}
                                    </div>

                                @endif

                            </td>


                            {{-- ==========================================
                                 FORNECEDOR
                            =========================================== --}}

                            <td>

                                {{ $batch->supplier?->name
                                    ?? '—' }}

                            </td>


                            {{-- ==========================================
                                 RECEPÇÃO
                            =========================================== --}}

                            <td>

                                {{ $batch->received_at
                                    ? $batch->received_at->format('d/m/Y')
                                    : '—'
                                }}

                            </td>


                            {{-- ==========================================
                                 INICIAL
                            =========================================== --}}

                            <td>

                                {{ $formatQuantity(
                                    $batch->initial_quantity
                                ) }}

                            </td>


                            {{-- ==========================================
                                 DISPONÍVEL
                            =========================================== --}}

                            <td>

                                <strong>

                                    {{ $formatQuantity(
                                        $batch->quantity
                                    ) }}

                                </strong>

                            </td>


                            {{-- ==========================================
                                 CUSTO
                            =========================================== --}}

                            <td>

                                {{ number_format(
                                    (float) $batch->unit_cost,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </td>


                            {{-- ==========================================
                                 VALIDADE
                            =========================================== --}}

                            @if ($hasExpiryDates)

                                <td>

                                    {{ $batch->expiry_date
                                        ? $batch->expiry_date->format('d/m/Y')
                                        : '—'
                                    }}

                                </td>

                            @endif


                            {{-- ==========================================
                                 ESTADO
                            =========================================== --}}

                            <td>

                                @if (
                                    $batch->status
                                    ===
                                    \App\Models\StockBatch::STATUS_ACTIVE
                                )

                                    <span class="status-badge">
                                        Activo
                                    </span>


                                @elseif (
                                    $batch->status
                                    ===
                                    \App\Models\StockBatch::STATUS_EXHAUSTED
                                )

                                    <span
                                        class="status-badge"
                                        style="
                                            background:#f2f4f7;
                                            color:#475467;
                                        "
                                    >
                                        Esgotado
                                    </span>


                                @elseif (
                                    $batch->status
                                    ===
                                    \App\Models\StockBatch::STATUS_CLOSED
                                )

                                    <span
                                        class="status-badge"
                                        style="
                                            background:#f2f4f7;
                                            color:#475467;
                                        "
                                    >
                                        Encerrado
                                    </span>


                                @else

                                    <span class="table-secondary">
                                        {{ $batch->status }}
                                    </span>

                                @endif

                            </td>


                            {{-- ==========================================
                                 ACÇÃO
                            =========================================== --}}

                            <td>

                                @if (
                                    $batch->status
                                    ===
                                    \App\Models\StockBatch::STATUS_CLOSED
                                )

                                    <span class="table-secondary">
                                        Indisponível
                                    </span>

                                @else

                                    <a
                                        href="{{ route(
                                            'stocks.batches.edit',
                                            [
                                                'stock' => $stock,
                                                'batch' => $batch,
                                            ]
                                        ) }}"
                                        class="topbar-button"
                                    >
                                        Ajustar
                                    </a>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="{{ $hasExpiryDates ? 9 : 8 }}"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem lotes
                                para este produto.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div style="margin-top:20px;">
            {{ $batches->links() }}
        </div>

    </div>

</div>

@endsection