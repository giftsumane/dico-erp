@extends('layouts.app')

@section('title', 'Produtos | DICO')
@section('topbar-title', 'Produtos')

@section('content')

<div class="page-heading">

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:20px;
            flex-wrap:wrap;
        "
    >

        <div>

            <h1>
                Produtos e Serviços
            </h1>

            <p>
                Consulte e gira os itens da empresa.
            </p>

        </div>


        <a
            href="{{ route('products.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:white;
                border-color:var(--primary);
            "
        >
            + Novo
        </a>

    </div>

</div>


<div class="panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Lista
            </h2>

            <p class="panel-subtitle">
                Empresa: {{ $activeCompany->name }}
            </p>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('products.index') }}"
            style="
                display:flex;
                gap:10px;
                margin-bottom:22px;
                max-width:600px;
            "
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar nome, SKU ou código de barras..."
            >

            <button
                type="submit"
                class="topbar-button"
            >
                Pesquisar
            </button>


            @if ($search)

                <a
                    href="{{ route('products.index') }}"
                    class="topbar-button"
                >
                    Limpar
                </a>

            @endif

        </form>


        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>SKU</th>
                        <th>Categoria</th>
                        <th>Marca</th>
                        <th>Unidade</th>
                        <th>Inventário</th>
                        <th>Compra</th>
                        <th>Venda</th>
                        <th>Estado</th>
                        <th>Acção</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse ($products as $product)

                        <tr>

                            <td>

                                <strong>
                                    {{ $product->name }}
                                </strong>

                                @if ($product->barcode)

                                    <div class="table-secondary">
                                        {{ $product->barcode }}
                                    </div>

                                @endif


                                @if ($product->isVariable())

                                    <div class="table-secondary">
                                        Produto principal · configuração de variantes
                                    </div>

                                @elseif ($product->isVariant())

                                    <div class="table-secondary">

                                        Variante de
                                        {{ $product->parent?->name ?? 'produto' }}

                                    </div>

                                @endif

                            </td>


                            <td>

                                @if ($product->isService())

                                    <span class="status-badge">
                                        Serviço
                                    </span>

                                @elseif ($product->isVariable())

                                    <span class="status-badge">
                                        Com variantes
                                    </span>

                                @elseif ($product->isVariant())

                                    <span class="status-badge">
                                        Variante
                                    </span>

                                @elseif ($product->isComposite())

                                    <span class="status-badge">
                                        Composto
                                    </span>

                                @else

                                    <span class="status-badge">
                                        Produto
                                    </span>

                                @endif

                            </td>


                            <td>
                                {{ $product->sku ?? '—' }}
                            </td>


                            <td>
                                {{ $product->category?->name ?? '—' }}
                            </td>


                            <td>
                                {{ $product->brand?->name ?? '—' }}
                            </td>


                            <td>
                                {{ $product->unit?->symbol ?? '—' }}
                            </td>


                            <td>

                                @if (
                                    $product->isService()
                                    || $product->isVariable()
                                )

                                    <span class="table-secondary">
                                        Sem stock
                                    </span>

                                @elseif (
                                    $product->inventory_mode
                                    === \App\Models\Product::INVENTORY_BATCH
                                )

                                    <span class="status-badge">
                                        Por lotes
                                    </span>

                                @elseif (
                                    $product->inventory_mode
                                    === \App\Models\Product::INVENTORY_SIMPLE
                                )

                                    <span class="status-badge">
                                        Simples
                                    </span>

                                @else

                                    <span class="table-secondary">
                                        Sem stock
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if (
                                    $product->isService()
                                    || $product->isVariable()
                                )

                                    —

                                @else

                                    {{ number_format(
                                        $product->purchase_price,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                    MT

                                @endif

                            </td>


                            <td>

                                @if ($product->isVariable())

                                    <span class="table-secondary">
                                        Preço base
                                    </span>

                                    <div>
                                        <strong>
                                            {{ number_format(
                                                $product->sale_price,
                                                2,
                                                ',',
                                                '.'
                                            ) }}
                                            MT
                                        </strong>
                                    </div>

                                @else

                                    <strong>

                                        {{ number_format(
                                            $product->sale_price,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                        MT

                                    </strong>

                                @endif

                            </td>


                            <td>

                                @if ($product->active)

                                    <span class="status-badge">
                                        Activo
                                    </span>

                                @else

                                    <span
                                        class="
                                            status-badge
                                            status-inactive
                                        "
                                    >
                                        Inactivo
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if ($product->isVariant())

                                    <span class="table-secondary">
                                        Gerida pelo produto principal
                                    </span>

                                @else

                                    <a
                                        href="{{ route(
                                            'products.edit',
                                            $product
                                        ) }}"
                                        class="topbar-button"
                                    >
                                        {{ $product->isVariable()
                                            ? 'Configurar'
                                            : 'Editar' }}
                                    </a>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="11"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem produtos ou serviços.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div style="margin-top:20px;">
            {{ $products->links() }}
        </div>

    </div>

</div>

@endsection
