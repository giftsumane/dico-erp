@extends('layouts.app')

@section('title', 'Ajustar Stock | DICO')
@section('topbar-title', 'Stock')

@section('content')

@php

    $product = $stock->product;

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

@endphp


<div class="page-heading">

    <h1>
        Ajustar stock
    </h1>

    <p>
        Corrija a quantidade existente na loja
        <strong>
            {{ $activeStore?->name }}
        </strong>.
    </p>

</div>


<div
    class="panel"
    style="max-width:700px;"
>

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
             STOCK ACTUAL
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

            <div
                style="
                    padding:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
                "
            >

                <div class="table-secondary">
                    Stock actual
                </div>

                <strong
                    style="
                        display:block;
                        margin-top:5px;
                        font-size:22px;
                    "
                >

                    {{ rtrim(
                        rtrim(
                            number_format(
                                (float) $stock->quantity,
                                3,
                                ',',
                                '.'
                            ),
                            '0'
                        ),
                        ','
                    ) }}

                    {{ $product?->unit?->symbol }}

                </strong>

            </div>


            <div
                style="
                    padding:16px;
                    border:1px solid var(--border);
                    border-radius:12px;
                "
            >

                <div class="table-secondary">
                    Tipo
                </div>

                <strong
                    style="
                        display:block;
                        margin-top:5px;
                    "
                >
                    Stock simples
                </strong>

            </div>

        </div>


        {{-- ============================================================
             FORMULÁRIO
        ============================================================= --}}

        <form
            method="POST"
            action="{{ route(
                'stocks.update',
                $stock
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="form-group">

                <label class="form-label">
                    Quantidade correcta *
                </label>

                <input
                    type="number"
                    name="quantity"
                    value="{{ old(
                        'quantity',
                        $stock->quantity
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
                    Introduza a quantidade física correcta.
                    O DICO calculará automaticamente a diferença.
                </div>

            </div>


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
                O stock não será sobrescrito directamente.
                A diferença ficará registada em Movimentos
                de Stock para preservar o histórico.
            </div>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:24px;
                "
            >

                <a
                    href="{{ route('stocks.index') }}"
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