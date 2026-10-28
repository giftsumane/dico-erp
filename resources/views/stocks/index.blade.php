@extends('layouts.app')

@section('title', 'Stock | DICO')
@section('topbar-title', 'Stock')

@section('content')

<div class="page-heading">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        flex-wrap:wrap;
        gap:20px;
    ">

        <div>
            <h1>Stock</h1>

            <p>
                Loja:
                <strong>{{ $activeStore?->name }}</strong>
            </p>
        </div>

        <div style="display:flex;gap:10px;">

            <a
                href="{{ route('stocks.movements') }}"
                class="topbar-button"
            >
                Movimentos
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
     ERROS DE STOCK
============================================================= --}}

@if ($errors->has('stock'))

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
        {{ $errors->first('stock') }}
    </div>

@endif


<div class="panel">

    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('stocks.index') }}"
            style="
                display:flex;
                gap:10px;
                max-width:600px;
                margin-bottom:22px;
            "
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar produto, variante, SKU..."
            >

            <button
                class="topbar-button"
                type="submit"
            >
                Pesquisar
            </button>

        </form>


        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Produto</th>
                        <th>SKU</th>
                        <th>Categoria</th>
                        <th>Quantidade</th>
                        <th>Unidade</th>
                        <th>Stock mínimo</th>
                        <th>Situação</th>
                        <th>Acção</th>
                    </tr>

                </thead>

                <tbody>

                    @forelse ($stocks as $stock)

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


                        <tr>

                            {{-- ====================================================
                                 PRODUTO
                            ===================================================== --}}

                            <td>

                                <strong>
                                    {{ $product?->name ?? 'Produto removido' }}
                                </strong>


                                @if ($product?->isVariant())

                                    <div
                                        style="
                                            display:flex;
                                            gap:7px;
                                            flex-wrap:wrap;
                                            align-items:center;
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


                                        @if ($variantLabel)

                                            <span class="table-secondary">
                                                {{ $variantLabel }}
                                            </span>

                                        @endif

                                    </div>

                                @endif

                            </td>


                            {{-- ====================================================
                                 SKU
                            ===================================================== --}}

                            <td>
                                {{ $product?->sku ?? '—' }}
                            </td>


                            {{-- ====================================================
                                 CATEGORIA
                            ===================================================== --}}

                            <td>
                                {{ $product?->category?->name ?? '—' }}
                            </td>


                            {{-- ====================================================
                                 QUANTIDADE
                            ===================================================== --}}

                            <td>

                                <strong>

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

                                </strong>

                            </td>


                            {{-- ====================================================
                                 UNIDADE
                            ===================================================== --}}

                            <td>
                                {{ $product?->unit?->symbol ?? '—' }}
                            </td>


                            {{-- ====================================================
                                 STOCK MÍNIMO
                            ===================================================== --}}

                            <td>

                                {{ rtrim(
                                    rtrim(
                                        number_format(
                                            (float) (
                                                $product?->minimum_stock ?? 0
                                            ),
                                            3,
                                            ',',
                                            '.'
                                        ),
                                        '0'
                                    ),
                                    ','
                                ) }}

                            </td>


                            {{-- ====================================================
                                 SITUAÇÃO
                            ===================================================== --}}

                            <td>

                                @if (
                                    $product
                                    && $stock->quantity
                                        <= $product->minimum_stock
                                )

                                    <span
                                        class="status-badge"
                                        style="
                                            background:#fff0f0;
                                            color:#b42318;
                                        "
                                    >
                                        Stock baixo
                                    </span>

                                @else

                                    <span class="status-badge">
                                        Normal
                                    </span>

                                @endif

                            </td>


                            {{-- ====================================================
                                 ACÇÃO
                            ===================================================== --}}

                            <td>

                                @if (! $product)

                                    <span class="table-secondary">
                                        Indisponível
                                    </span>


                                    @elseif (
                                            $product->inventory_mode
                                            === \App\Models\Product::INVENTORY_BATCH
                                        )

                                            <a
                                                href="{{ route(
                                                    'stocks.batches.index',
                                                    $stock
                                                ) }}"
                                                class="topbar-button"
                                            >
                                                Ver lotes
                                            </a>


                                @else

                                    <a
                                        href="{{ route(
                                            'stocks.edit',
                                            $stock
                                        ) }}"
                                        class="topbar-button"
                                    >
                                        Ajustar stock
                                    </a>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existe stock nesta loja.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div style="margin-top:20px;">
            {{ $stocks->links() }}
        </div>

    </div>

</div>

@endsection