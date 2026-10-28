@extends('layouts.app')

@section('title', 'Entrada de Stock | DICO')
@section('topbar-title', 'Stock')

@section('content')


<div class="page-heading">

    <h1>Entrada de stock</h1>

    <p>
        Adicione mercadoria à loja
        <strong>
            {{ $activeStore?->name }}
        </strong>.
    </p>

</div>


<div
    class="panel"
    style="max-width:760px;"
>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('stocks.store') }}"
        >

            @csrf


            {{-- ============================================================
                 PRODUTO
            ============================================================= --}}

            <div class="form-group">

                <label class="form-label">
                    Produto
                </label>


                <select
                    name="product_id"
                    id="product_id"
                    class="form-control"
                    required
                >

                    <option value="">
                        Seleccionar produto
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

                            data-inventory-mode="{{
                                $product->inventory_mode
                            }}"

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

                                — SKU:
                                {{ $product->sku }}

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
                    id="inventory-mode-info"
                    style="
                        margin-top:7px;
                        color:var(--muted);
                        font-size:11px;
                        line-height:1.5;
                    "
                >
                    Seleccione o produto para continuar.
                </div>

            </div>



            {{-- ============================================================
                 CAMPOS DO LOTE
            ============================================================= --}}

            <div
                id="batch-fields"
                style="display:none;"
            >

                <div
                    style="
                        padding:16px;
                        margin-bottom:20px;
                        border:1px solid var(--border);
                        border-radius:12px;
                        background:var(--primary-soft);
                    "
                >

                    <strong>
                        Stock por lotes
                    </strong>

                    <div
                        style="
                            margin-top:5px;
                            font-size:12px;
                            color:var(--muted);
                            line-height:1.5;
                        "
                    >
                        Esta entrada será identificada
                        individualmente e o seu saldo poderá
                        ser acompanhado separadamente.
                    </div>

                </div>


                <div
                    style="
                        display:grid;
                        grid-template-columns:
                            repeat(
                                auto-fit,
                                minmax(220px,1fr)
                            );
                        gap:14px;
                    "
                >


                    {{-- CÓDIGO --}}

                    <div class="form-group">

                        <label class="form-label">
                            Código do lote *
                        </label>

                        <input
                            type="text"
                            name="batch_code"
                            id="batch_code"
                            value="{{ old('batch_code') }}"
                            class="form-control"
                            maxlength="100"
                            placeholder="Ex.: TR-SEP-2026"
                        >

                        @error('batch_code')

                            <div class="error-message">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>



                    {{-- NOME --}}

                    <div class="form-group">

                        <label class="form-label">
                            Nome / descrição
                        </label>

                        <input
                            type="text"
                            name="batch_name"
                            value="{{ old('batch_name') }}"
                            class="form-control"
                            maxlength="200"
                            placeholder="Ex.: Viagem Turquia Setembro"
                        >

                        @error('batch_name')

                            <div class="error-message">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>



                    {{-- FORNECEDOR --}}

                    <div class="form-group">

                        <label class="form-label">
                            Fornecedor
                        </label>

                        <select
                            name="supplier_id"
                            class="form-control"
                        >

                            <option value="">
                                Sem fornecedor
                            </option>

                            @foreach ($suppliers as $supplier)

                                <option
                                    value="{{ $supplier->id }}"
                                    @selected(
                                        old('supplier_id')
                                        == $supplier->id
                                    )
                                >
                                    {{ $supplier->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('supplier_id')

                            <div class="error-message">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>



                    {{-- CUSTO --}}

                    <div class="form-group">

                        <label class="form-label">
                            Custo unitário
                        </label>

                        <input
                            type="number"
                            name="unit_cost"
                            value="{{ old('unit_cost', 0) }}"
                            class="form-control"
                            min="0"
                            step="0.01"
                        >

                        @error('unit_cost')

                            <div class="error-message">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>



                    {{-- RECEPÇÃO --}}

                    <div class="form-group">

                        <label class="form-label">
                            Data de recepção
                        </label>

                        <input
                            type="date"
                            name="received_at"
                            value="{{
                                old(
                                    'received_at',
                                    now()->format('Y-m-d')
                                )
                            }}"
                            class="form-control"
                        >

                        @error('received_at')

                            <div class="error-message">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>



                    {{-- VALIDADE --}}

                    @if ($hasExpiryDates)

                        <div class="form-group">

                            <label class="form-label">
                                Data de validade
                            </label>

                            <input
                                type="date"
                                name="expiry_date"
                                value="{{ old('expiry_date') }}"
                                class="form-control"
                            >

                            @error('expiry_date')

                                <div class="error-message">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    @endif

                </div>

            </div>



            {{-- ============================================================
                 QUANTIDADE
            ============================================================= --}}

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



            {{-- ============================================================
                 OBSERVAÇÃO
            ============================================================= --}}

            <div class="form-group">

                <label class="form-label">
                    Observação
                </label>

                <textarea
                    name="notes"
                    class="form-control"
                    style="
                        min-height:110px;
                        padding-top:14px;
                    "
                    placeholder="Ex.: Stock inicial, mercadoria recebida..."
                >{{ old('notes') }}</textarea>

                @error('notes')

                    <div class="error-message">
                        {{ $message }}
                    </div>

                @enderror

            </div>



            {{-- ============================================================
                 BOTÕES
            ============================================================= --}}

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
                    Registar entrada
                </button>

            </div>

        </form>

    </div>

</div>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const productSelect =
            document.getElementById(
                'product_id'
            );

        const batchFields =
            document.getElementById(
                'batch-fields'
            );

        const batchCode =
            document.getElementById(
                'batch_code'
            );

        const inventoryInfo =
            document.getElementById(
                'inventory-mode-info'
            );


        function updateInventoryFields() {

            const selectedOption =
                productSelect.options[
                    productSelect.selectedIndex
                ];


            const inventoryMode =
                selectedOption
                    ?.dataset
                    ?.inventoryMode
                || null;


            /*
            |--------------------------------------------------------------------------
            | BATCH
            |--------------------------------------------------------------------------
            */

            if (inventoryMode === 'batch') {

                batchFields.style.display =
                    'block';

                batchCode.required = true;

                inventoryInfo.textContent =
                    'Este produto utiliza stock por lotes. '
                    + 'A entrada será associada a um lote específico.';

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | SIMPLE
            |--------------------------------------------------------------------------
            */

            batchFields.style.display =
                'none';

            batchCode.required = false;


            if (inventoryMode === 'simple') {

                inventoryInfo.textContent =
                    'Este produto utiliza stock simples.';

            } else {

                inventoryInfo.textContent =
                    'Seleccione o produto para continuar.';
            }
        }


        productSelect.addEventListener(
            'change',
            updateInventoryFields
        );


        /*
        |--------------------------------------------------------------------------
        | Estado inicial / erros de validação
        |--------------------------------------------------------------------------
        */

        updateInventoryFields();

    }
);

</script>

@endsection