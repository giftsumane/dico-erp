@extends('layouts.app')

@section('title', 'Movimentos de Stock | DICO')
@section('topbar-title', 'Stock')

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
            <h1>Movimentos de stock</h1>

            <p>
                Histórico de entradas e saídas da loja
                <strong>{{ $activeStore?->name }}</strong>.
            </p>
        </div>

        <a
            href="{{ route('stocks.index') }}"
            class="topbar-button"
        >
            Voltar ao stock
        </a>
    </div>
</div>

<div class="panel">

    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Histórico
            </h2>

            <p class="panel-subtitle">
                Empresa: {{ $activeCompany->name }}
            </p>
        </div>
    </div>

    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>Origem</th>
                        <th>Antes</th>
                        <th>Quantidade</th>
                        <th>Depois</th>
                        <th>Utilizador</th>
                        <th>Observação</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($movements as $movement)

                        @php
                            $product = $movement->product;

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

                            $movementLabels = [
                                'entry' => 'Entrada',
                                'exit' => 'Saída',
                                'adjustment_in' => 'Ajuste +',
                                'adjustment_out' => 'Ajuste -',
                                'transfer_in' => 'Transferência recebida',
                                'transfer_out' => 'Transferência enviada',
                                'sale' => 'Venda',
                                'purchase' => 'Compra',
                                'return_in' => 'Devolução entrada',
                                'return_out' => 'Devolução saída',
                            ];

                            $isPurchaseReference =
                                $movement->reference_type === 'purchase'
                                && ! empty($movement->reference_id);

                            $isSaleReference =
                                $movement->reference_type === 'sale'
                                && ! empty($movement->reference_id);
                        @endphp

                        <tr>

                            <td>
                                {{ $movement->occurred_at
                                    ?->format('d/m/Y H:i') ?? '—' }}
                            </td>

                            <td>
                                <strong>
                                    {{ $product?->name ?? 'Produto removido' }}
                                </strong>

                                @if ($product?->isVariant())
                                    <div style="
                                        display:flex;
                                        flex-wrap:wrap;
                                        align-items:center;
                                        gap:6px;
                                        margin-top:5px;
                                    ">
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

                                @if ($product?->sku)
                                    <div class="table-secondary">
                                        SKU: {{ $product->sku }}
                                    </div>
                                @endif

                                @if ($movement->stockBatch)
                                    <div style="
                                        display:flex;
                                        gap:6px;
                                        align-items:center;
                                        flex-wrap:wrap;
                                        margin-top:6px;
                                    ">
                                        <span
                                            class="status-badge"
                                            style="
                                                background:#eef4ff;
                                                color:#3538cd;
                                            "
                                        >
                                            Lote
                                        </span>

                                        <strong style="font-size:11px;">
                                            {{ $movement->stockBatch->code }}
                                        </strong>

                                        @if ($movement->stockBatch->name)
                                            <span class="table-secondary">
                                                · {{ $movement->stockBatch->name }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <td>
                                <span class="status-badge">
                                    {{ $movementLabels[$movement->type]
                                        ?? $movement->type }}
                                </span>
                            </td>

                            <td>
                                @if ($isPurchaseReference)

                                    <a
                                        href="{{ route(
                                            'purchases.show',
                                            $movement->reference_id
                                        ) }}"
                                        style="
                                            font-weight:700;
                                            text-decoration:none;
                                            color:var(--primary);
                                        "
                                    >
                                        {{ $movement->reference
                                            ?: 'Compra #' . $movement->reference_id }}
                                    </a>

                                    @if ($movement->stockBatch)
                                        <div class="table-secondary">
                                            Lote:
                                            {{ $movement->stockBatch->code }}
                                        </div>
                                    @endif

                                @elseif ($isSaleReference)

                                        <a
                                            href="{{ route(
                                                'sales.show',
                                                $movement->reference_id
                                            ) }}"
                                            style="
                                                font-weight:700;
                                                text-decoration:none;
                                                color:var(--primary);
                                            "
                                        >
                                            {{ $movement->reference
                                                ?: 'Venda #' . $movement->reference_id }}
                                        </a>

                                        @if ($movement->stockBatch)
                                            <div class="table-secondary">
                                                Lote:
                                                {{ $movement->stockBatch->code }}
                                            </div>
                                        @endif


                                @elseif ($movement->reference)

                                    {{ $movement->reference }}

                                @elseif ($movement->stockBatch)

                                    Lote {{ $movement->stockBatch->code }}

                                @else
                                    —
                                @endif
                            </td>

                            <td>
                                {{ rtrim(
                                    rtrim(
                                        number_format(
                                            (float) $movement->quantity_before,
                                            3,
                                            ',',
                                            '.'
                                        ),
                                        '0'
                                    ),
                                    ','
                                ) }}
                            </td>

                            <td>
                                <strong>
                                    @if (
                                        in_array(
                                            $movement->type,
                                            [
                                                'entry',
                                                'adjustment_in',
                                                'transfer_in',
                                                'purchase',
                                                'return_in',
                                            ]
                                        )
                                    )
                                        +
                                    @else
                                        -
                                    @endif

                                    {{ rtrim(
                                        rtrim(
                                            number_format(
                                                (float) $movement->quantity,
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

                            <td>
                                <strong>
                                    {{ rtrim(
                                        rtrim(
                                            number_format(
                                                (float) $movement->quantity_after,
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

                            <td>
                                {{ $movement->user?->name ?? 'Sistema' }}
                            </td>

                            <td>
                                {{ $movement->notes ?? '—' }}
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="9"
                                style="
                                    padding:50px 20px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem movimentos de stock nesta loja.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($movements->hasPages())
            <div style="margin-top:20px;">
                {{ $movements->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
