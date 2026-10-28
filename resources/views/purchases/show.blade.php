@extends('layouts.app')

@section('title', $purchase->number . ' | DICO')
@section('topbar-title', 'Compras')

@section('content')

<div class="page-heading">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        flex-wrap:wrap;
    ">
        <div>
            <h1>{{ $purchase->number }}</h1>

            <p>
                {{ $purchase->supplier?->name ?? 'Sem fornecedor' }}
                ·
                {{ $purchase->purchase_date?->format('d/m/Y') }}
                ·
                {{ $purchase->store->name }}
            </p>
        </div>

        <div style="display:flex;gap:10px;">

            <a
                href="{{ route('purchases.index') }}"
                class="topbar-button"
            >
                Voltar
            </a>

            @if ($purchase->status === 'draft')

                <form
                    method="POST"
                    action="{{ route(
                        'purchases.confirm',
                        $purchase
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Confirmar esta compra? O stock será actualizado.'
                        );
                    "
                >
                    @csrf

                    <button
                        type="submit"
                        class="topbar-button"
                        style="
                            background:var(--primary);
                            color:#fff;
                            border-color:var(--primary);
                        "
                    >
                        Confirmar compra
                    </button>
                </form>

            @endif

        </div>
    </div>

</div>

@if ($errors->has('purchase'))
    <div
        class="error-message"
        data-validation-section
        style="
            margin-bottom:20px;
            padding:14px 16px;
            border-radius:12px;
            background:#fff0f0;
            color:#b42318;
        "
    >
        {{ $errors->first('purchase') }}
    </div>
@endif

@if ($purchase->status === 'draft')

<div class="panel" style="margin-bottom:22px;">

    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Adicionar produto
            </h2>

            <p class="panel-subtitle">
                Os produtos ainda não afectam o stock enquanto
                a compra estiver em rascunho.
            </p>
        </div>
    </div>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route(
                'purchases.items.store',
                $purchase
            ) }}"
        >
            @csrf

            <div
                style="
                    display:grid;
                    grid-template-columns:
                        minmax(240px, 2fr)
                        minmax(100px, .7fr)
                        minmax(120px, .9fr)
                        minmax(100px, .7fr);
                    gap:12px;
                    align-items:end;
                "
            >

                <div class="form-group">

                    <label class="form-label">
                        Produto
                    </label>

                    <select
                        id="purchase-product"
                        name="product_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            Seleccionar
                        </option>

                        @foreach ($products as $product)

                            @php
                                $variantLabel = null;

                                if ($product->isVariant()) {
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

                            <option
                                value="{{ $product->id }}"
                                data-inventory-mode="{{ $product->inventory_mode }}"
                                data-track-stock="{{ $product->track_stock ? '1' : '0' }}"
                                @selected(
                                    old('product_id')
                                    == $product->id
                                )
                            >
                                {{ $product->name }}

                                @if (
                                    $product->isVariant()
                                    && $variantLabel
                                )
                                    — {{ $variantLabel }}
                                @endif

                                @if ($product->sku)
                                    — SKU: {{ $product->sku }}
                                @endif

                            </option>

                        @endforeach

                    </select>

                    @error('product_id')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                    <div
                        style="
                            margin-top:6px;
                            color:var(--muted);
                            font-size:10px;
                            line-height:1.4;
                        "
                    >
                        Em produtos com variantes, seleccione a
                        variante concreta.
                    </div>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Quantidade
                    </label>

                    <input
                        type="number"
                        name="quantity"
                        value="{{ old('quantity') }}"
                        class="form-control"
                        step="0.001"
                        min="0.001"
                        required
                    >

                    @error('quantity')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Custo unitário
                    </label>

                    <input
                        type="number"
                        name="unit_cost"
                        value="{{ old('unit_cost') }}"
                        class="form-control"
                        step="0.01"
                        min="0"
                        required
                    >

                    @error('unit_cost')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        IVA %
                    </label>

                    <input
                        type="number"
                        name="tax_rate"
                        value="{{ old('tax_rate', 0) }}"
                        class="form-control"
                        step="0.01"
                        min="0"
                        max="100"
                    >

                    @error('tax_rate')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            @if ($hasStockBatches)

                <div
                    id="purchase-batch-fields"
                    data-validation-section
                    style="
                        display:none;
                        margin-top:18px;
                        padding:18px;
                        border:1px solid var(--border);
                        border-radius:12px;
                        background:var(--primary-soft);
                    "
                >

                    <div style="margin-bottom:16px;">

                        <strong>
                            Dados do lote
                        </strong>

                        <div
                            style="
                                margin-top:4px;
                                color:var(--muted);
                                font-size:12px;
                            "
                        >
                            Este produto utiliza stock por lotes.
                            A confirmação da compra criará o lote
                            automaticamente.
                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label class="form-label">
                                Código do lote *
                            </label>

                            <input
                                id="batch-code"
                                type="text"
                                name="batch_code"
                                value="{{ old('batch_code') }}"
                                class="form-control"
                                placeholder="Ex.: TUR-SET-2026"
                            >

                            @error('batch_code')
                                <div class="error-message">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Nome / referência
                            </label>

                            <input
                                type="text"
                                name="batch_name"
                                value="{{ old('batch_name') }}"
                                class="form-control"
                                placeholder="Ex.: Viagem Turquia Setembro"
                            >

                            @error('batch_name')
                                <div class="error-message">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>

                    </div>


                    <div class="form-row">

                        <div class="form-group">

                            <label class="form-label">
                                Data de recepção
                            </label>

                            <input
                                type="date"
                                name="batch_received_at"
                                value="{{ old(
                                    'batch_received_at',
                                    $purchase
                                        ->purchase_date
                                        ?->format('Y-m-d')
                                ) }}"
                                class="form-control"
                            >

                            @error('batch_received_at')
                                <div class="error-message">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        @if ($hasExpiryDates)

                            <div class="form-group">

                                <label class="form-label">
                                    Data de validade
                                </label>

                                <input
                                    type="date"
                                    name="batch_expiry_date"
                                    value="{{ old(
                                        'batch_expiry_date'
                                    ) }}"
                                    class="form-control"
                                >

                                @error('batch_expiry_date')
                                    <div class="error-message">
                                        {{ $message }}
                                    </div>
                                @enderror

                            </div>

                        @endif

                    </div>

                </div>

            @endif


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    margin-top:18px;
                "
            >

                <button
                    type="submit"
                    class="topbar-button"
                    style="
                        background:var(--primary);
                        color:#fff;
                        border-color:var(--primary);
                    "
                >
                    Adicionar produto
                </button>

            </div>

        </form>

    </div>
</div>

@endif


<div class="panel">

    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Produtos da compra
            </h2>

            <p class="panel-subtitle">
                Estado:
                {{ $purchase->status === 'confirmed'
                    ? 'Confirmada'
                    : 'Rascunho'
                }}
            </p>
        </div>
    </div>

    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Custo Unit.</th>
                        <th>IVA</th>
                        <th>Subtotal</th>
                        <th>Total</th>

                        @if ($purchase->status === 'draft')
                            <th></th>
                        @endif
                    </tr>
                </thead>

                <tbody>

                    @forelse ($purchase->items as $item)

                        <tr>

                            @php
                                $itemVariantLabel = null;

                                if ($item->product?->isVariant()) {
                                    $itemVariantLabel = $item
                                        ->product
                                        ->attributeValues
                                        ->sortBy(
                                            fn ($value) =>
                                                $value->attribute?->position ?? 0
                                        )
                                        ->pluck('value')
                                        ->implode(' / ');
                                }
                            @endphp

                            <td>

                                <strong>
                                    {{ $item->product?->name ?? 'Produto removido' }}
                                </strong>


                                @if ($item->product?->isVariant())

                                    <div
                                        style="
                                            display:flex;
                                            gap:6px;
                                            align-items:center;
                                            flex-wrap:wrap;
                                            margin-top:5px;
                                        "
                                    >

                                        <span
                                            class="status-badge"
                                            style="
                                                background:var(--primary-soft);
                                                color:var(--primary-dark);
                                            "
                                        >
                                            Variante
                                        </span>

                                        @if ($itemVariantLabel)

                                            <span class="table-secondary">
                                                {{ $itemVariantLabel }}
                                            </span>

                                        @endif

                                    </div>

                                @endif


                                @if ($item->product?->sku)

                                    <div class="table-secondary">
                                        SKU: {{ $item->product->sku }}
                                    </div>

                                @endif


                                @if ($item->batch_code)

                                    <div
                                        style="
                                            margin-top:7px;
                                            font-size:11px;
                                        "
                                    >

                                        <span
                                            class="status-badge"
                                            style="
                                                background:#eef4ff;
                                                color:#3538cd;
                                            "
                                        >
                                            Lote
                                        </span>

                                        <strong style="margin-left:5px;">
                                            {{ $item->batch_code }}
                                        </strong>

                                        @if ($item->batch_name)
                                            <span class="table-secondary">
                                                · {{ $item->batch_name }}
                                            </span>
                                        @endif

                                        @if ($item->batch_received_at)
                                            <div class="table-secondary">
                                                Recepção:
                                                {{ $item->batch_received_at->format('d/m/Y') }}
                                            </div>
                                        @endif

                                        @if ($item->batch_expiry_date)
                                            <div class="table-secondary">
                                                Validade:
                                                {{ $item->batch_expiry_date->format('d/m/Y') }}
                                            </div>
                                        @endif

                                        @if ($item->stockBatch)
                                            <div class="table-secondary">
                                                Lote registado no stock
                                                #{{ $item->stockBatch->id }}
                                            </div>
                                        @endif

                                    </div>

                                @endif

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

                                {{ $item->product?->unit?->symbol }}
                            </td>

                            <td>
                                {{ number_format(
                                    $item->unit_cost,
                                    2,
                                    ',',
                                    '.'
                                ) }}
                                MT
                            </td>

                            <td>
                                {{ rtrim(
                                    rtrim(
                                        number_format(
                                            (float) $item->tax_rate,
                                            2,
                                            ',',
                                            '.'
                                        ),
                                        '0'
                                    ),
                                    ','
                                ) }}
                                %
                            </td>

                            <td>
                                {{ number_format(
                                    $item->subtotal,
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

                            @if ($purchase->status === 'draft')

                                <td>

                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'purchases.items.destroy',
                                            [
                                                $purchase,
                                                $item,
                                            ]
                                        ) }}"
                                        onsubmit="
                                            return confirm(
                                                'Remover este produto?'
                                            );
                                        "
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="topbar-button"
                                        >
                                            Remover
                                        </button>

                                    </form>

                                </td>

                            @endif

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="7"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem produtos nesta compra.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div
            style="
                margin-top:25px;
                margin-left:auto;
                max-width:360px;
            "
        >

            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    padding:8px 0;
                    color:var(--muted);
                "
            >
                <span>Subtotal</span>

                <span>
                    {{ number_format(
                        $purchase->subtotal,
                        2,
                        ',',
                        '.'
                    ) }}
                    MT
                </span>
            </div>


            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    padding:8px 0;
                    color:var(--muted);
                "
            >
                <span>IVA</span>

                <span>
                    {{ number_format(
                        $purchase->tax_total,
                        2,
                        ',',
                        '.'
                    ) }}
                    MT
                </span>
            </div>


            <div
                style="
                    display:flex;
                    justify-content:space-between;
                    padding:14px 0 0;
                    margin-top:6px;
                    border-top:1px solid var(--border);
                    font-size:20px;
                    font-weight:800;
                "
            >
                <span>Total</span>

                <span>
                    {{ number_format(
                        $purchase->total,
                        2,
                        ',',
                        '.'
                    ) }}
                    MT
                </span>
            </div>

        </div>

    </div>
</div>

@endsection


@section('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const productSelect =
            document.getElementById(
                'purchase-product'
            );

        const batchFields =
            document.getElementById(
                'purchase-batch-fields'
            );

        const batchCode =
            document.getElementById(
                'batch-code'
            );


        if (
            ! productSelect
            || ! batchFields
        ) {
            return;
        }


        function updateBatchFields() {

            const option =
                productSelect.options[
                    productSelect.selectedIndex
                ];

            const inventoryMode =
                option
                    ? option.dataset.inventoryMode
                    : null;

            const trackStock =
                option
                    ? option.dataset.trackStock
                    : null;

            const isBatch =
                trackStock === '1'
                && inventoryMode === 'batch';


            batchFields.style.display =
                isBatch
                    ? 'block'
                    : 'none';


            if (batchCode) {
                batchCode.required = isBatch;
            }

        }


        productSelect.addEventListener(
            'change',
            updateBatchFields
        );


        updateBatchFields();


        /*
        |--------------------------------------------------------------------------
        | Erros de validação do lote
        |--------------------------------------------------------------------------
        |
        | Se o servidor devolveu um erro num campo do lote,
        | garantimos que a área fica visível.
        |
        */

        const batchError =
            batchFields.querySelector(
                '.error-message'
            );

        if (batchError) {

            batchFields.style.display =
                'block';

            if (batchCode) {
                batchCode.required = true;
            }
        }

    }
);

</script>

@endsection
