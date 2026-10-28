@extends('layouts.app')

@section('title', 'Ajustar Lote | DICO')
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

    <h1>
        Ajustar lote
    </h1>

    <p>
        Corrija a quantidade existente no lote
        <strong>
            {{ $batch->code }}
        </strong>
        da loja
        <strong>
            {{ $activeStore?->name }}
        </strong>.
    </p>

</div>


<div
    class="panel"
    style="max-width:700px;"
>

    {{-- ============================================================
         CABEÇALHO
    ============================================================= --}}

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                {{ $product?->name }}
            </h2>


            <p class="panel-subtitle">

                @if ($variantLabel)

                    Variante:
                    {{ $variantLabel }}

                @endif


                @if ($product?->sku)

                    {{ $variantLabel ? ' · ' : '' }}

                    SKU:
                    {{ $product->sku }}

                @endif

            </p>

        </div>

    </div>


    <div class="panel-body">


        {{-- ============================================================
             RESUMO DO LOTE
        ============================================================= --}}

        <div
            style="
                display:grid;
                grid-template-columns:
                    repeat(
                        auto-fit,
                        minmax(180px, 1fr)
                    );
                gap:12px;
                margin-bottom:22px;
            "
        >

            {{-- LOTE --}}

            <div
                style="
                    padding:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
                "
            >

                <div class="table-secondary">
                    Lote
                </div>

                <strong
                    style="
                        display:block;
                        margin-top:5px;
                    "
                >
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

            </div>


            {{-- QUANTIDADE INICIAL --}}

            <div
                style="
                    padding:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
                "
            >

                <div class="table-secondary">
                    Quantidade inicial
                </div>

                <strong
                    style="
                        display:block;
                        margin-top:5px;
                        font-size:22px;
                    "
                >

                    {{ $formatQuantity(
                        $batch->initial_quantity
                    ) }}

                    {{ $product?->unit?->symbol }}

                </strong>

            </div>


            {{-- SALDO ACTUAL --}}

            <div
                style="
                    padding:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
                "
            >

                <div class="table-secondary">
                    Saldo actual
                </div>

                <strong
                    style="
                        display:block;
                        margin-top:5px;
                        font-size:22px;
                    "
                >

                    {{ $formatQuantity(
                        $batch->quantity
                    ) }}

                    {{ $product?->unit?->symbol }}

                </strong>

            </div>


            {{-- STOCK TOTAL --}}

            <div
                style="
                    padding:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
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

                    {{ $formatQuantity(
                        $stock->quantity
                    ) }}

                    {{ $product?->unit?->symbol }}

                </strong>

            </div>

        </div>


        {{-- ============================================================
             INFORMAÇÃO DO LOTE
        ============================================================= --}}

        <div
            style="
                padding:16px;
                border:1px solid var(--border);
                border-radius:12px;
                margin-bottom:22px;
            "
        >

            <div
                style="
                    display:grid;
                    grid-template-columns:
                        repeat(
                            auto-fit,
                            minmax(150px, 1fr)
                        );
                    gap:18px;
                "
            >

                {{-- FORNECEDOR --}}

                <div>

                    <div class="table-secondary">
                        Fornecedor
                    </div>

                    <strong
                        style="
                            display:block;
                            margin-top:5px;
                        "
                    >
                        {{ $batch->supplier?->name
                            ?? 'Não definido' }}
                    </strong>

                </div>


                {{-- RECEPÇÃO --}}

                <div>

                    <div class="table-secondary">
                        Recepção
                    </div>

                    <strong
                        style="
                            display:block;
                            margin-top:5px;
                        "
                    >

                        {{ $batch->received_at
                            ? $batch->received_at->format('d/m/Y')
                            : 'Não definida'
                        }}

                    </strong>

                </div>


                {{-- CUSTO --}}

                <div>

                    <div class="table-secondary">
                        Custo unitário
                    </div>

                    <strong
                        style="
                            display:block;
                            margin-top:5px;
                        "
                    >

                        {{ number_format(
                            (float) $batch->unit_cost,
                            2,
                            ',',
                            '.'
                        ) }}

                        MT

                    </strong>

                </div>


                {{-- VALIDADE --}}

                @if ($batch->expiry_date)

                    <div>

                        <div class="table-secondary">
                            Validade
                        </div>

                        <strong
                            style="
                                display:block;
                                margin-top:5px;
                            "
                        >

                            {{ $batch
                                ->expiry_date
                                ->format('d/m/Y') }}

                        </strong>

                    </div>

                @endif

            </div>

        </div>


        {{-- ============================================================
             FORMULÁRIO
        ============================================================= --}}

        <form
            method="POST"
            action="{{ route(
                'stocks.batches.update',
                [
                    'stock' => $stock,
                    'batch' => $batch,
                ]
            ) }}"
        >

            @csrf
            @method('PUT')


            {{-- QUANTIDADE --}}

            <div class="form-group">

                <label class="form-label">
                    Quantidade correcta *
                </label>


                <input
                    type="number"
                    name="quantity"
                    value="{{ old(
                        'quantity',
                        $batch->quantity
                    ) }}"
                    class="form-control"
                    min="0"
                    step="0.001"
                    required
                    autofocus
                >


                @error('quantity')

                    <div class="error-message">
                        {{ $message }}
                    </div>

                @enderror


                <div
                    style="
                        margin-top:7px;
                        color:var(--muted);
                        font-size:12px;
                        line-height:1.5;
                    "
                >
                    Introduza a quantidade física correcta
                    deste lote. O DICO calculará
                    automaticamente a diferença.
                </div>

            </div>


            {{-- MOTIVO --}}

            <div class="form-group">

                <label class="form-label">
                    Motivo do ajuste *
                </label>


                <textarea
                    name="notes"
                    class="form-control"
                    style="
                        min-height:110px;
                        padding-top:14px;
                    "
                    placeholder="Ex.: Correcção após contagem física, quebra, diferença de inventário..."
                    required
                >{{ old('notes') }}</textarea>


                @error('notes')

                    <div class="error-message">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            {{-- INFORMAÇÃO --}}

            <div
                style="
                    padding:14px;
                    margin-top:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
                    background:var(--primary-soft);
                    color:var(--muted);
                    font-size:12px;
                    line-height:1.6;
                "
            >
                O saldo do lote não será sobrescrito
                directamente sem histórico.

                A diferença será registada em
                Movimentos de Stock e o stock total
                do produto será actualizado automaticamente.
            </div>


            {{-- BOTÕES --}}

            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:24px;
                "
            >

                <a
                    href="{{ route(
                        'stocks.batches.index',
                        $stock
                    ) }}"
                    class="topbar-button"
                >
                    Cancelar
                </a>


                <button
                    type="submit"
                    class="topbar-button"
                    style="
                        background:var(--primary);
                        color:white;
                        border-color:var(--primary);
                    "
                >
                    Guardar ajuste
                </button>

            </div>

        </form>

    </div>

</div>

@endsection