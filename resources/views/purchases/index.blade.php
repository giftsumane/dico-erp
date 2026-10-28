@extends('layouts.app')

@section('title', 'Compras | DICO')
@section('topbar-title', 'Compras')

@section('content')

<div class="page-heading">
    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
        flex-wrap:wrap;
    ">
        <div>
            <h1>Compras</h1>
            <p>
                Compras e entradas de mercadoria da loja
                <strong>{{ $activeStore?->name }}</strong>.
            </p>
        </div>

        <a
            href="{{ route('purchases.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:#fff;
                border-color:var(--primary);
            "
        >
            + Nova compra
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('purchases.index') }}"
            style="
                display:flex;
                gap:10px;
                max-width:650px;
                margin-bottom:22px;
            "
        >
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar compra ou fornecedor..."
            >

            <button
                type="submit"
                class="topbar-button"
            >
                Pesquisar
            </button>

            @if ($search)
                <a
                    href="{{ route('purchases.index') }}"
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
                        <th>Número</th>
                        <th>Data</th>
                        <th>Fornecedor</th>
                        <th>Referência</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($purchases as $purchase)
                        <tr>
                            <td>
                                <strong>
                                    {{ $purchase->number }}
                                </strong>
                            </td>

                            <td>
                                {{ $purchase->purchase_date?->format('d/m/Y') }}
                            </td>

                            <td>
                                {{ $purchase->supplier?->name ?? 'Sem fornecedor' }}
                            </td>

                            <td>
                                {{ $purchase->supplier_reference ?? '—' }}
                            </td>

                            <td>
                                <strong>
                                    {{ number_format(
                                        $purchase->total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                    MT
                                </strong>
                            </td>

                            <td>
                                @if ($purchase->status === 'confirmed')
                                    <span class="status-badge">
                                        Confirmada
                                    </span>
                                @elseif ($purchase->status === 'draft')
                                    <span
                                        class="status-badge"
                                        style="
                                            background:#fff7df;
                                            color:#8a5c00;
                                        "
                                    >
                                        Rascunho
                                    </span>
                                @else
                                    <span class="status-badge status-inactive">
                                        Cancelada
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ route(
                                        'purchases.show',
                                        $purchase
                                    ) }}"
                                    class="topbar-button"
                                >
                                    Abrir
                                </a>
                            </td>
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
                                Ainda não existem compras.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $purchases->links() }}
        </div>

    </div>
</div>

@endsection