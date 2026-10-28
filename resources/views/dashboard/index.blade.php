@extends('layouts.app')

@section('title', 'Dashboard | DICO')
@section('topbar-title', 'Dashboard')

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

    $isAdmin = in_array(
        $activeRole,
        [
            'owner',
            'admin',
        ],
        true
    );

    $isManager = in_array(
        $activeRole,
        [
            'owner',
            'admin',
            'manager',
        ],
        true
    );

    $isCashier =
        $activeRole === 'cashier';
@endphp


{{-- =========================================================================
     CABEÇALHO
============================================================================ --}}

<div class="page-heading">

    <h1>
        Dashboard
    </h1>

    <p>

        {{ $activeCompany->name }}

        @if ($activeStore)

            ·
            {{ $activeStore->name }}

        @endif

    </p>

</div>


{{-- =========================================================================
     ALERTA GLOBAL - VENCIDAS
============================================================================ --}}

@if ($metrics['overdue_count'] > 0)

    <div class="dashboard-overdue-alert">

        <div class="dashboard-overdue-icon">
            !
        </div>


        <div class="dashboard-overdue-content">

            <strong>
                Existem pagamentos vencidos
            </strong>

            <span>

                {{ $metrics['overdue_count'] }}

                {{ $metrics['overdue_count'] == 1
                    ? 'conta está vencida'
                    : 'contas estão vencidas'
                }}

                ·

                {{ number_format(
                    $metrics['overdue_total'],
                    2,
                    ',',
                    '.'
                ) }}

                MT por receber.

            </span>

        </div>


        <a
            href="{{ route(
                'receivables.index',
                [
                    'status' => 'overdue',
                ]
            ) }}"
            class="topbar-button dashboard-overdue-button"
        >
            Ver vencidas
        </a>

    </div>

@endif


{{-- =========================================================================
     VENCEM HOJE
============================================================================ --}}

@if ($metrics['due_today_count'] > 0)

    <div class="dashboard-due-today">

        <div>

            <strong>
                Vencem hoje:
            </strong>

            {{ $metrics['due_today_count'] }}

            {{ $metrics['due_today_count'] == 1
                ? 'conta'
                : 'contas'
            }}

            ·

            <strong>

                {{ number_format(
                    $metrics['due_today_total'],
                    2,
                    ',',
                    '.'
                ) }}

                MT

            </strong>

        </div>


        <a
            href="{{ route(
                'receivables.index',
                [
                    'status' => 'open',
                ]
            ) }}"
        >
            Consultar
        </a>

    </div>

@endif


{{-- =========================================================================
     CASHIER
============================================================================ --}}

@if ($isCashier)

    <section class="metric-grid">

        {{-- Caixa --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Caixa
                </div>

                <div class="metric-icon">
                    ▦
                </div>

            </div>


            <div class="metric-value">

                {{ $currentRegister
                    ? 'Aberto'
                    : 'Fechado'
                }}

            </div>


            <div class="metric-description">

                @if ($currentRegister)

                    Desde

                    {{ $currentRegister
                        ->opened_at
                        ?->format('H:i')
                    }}

                @else

                    Abra o caixa para começar a vender

                @endif

            </div>

        </article>


        {{-- Vendas --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Vendas do turno
                </div>

                <div class="metric-icon">
                    ▣
                </div>

            </div>


            <div class="metric-value">
                {{ $metrics['cash_sales_count'] }}
            </div>


            <div class="metric-description">
                Vendas concluídas neste caixa
            </div>

        </article>


        {{-- Cash --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Cash recebido
                </div>

                <div class="metric-icon">
                    MT
                </div>

            </div>


            <div class="metric-value">

                {{ number_format(
                    $metrics['cash_sales'],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">
                MT recebidos neste caixa
            </div>

        </article>


        {{-- Saldo --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Saldo esperado
                </div>

                <div class="metric-icon">
                    =
                </div>

            </div>


            <div class="metric-value">

                {{ number_format(
                    $metrics[
                        'cash_expected_balance'
                    ],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">
                MT no caixa neste momento
            </div>

        </article>

    </section>


    {{-- Atalhos --}}

    <div
        class="panel"
        style="margin-top:22px;"
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Operação do turno
                </h2>

                <p class="panel-subtitle">
                    Atalhos para o trabalho diário.
                </p>

            </div>

        </div>


        <div class="panel-body">

            <div class="quick-actions">

                <a
                    href="{{ route('pos.index') }}"
                    class="quick-action"
                >

                    <div class="quick-action-icon">
                        ▣
                    </div>

                    <div>

                        <div class="quick-action-title">
                            Nova venda
                        </div>

                        <div class="quick-action-description">
                            Abrir o ponto de venda
                        </div>

                    </div>

                </a>


                <a
                    href="{{ route(
                        'cash-registers.index'
                    ) }}"
                    class="quick-action"
                >

                    <div class="quick-action-icon">
                        ▦
                    </div>

                    <div>

                        <div class="quick-action-title">
                            Caixa
                        </div>

                        <div class="quick-action-description">
                            Consultar ou fechar o turno
                        </div>

                    </div>

                </a>


                <a
                    href="{{ route(
                        'receivables.index'
                    ) }}"
                    class="quick-action"
                >

                    <div class="quick-action-icon">
                        ◫
                    </div>

                    <div>

                        <div class="quick-action-title">
                            Contas a Receber
                        </div>

                        <div class="quick-action-description">
                            Receber pagamentos pendentes
                        </div>

                    </div>

                </a>

            </div>

        </div>

    </div>

@endif


{{-- =========================================================================
     MANAGER / ADMIN / OWNER
============================================================================ --}}

@if ($isManager)

    <section class="metric-grid">

        {{-- Vendas --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Vendas hoje
                </div>

                <div class="metric-icon">
                    ▣
                </div>

            </div>


            <div class="metric-value">
                {{ $metrics['sales_today'] }}
            </div>


            <div class="metric-description">
                Vendas concluídas
            </div>

        </article>


        {{-- Vendido --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Vendido hoje
                </div>

                <div class="metric-icon">
                    MT
                </div>

            </div>


            <div class="metric-value">

                {{ number_format(
                    $metrics['revenue_today'],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">
                MT em vendas
            </div>

        </article>


        {{-- Recebido --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Recebido hoje
                </div>

                <div class="metric-icon">
                    ↓
                </div>

            </div>


            <div class="metric-value">

                {{ number_format(
                    $metrics[
                        'payments_received_today'
                    ],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">

                MT ·

                {{ $metrics[
                    'payments_received_today_count'
                ] }}

                {{ $metrics[
                    'payments_received_today_count'
                ] == 1
                    ? 'pagamento'
                    : 'pagamentos'
                }}

            </div>

        </article>


        {{-- Ticket --}}

        <article class="metric-card">

            <div class="metric-card-header">

                <div class="metric-label">
                    Ticket médio
                </div>

                <div class="metric-icon">
                    Ø
                </div>

            </div>


            <div class="metric-value">

                {{ number_format(
                    $metrics['average_ticket'],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">
                MT por venda
            </div>

        </article>

    </section>


    {{-- Segunda linha --}}

    <section
        class="metric-grid"
        style="margin-top:18px;"
    >

        {{-- A receber --}}

        <article class="metric-card">

            <div class="metric-label">
                Por receber
            </div>


            <div
                class="metric-value"
                style="color:#8a5c00;"
            >

                {{ number_format(
                    $metrics[
                        'receivables_total'
                    ],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">

                MT ·

                {{ $metrics[
                    'receivables_count'
                ] }}

                {{ $metrics[
                    'receivables_count'
                ] == 1
                    ? 'conta'
                    : 'contas'
                }}

            </div>

        </article>


        {{-- Vencido --}}

        <article class="metric-card">

            <div class="metric-label">
                Vencido
            </div>


            <div
                class="metric-value"
                @if (
                    $metrics['overdue_total'] > 0
                )
                    style="color:#b42318;"
                @endif
            >

                {{ number_format(
                    $metrics[
                        'overdue_total'
                    ],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">

                MT ·

                {{ $metrics[
                    'overdue_count'
                ] }}

                {{ $metrics[
                    'overdue_count'
                ] == 1
                    ? 'conta vencida'
                    : 'contas vencidas'
                }}

            </div>

        </article>


        {{-- Vence hoje --}}

        <article class="metric-card">

            <div class="metric-label">
                Vence hoje
            </div>


            <div
                class="metric-value"
                @if (
                    $metrics['due_today_total'] > 0
                )
                    style="color:#8a5c00;"
                @endif
            >

                {{ number_format(
                    $metrics[
                        'due_today_total'
                    ],
                    2,
                    ',',
                    '.'
                ) }}

            </div>


            <div class="metric-description">

                MT ·

                {{ $metrics[
                    'due_today_count'
                ] }}

                {{ $metrics[
                    'due_today_count'
                ] == 1
                    ? 'conta'
                    : 'contas'
                }}

            </div>

        </article>


        {{-- Stock --}}

        <article class="metric-card">

            <div class="metric-label">
                Stock baixo
            </div>


            <div class="metric-value">

                {{ $metrics['low_stock'] }}

            </div>


            <div class="metric-description">
                Produtos abaixo do mínimo
            </div>

        </article>

    </section>

@endif


{{-- =========================================================================
     OWNER / ADMIN
============================================================================ --}}

@if ($isAdmin)

    <div
        class="dashboard-admin-summary"
        style="margin-top:22px;"
    >

        <div>

            <span class="dashboard-admin-label">
                Empresas
            </span>

            <strong class="dashboard-admin-value">
                {{ $metrics['companies'] }}
            </strong>

            <span class="dashboard-admin-description">
                Empresas acessíveis
            </span>

        </div>


        <div class="dashboard-admin-divider"></div>


        <div>

            <span class="dashboard-admin-label">
                Lojas
            </span>

            <strong class="dashboard-admin-value">
                {{ $metrics['stores'] }}
            </strong>

            <span class="dashboard-admin-description">
                Lojas activas
            </span>

        </div>


        <div class="dashboard-admin-divider"></div>


        <div>

            <span class="dashboard-admin-label">
                Utilizadores
            </span>

            <strong class="dashboard-admin-value">
                {{ $metrics['users'] }}
            </strong>

            <span class="dashboard-admin-description">
                Utilizadores activos
            </span>

        </div>

    </div>

@endif


{{-- =========================================================================
     CAIXA ACTUAL
============================================================================ --}}

@if (! $isCashier)

    <div
        class="panel"
        style="margin-top:22px;"
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Meu caixa
                </h2>

                <p class="panel-subtitle">
                    Estado do teu turno nesta loja.
                </p>

            </div>


            <a
                href="{{ route(
                    'cash-registers.index'
                ) }}"
                class="topbar-button"
            >
                Ver caixa
            </a>

        </div>


        <div class="panel-body">

            @if ($currentRegister)

                <div class="dashboard-register-summary">

                    <div>

                        <span class="status-badge">
                            Caixa aberto
                        </span>

                        <div class="dashboard-register-date">

                            Desde

                            {{ $currentRegister
                                ->opened_at
                                ?->format('d/m/Y H:i')
                            }}

                        </div>

                    </div>


                    <div class="dashboard-register-balance">

                        <div>

                            {{ number_format(
                                $metrics[
                                    'cash_expected_balance'
                                ],
                                2,
                                ',',
                                '.'
                            ) }}

                            MT

                        </div>

                        <span>
                            Saldo esperado
                        </span>

                    </div>

                </div>

            @else

                <div class="empty-state">

                    Não tens um caixa aberto nesta loja.

                </div>

            @endif

        </div>

    </div>

@endif


{{-- =========================================================================
     DÍVIDAS VENCIDAS
============================================================================ --}}

@if (
    $metrics['overdue_count'] > 0
    && $overdueSales->isNotEmpty()
)

    <div
        class="panel"
        style="margin-top:22px;"
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Pagamentos vencidos
                </h2>

                <p class="panel-subtitle">
                    Contas que precisam de atenção.
                </p>

            </div>


            <a
                href="{{ route(
                    'receivables.index',
                    [
                        'status' => 'overdue',
                    ]
                ) }}"
                class="topbar-button"
            >
                Ver todas
            </a>

        </div>


        <div class="panel-body">

            @foreach (
                $overdueSales
                as $sale
            )

                @php
                    $daysOverdue =
                        $sale->due_date
                            ? $sale
                                ->due_date
                                ->startOfDay()
                                ->diffInDays(
                                    today()
                                )
                            : 0;
                @endphp


                <div class="dashboard-debt-row">

                    <div>

                        <strong>
                            {{ $sale->customer?->name
                                ?? 'Cliente'
                            }}
                        </strong>

                        <div class="table-secondary">

                            {{ $sale->number }}

                            · venceu em

                            {{ $sale->due_date
                                ?->format('d/m/Y')
                            }}

                        </div>

                        <div class="dashboard-debt-days">

                            {{ $daysOverdue }}

                            {{ $daysOverdue == 1
                                ? 'dia de atraso'
                                : 'dias de atraso'
                            }}

                        </div>

                    </div>


                    <div class="dashboard-debt-value">

                        <strong>

                            {{ number_format(
                                $sale->amount_due,
                                2,
                                ',',
                                '.'
                            ) }}

                            MT

                        </strong>


                        <a
                            href="{{ route(
                                'receivables.show',
                                $sale
                            ) }}"
                        >
                            Abrir
                        </a>

                    </div>

                </div>

            @endforeach

        </div>

    </div>

@endif


{{-- =========================================================================
     ÚLTIMAS VENDAS
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Últimas vendas
            </h2>

            <p class="panel-subtitle">
                Actividade recente da loja.
            </p>

        </div>


        <a
            href="{{ route('sales.index') }}"
            class="topbar-button"
        >
            Ver todas
        </a>

    </div>


    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Venda</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Operador</th>
                        <th>Pagamento</th>
                        <th>Total</th>
                        <th>Estado Pag.</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $recentSales
                        as $sale
                    )

                        @php
                            $methods =
                                $sale->payments
                                    ->pluck('method')
                                    ->unique()
                                    ->map(
                                        fn ($method) =>
                                            $paymentLabels[
                                                $method
                                            ]
                                            ?? $method
                                    )
                                    ->implode(', ');
                        @endphp


                        <tr>

                            <td>

                                <a
                                    href="{{ route(
                                        'sales.show',
                                        $sale
                                    ) }}"
                                >

                                    <strong>
                                        {{ $sale->number }}
                                    </strong>

                                </a>

                            </td>


                            <td>

                                {{ $sale
                                    ->completed_at
                                    ?->format(
                                        'd/m/Y H:i'
                                    )
                                    ?? '—'
                                }}

                            </td>


                            <td>

                                {{ $sale->customer?->name
                                    ?? 'Consumidor final'
                                }}

                            </td>


                            <td>

                                {{ $sale->user?->name
                                    ?? '—'
                                }}

                            </td>


                            <td>

                                @if (
                                    $sale
                                        ->payments
                                        ->isNotEmpty()
                                )

                                    {{ $methods }}

                                @else

                                    <span class="table-secondary">
                                        Sem pagamento
                                    </span>

                                @endif

                            </td>


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


                                @if (
                                    (float) $sale
                                        ->amount_due > 0
                                )

                                    <div
                                        class="table-secondary"
                                        style="color:#8a5c00;"
                                    >

                                        Saldo:

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

                                    <span class="dashboard-status-partial">
                                        Parcial
                                    </span>

                                @else

                                    <span class="dashboard-status-credit">
                                        A crédito
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                style="
                                    padding:45px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem vendas nesta loja.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection


@section('styles')

/* ==========================================================================
   ALERTAS
============================================================================ */

.dashboard-overdue-alert {
    display:flex;
    align-items:center;
    gap:14px;
    margin-bottom:14px;
    padding:15px 17px;
    border:1px solid #f2c3c3;
    border-radius:12px;
    background:#fff0f0;
    color:#b42318;
}

.dashboard-overdue-icon {
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    border-radius:10px;
    background:#b42318;
    color:#fff;
    font-size:18px;
    font-weight:900;
}

.dashboard-overdue-content {
    min-width:0;
    flex:1;
    display:flex;
    flex-direction:column;
    gap:3px;
}

.dashboard-overdue-content strong {
    font-size:13px;
}

.dashboard-overdue-content span {
    font-size:11px;
    line-height:1.5;
}

.dashboard-overdue-button {
    border-color:#f2c3c3;
    background:#fff;
    color:#b42318;
}

.dashboard-due-today {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:15px;
    margin-bottom:22px;
    padding:12px 15px;
    border:1px solid #f0d58d;
    border-radius:10px;
    background:#fff7df;
    color:#8a5c00;
    font-size:12px;
}

.dashboard-due-today a {
    color:#8a5c00;
    font-weight:850;
}


/* ==========================================================================
   ADMIN
============================================================================ */

.dashboard-admin-summary {
    display:flex;
    align-items:center;
    gap:32px;
    padding:20px 24px;
    border:1px solid var(--border);
    border-radius:17px;
    background:var(--surface);
    box-shadow:var(--shadow);
}

.dashboard-admin-summary
> div:not(.dashboard-admin-divider) {
    display:flex;
    flex-direction:column;
}

.dashboard-admin-label {
    color:var(--muted);
    font-size:12px;
    font-weight:700;
}

.dashboard-admin-value {
    margin-top:5px;
    font-size:23px;
}

.dashboard-admin-description {
    margin-top:4px;
    color:var(--muted);
    font-size:11px;
}

.dashboard-admin-divider {
    width:1px;
    align-self:stretch;
    background:var(--border);
}


/* ==========================================================================
   CAIXA
============================================================================ */

.dashboard-register-summary {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap;
}

.dashboard-register-date {
    margin-top:10px;
    color:var(--muted);
    font-size:13px;
}

.dashboard-register-balance {
    text-align:right;
}

.dashboard-register-balance div {
    font-size:24px;
    font-weight:850;
}

.dashboard-register-balance span {
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-size:11px;
}


/* ==========================================================================
   DÍVIDAS
============================================================================ */

.dashboard-debt-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:14px 0 14px 12px;
    border-bottom:1px solid var(--border);
    border-left:3px solid #b42318;
}

.dashboard-debt-row:first-child {
    padding-top:0;
}

.dashboard-debt-row:last-child {
    border-bottom:0;
}

.dashboard-debt-days {
    margin-top:4px;
    color:#b42318;
    font-size:10px;
    font-weight:800;
}

.dashboard-debt-value {
    flex:0 0 auto;
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:4px;
}

.dashboard-debt-value strong {
    color:#b42318;
}

.dashboard-debt-value a {
    color:var(--primary-dark);
    font-size:10px;
    font-weight:800;
}


/* ==========================================================================
   ESTADOS
============================================================================ */

.dashboard-status-partial {
    display:inline-flex;
    padding:5px 8px;
    border-radius:999px;
    background:#fff7df;
    color:#8a5c00;
    font-size:10px;
    font-weight:800;
}

.dashboard-status-credit {
    display:inline-flex;
    padding:5px 8px;
    border-radius:999px;
    background:#fff0f0;
    color:#b42318;
    font-size:10px;
    font-weight:800;
}


/* ==========================================================================
   RESPONSIVO
============================================================================ */

@media (max-width:760px) {

    .dashboard-overdue-alert {
        align-items:flex-start;
        flex-wrap:wrap;
    }

    .dashboard-overdue-button {
        margin-left:52px;
    }

    .dashboard-admin-summary {
        flex-direction:column;
        align-items:flex-start;
    }

    .dashboard-admin-divider {
        width:100%;
        height:1px;
    }

}

@media (max-width:560px) {

    .dashboard-due-today {
        align-items:flex-start;
        flex-direction:column;
    }

    .dashboard-debt-row {
        align-items:flex-start;
        flex-direction:column;
    }

    .dashboard-debt-value {
        align-items:flex-start;
    }

    .dashboard-register-summary {
        align-items:flex-start;
        flex-direction:column;
    }

    .dashboard-register-balance {
        text-align:left;
    }

}

@endsection