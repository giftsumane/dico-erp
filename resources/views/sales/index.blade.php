@extends('layouts.app')

@section('title', 'Vendas | DICO')
@section('topbar-title', 'Vendas')

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
@endphp


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
                Vendas
            </h1>

            <p>
                Histórico de vendas da loja
                <strong>{{ $activeStore?->name }}</strong>.
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
                href="{{ route('receivables.index') }}"
                class="topbar-button"
            >
                Contas a Receber
            </a>

            <a
                href="{{ route('pos.index') }}"
                class="topbar-button"
                style="
                    background:var(--primary);
                    color:#fff;
                    border-color:var(--primary);
                "
            >
                + Nova venda
            </a>

        </div>

    </div>

</div>


{{-- =========================================================================
     MÉTRICAS
============================================================================ --}}

<div class="metric-grid">

    <div class="metric-card">

        <div class="metric-label">
            Vendas
        </div>

        <div class="metric-value">

            {{ number_format(
                $salesCount,
                0,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">
            Vendas concluídas
        </div>

    </div>


    <div class="metric-card">

        <div class="metric-label">
            Total vendido
        </div>

        <div class="metric-value">

            {{ number_format(
                $salesTotal,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">
            MT
        </div>

    </div>

</div>


{{-- =========================================================================
     FILTROS
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('sales.index') }}"
        >

            <div class="sales-filter-grid">

                <div
                    class="form-group"
                    style="margin:0;"
                >

                    <label class="form-label">
                        Pesquisar
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Venda ou cliente..."
                    >

                </div>


                <div
                    class="form-group"
                    style="margin:0;"
                >

                    <label class="form-label">
                        Estado
                    </label>

                    <select
                        name="status"
                        class="form-control"
                    >

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="completed"
                            @selected(
                                $status === 'completed'
                            )
                        >
                            Concluída
                        </option>

                        <option
                            value="draft"
                            @selected(
                                $status === 'draft'
                            )
                        >
                            Rascunho
                        </option>

                        <option
                            value="cancelled"
                            @selected(
                                $status === 'cancelled'
                            )
                        >
                            Cancelada
                        </option>

                    </select>

                </div>


                <div
                    class="form-group"
                    style="margin:0;"
                >

                    <label class="form-label">
                        Método
                    </label>

                    <select
                        name="payment_method"
                        class="form-control"
                    >

                        <option value="">
                            Todos
                        </option>

                        @foreach (
                            $paymentLabels
                            as $value => $label
                        )

                            <option
                                value="{{ $value }}"
                                @selected(
                                    $paymentMethod === $value
                                )
                            >
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div
                    class="form-group"
                    style="margin:0;"
                >

                    <label class="form-label">
                        De
                    </label>

                    <input
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        class="form-control"
                    >

                </div>


                <div
                    class="form-group"
                    style="margin:0;"
                >

                    <label class="form-label">
                        Até
                    </label>

                    <input
                        type="date"
                        name="date_to"
                        value="{{ $dateTo }}"
                        class="form-control"
                    >

                </div>

            </div>


            <div
                style="
                    display:flex;
                    gap:10px;
                    margin-top:15px;
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
                    Aplicar filtros
                </button>

                <a
                    href="{{ route('sales.index') }}"
                    class="topbar-button"
                >
                    Limpar
                </a>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================================
     LISTA
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Venda</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Operador</th>
                        <th>Método</th>
                        <th>Total</th>
                        <th>Estado Pag.</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>

                </thead>


                <tbody>

                    @forelse ($sales as $sale)

                        @php
                            $payment =
                                $sale->payments->first();

                            $paymentMethods =
                                $sale->payments
                                    ->pluck('method')
                                    ->unique()
                                    ->map(
                                        fn ($method) =>
                                            $paymentLabels[$method]
                                            ?? $method
                                    )
                                    ->implode(', ');
                        @endphp


                        <tr>

                            {{-- Venda --}}

                            <td>

                                <strong>
                                    {{ $sale->number }}
                                </strong>

                            </td>


                            {{-- Data --}}

                            <td>

                                {{ $sale->completed_at
                                    ?->format('d/m/Y H:i')
                                    ?? $sale->created_at
                                        ->format('d/m/Y H:i')
                                }}

                            </td>


                            {{-- Cliente --}}

                            <td>

                                {{ $sale->customer?->name
                                    ?? 'Consumidor final'
                                }}

                            </td>


                            {{-- Operador --}}

                            <td>

                                {{ $sale->user?->name
                                    ?? '—'
                                }}

                            </td>


                            {{-- Métodos --}}

                            <td>

                                @if ($sale->payments->isNotEmpty())

                                    {{ $paymentMethods }}

                                @elseif (
                                    $sale->payment_status
                                    === 'unpaid'
                                )

                                    <span class="table-secondary">
                                        Sem pagamento
                                    </span>

                                @else

                                    —

                                @endif

                            </td>


                            {{-- Total --}}

                            <td>

                                <strong>

                                    {{ number_format(
                                        $sale->total,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    MT

                                </strong>


                                @if ((float) $sale->paid_total > 0)

                                    <div class="table-secondary">

                                        Pago:

                                        {{ number_format(
                                            $sale->paid_total,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        MT

                                    </div>

                                @endif


                                @if ((float) $sale->amount_due > 0)

                                    <div
                                        class="table-secondary"
                                        style="color:#8a5c00;"
                                    >

                                        Por receber:

                                        {{ number_format(
                                            $sale->amount_due,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        MT

                                    </div>

                                @endif

                            </td>


                            {{-- Estado pagamento --}}

                            <td>

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

                                @else

                                    <span class="status-inactive">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- Estado comercial --}}

                            <td>

                                @if (
                                    $sale->status
                                    === 'completed'
                                )

                                    <span class="status-badge">
                                        Concluída
                                    </span>

                                @elseif (
                                    $sale->status
                                    === 'draft'
                                )

                                    <span
                                        class="
                                            status-badge
                                            payment-status-partial
                                        "
                                    >
                                        Rascunho
                                    </span>

                                @else

                                    <span
                                        class="
                                            status-badge
                                            status-inactive
                                        "
                                    >
                                        Cancelada
                                    </span>

                                @endif

                            </td>


                            {{-- Acção --}}

                            <td>

                                <div
                                    style="
                                        display:flex;
                                        gap:7px;
                                    "
                                >

                                    <a
                                        href="{{ route(
                                            'sales.show',
                                            $sale
                                        ) }}"
                                        class="topbar-button"
                                    >
                                        Ver
                                    </a>


                                    @if (
                                        (float) $sale->amount_due
                                        > 0
                                    )

                                        <a
                                            href="{{ route(
                                                'receivables.show',
                                                $sale
                                            ) }}"
                                            class="
                                                topbar-button
                                                payment-action-button
                                            "
                                        >
                                            Receber
                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Nenhuma venda encontrada.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div style="margin-top:20px;">

            {{ $sales->links() }}

        </div>

    </div>

</div>

@endsection


@section('styles')

.sales-filter-grid {
    display:grid;
    grid-template-columns:
        minmax(180px,1.5fr)
        minmax(130px,.8fr)
        minmax(150px,.8fr)
        minmax(140px,.8fr)
        minmax(140px,.8fr);
    gap:12px;
    align-items:end;
}

.payment-status-partial {
    background:#fff7df;
    color:#8a5c00;
}

.payment-status-unpaid {
    background:#fff0f0;
    color:#b42318;
}

.payment-action-button {
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}

@media (max-width:1100px) {

    .sales-filter-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:650px) {

    .sales-filter-grid {
        grid-template-columns:1fr;
    }

}

@endsection