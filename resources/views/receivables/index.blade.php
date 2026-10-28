@extends('layouts.app')

@section('title', 'Contas a Receber | DICO')
@section('topbar-title', 'Contas a Receber')

@section('content')

@php
    $statusLabels = [
        'open' => 'Em aberto',
        'overdue' => 'Vencidas',
        'paid' => 'Liquidadas',
    ];
@endphp


{{-- =========================================================================
     CABEÇALHO
============================================================================ --}}

<div class="page-heading">

    <div
        style="
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:20px;
            flex-wrap:wrap;
        "
    >

        <div>

            <h1>
                Contas a Receber
            </h1>

            <p>
                Acompanhe pagamentos pendentes da loja

                <strong>
                    {{ $activeStore?->name }}
                </strong>.
            </p>

        </div>


        <a
            href="{{ route('sales.index') }}"
            class="topbar-button"
        >
            Ver vendas
        </a>

    </div>

</div>


{{-- =========================================================================
     MÉTRICAS
============================================================================ --}}

<div class="receivables-metric-grid">

    {{-- Total a receber --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Total a receber
            </div>

            <div class="metric-icon">
                MT
            </div>

        </div>


        <div class="metric-value">

            {{ number_format(
                $totalDue,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">

            MT em
            {{ $receivablesCount }}

            {{ $receivablesCount === 1
                ? 'conta pendente'
                : 'contas pendentes'
            }}

        </div>

    </article>


    {{-- Total vencido --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Valor vencido
            </div>

            <div class="metric-icon">
                !
            </div>

        </div>


        <div
            class="metric-value"
            @if ($totalOverdue > 0)
                style="color:#b42318;"
            @endif
        >

            {{ number_format(
                $totalOverdue,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">

            MT com prazo ultrapassado

        </div>

    </article>


    {{-- Contas em aberto --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Em aberto
            </div>

            <div class="metric-icon">
                ◫
            </div>

        </div>


        <div class="metric-value">
            {{ $receivablesCount }}
        </div>


        <div class="metric-description">
            Vendas ainda por liquidar
        </div>

    </article>


    {{-- Vencidas --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Vencidas
            </div>

            <div class="metric-icon">
                !
            </div>

        </div>


        <div
            class="metric-value"
            @if ($overdueCount > 0)
                style="color:#b42318;"
            @endif
        >
            {{ $overdueCount }}
        </div>


        <div class="metric-description">
            Contas com prazo ultrapassado
        </div>

    </article>

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
            action="{{ route('receivables.index') }}"
        >

            <div class="receivables-filter-grid">

                {{-- Pesquisa --}}

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
                        placeholder="Venda, cliente, NUIT ou contacto..."
                    >

                </div>


                {{-- Estado --}}

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

                        <option
                            value="open"
                            @selected(
                                $status === 'open'
                            )
                        >
                            Em aberto
                        </option>

                        <option
                            value="overdue"
                            @selected(
                                $status === 'overdue'
                            )
                        >
                            Vencidas
                        </option>

                        <option
                            value="paid"
                            @selected(
                                $status === 'paid'
                            )
                        >
                            Liquidadas
                        </option>

                    </select>

                </div>


                {{-- Botão --}}

                <div
                    style="
                        display:flex;
                        align-items:flex-end;
                        gap:10px;
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
                        Aplicar
                    </button>


                    <a
                        href="{{ route('receivables.index') }}"
                        class="topbar-button"
                    >
                        Limpar
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================================
     TABS
============================================================================ --}}

<div class="receivable-tabs">

    <a
        href="{{ route(
            'receivables.index',
            [
                'status' => 'open',
                'search' => $search ?: null,
            ]
        ) }}"
        class="
            receivable-tab
            {{ $status === 'open'
                ? 'active'
                : ''
            }}
        "
    >
        Em aberto

        <span>
            {{ $receivablesCount }}
        </span>
    </a>


    <a
        href="{{ route(
            'receivables.index',
            [
                'status' => 'overdue',
                'search' => $search ?: null,
            ]
        ) }}"
        class="
            receivable-tab
            {{ $status === 'overdue'
                ? 'active danger'
                : ''
            }}
        "
    >
        Vencidas

        <span>
            {{ $overdueCount }}
        </span>
    </a>


    <a
        href="{{ route(
            'receivables.index',
            [
                'status' => 'paid',
                'search' => $search ?: null,
            ]
        ) }}"
        class="
            receivable-tab
            {{ $status === 'paid'
                ? 'active'
                : ''
            }}
        "
    >
        Liquidadas

        <span>
            {{ $paidCount }}
        </span>
    </a>

</div>


{{-- =========================================================================
     LISTA
============================================================================ --}}

<div class="panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">

                {{ $statusLabels[$status]
                    ?? 'Contas a Receber'
                }}

            </h2>


            <p class="panel-subtitle">

                @if ($status === 'open')

                    Valores que ainda aguardam pagamento.

                @elseif ($status === 'overdue')

                    Contas cuja data prevista de pagamento já passou.

                @else

                    Vendas que se encontram totalmente pagas.

                @endif

            </p>

        </div>

    </div>


    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Venda</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Total</th>
                        <th>Pago</th>
                        <th>Saldo</th>
                        <th>Vencimento</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $receivables
                        as $sale
                    )

                        @php
                            $isOverdue =
                                $sale->due_date
                                && (float) $sale->amount_due > 0
                                && $sale->due_date->isPast()
                                && ! $sale->due_date->isToday();
                        @endphp


                        <tr>

                            {{-- Venda --}}

                            <td>

                                <strong>
                                    {{ $sale->number }}
                                </strong>

                                <div class="table-secondary">

                                    {{ $sale->store?->name }}

                                </div>

                            </td>


                            {{-- Cliente --}}

                            <td>

                                <strong>
                                    {{ $sale->customer?->name
                                        ?? '—'
                                    }}
                                </strong>


                                @if ($sale->customer?->phone)

                                    <div class="table-secondary">

                                        {{ $sale->customer->phone }}

                                    </div>

                                @elseif ($sale->customer?->nuit)

                                    <div class="table-secondary">

                                        NUIT:
                                        {{ $sale->customer->nuit }}

                                    </div>

                                @endif

                            </td>


                            {{-- Data --}}

                            <td>

                                {{ $sale->completed_at
                                    ?->format('d/m/Y')
                                    ?? $sale->created_at
                                        ->format('d/m/Y')
                                }}

                                <div class="table-secondary">

                                    {{ $sale->completed_at
                                        ?->format('H:i')
                                        ?? $sale->created_at
                                            ->format('H:i')
                                    }}

                                </div>

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

                            </td>


                            {{-- Pago --}}

                            <td>

                                {{ number_format(
                                    $sale->paid_total,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </td>


                            {{-- Saldo --}}

                            <td>

                                @if (
                                    (float) $sale->amount_due
                                    > 0
                                )

                                    <strong
                                        style="color:#8a5c00;"
                                    >

                                        {{ number_format(
                                            $sale->amount_due,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        MT

                                    </strong>

                                @else

                                    <strong
                                        style="
                                            color:var(--primary-dark);
                                        "
                                    >
                                        0,00 MT
                                    </strong>

                                @endif

                            </td>


                            {{-- Vencimento --}}

                            <td>

                                @if (
                                    $sale->due_date
                                    && (float) $sale->amount_due > 0
                                )

                                    <strong
                                        @if ($isOverdue)
                                            style="color:#b42318;"
                                        @endif
                                    >

                                        {{ $sale->due_date
                                            ->format('d/m/Y')
                                        }}

                                    </strong>


                                    @if ($isOverdue)

                                        <div
                                            class="table-secondary"
                                            style="color:#b42318;"
                                        >
                                            Vencida
                                        </div>

                                    @elseif (
                                        $sale->due_date->isToday()
                                    )

                                        <div
                                            class="table-secondary"
                                            style="color:#8a5c00;"
                                        >
                                            Vence hoje
                                        </div>

                                    @endif

                                @else

                                    —

                                @endif

                            </td>


                            {{-- Estado --}}

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
                                            receivable-status-partial
                                        "
                                    >
                                        Pagamento parcial
                                    </span>

                                @else

                                    <span
                                        class="
                                            status-badge
                                            receivable-status-credit
                                        "
                                    >
                                        A crédito
                                    </span>

                                @endif

                            </td>


                            {{-- Acção --}}

                            <td>

                                <a
                                    href="{{ route(
                                        'receivables.show',
                                        $sale
                                    ) }}"
                                    class="topbar-button"
                                >

                                    @if (
                                        (float) $sale->amount_due
                                        > 0
                                    )

                                        Abrir

                                    @else

                                        Ver

                                    @endif

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                style="
                                    padding:55px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >

                                @if ($status === 'open')

                                    Não existem contas em aberto.

                                @elseif ($status === 'overdue')

                                    Não existem contas vencidas.

                                @else

                                    Não existem contas liquidadas.

                                @endif

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if ($receivables->hasPages())

            <div style="margin-top:22px;">

                {{ $receivables->links() }}

            </div>

        @endif

    </div>

</div>

@endsection


@section('styles')

.receivables-metric-grid {
    display:grid;
    grid-template-columns:
        repeat(4,minmax(0,1fr));
    gap:18px;
}

.receivables-filter-grid {
    display:grid;
    grid-template-columns:
        minmax(250px,1.5fr)
        minmax(180px,.7fr)
        auto;
    gap:14px;
    align-items:end;
}

.receivable-tabs {
    display:flex;
    gap:8px;
    margin:22px 0 12px;
    overflow-x:auto;
}

.receivable-tab {
    min-height:42px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    border:1px solid var(--border);
    border-radius:10px;
    padding:0 14px;
    background:#fff;
    color:var(--muted);
    font-size:12px;
    font-weight:750;
    white-space:nowrap;
}

.receivable-tab span {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:23px;
    height:23px;
    border-radius:999px;
    padding:0 7px;
    background:#f0f3f1;
    color:var(--text);
    font-size:10px;
}

.receivable-tab.active {
    border-color:var(--primary);
    background:var(--primary-soft);
    color:var(--primary-dark);
}

.receivable-tab.active span {
    background:var(--primary);
    color:#fff;
}

.receivable-tab.active.danger {
    border-color:#f2c3c3;
    background:#fff0f0;
    color:#b42318;
}

.receivable-tab.active.danger span {
    background:#b42318;
}

.receivable-status-partial {
    background:#fff7df;
    color:#8a5c00;
}

.receivable-status-credit {
    background:#fff0f0;
    color:#b42318;
}


@media (max-width:1100px) {

    .receivables-metric-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:800px) {

    .receivables-filter-grid {
        grid-template-columns:1fr;
    }

}

@media (max-width:550px) {

    .receivables-metric-grid {
        grid-template-columns:1fr;
    }

}

@endsection