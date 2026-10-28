@extends('layouts.app')

@section('title', 'Relatórios | DICO')
@section('topbar-title', 'Relatórios')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | Métodos
    |--------------------------------------------------------------------------
    */

    $paymentLabels = [
        'cash' => 'Dinheiro',
        'mpesa' => 'M-Pesa',
        'emola' => 'e-Mola',
        'card' => 'Cartão',
        'bank_transfer' => 'Transferência',
        'other' => 'Outro',
    ];

    /*
    |--------------------------------------------------------------------------
    | Maior valor diário
    |--------------------------------------------------------------------------
    */

    $maxDailyTotal = max(
        1,
        (float) $dailySales->max('total')
    );

    /*
    |--------------------------------------------------------------------------
    | Percentagem vencida
    |--------------------------------------------------------------------------
    */

    $overduePercentage =
        $receivablesTotal > 0
            ? (
                $overdueTotal
                / $receivablesTotal
            ) * 100
            : 0;
@endphp


{{-- =========================================================================
     CABEÇALHO
============================================================================ --}}

<div class="page-heading">

    <div class="report-page-heading">

        <div>

            <h1>
                Relatórios
            </h1>

            <p>
                Visão comercial e financeira da loja

                <strong>
                    {{ $activeStore?->name
                        ?? 'Sem loja seleccionada'
                    }}
                </strong>.
            </p>

        </div>


        <div class="report-heading-actions">

            <a
                href="{{ route('receivables.index') }}"
                class="topbar-button"
            >
                Contas a Receber
            </a>

            <a
                href="{{ route('sales.index') }}"
                class="topbar-button"
            >
                Ver vendas
            </a>

            <a
                href="{{ route('pos.index') }}"
                class="
                    topbar-button
                    report-primary-button
                "
            >
                + Nova venda
            </a>

        </div>

    </div>

</div>


{{-- =========================================================================
     ALERTA GLOBAL - DÍVIDAS VENCIDAS
============================================================================ --}}

@if ($overdueCount > 0)

    <div class="receivables-warning">

        <div class="receivables-warning-icon">
            !
        </div>


        <div class="receivables-warning-content">

            <strong>
                Existem pagamentos vencidos
            </strong>

            <span>

                {{ $overdueCount }}

                {{ $overdueCount == 1
                    ? 'conta ultrapassou'
                    : 'contas ultrapassaram'
                }}

                a data prevista de pagamento.

                Saldo vencido:

                <strong>

                    {{ number_format(
                        $overdueTotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>.

            </span>

        </div>


        <a
            href="{{ route(
                'receivables.index',
                [
                    'status' => 'overdue',
                ]
            ) }}"
            class="topbar-button receivables-warning-button"
        >
            Ver vencidas
        </a>

    </div>

@endif


{{-- =========================================================================
     ALERTA - VENCE HOJE
============================================================================ --}}

@if ($dueTodayCount > 0)

    <div class="receivables-due-today">

        <div>

            <strong>
                Vencem hoje:
            </strong>

            {{ $dueTodayCount }}

            {{ $dueTodayCount == 1
                ? 'conta'
                : 'contas'
            }}

            ·

            <strong>

                {{ number_format(
                    $dueTodayTotal,
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
     FILTRO COMERCIAL
============================================================================ --}}

<div class="panel report-filter-panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Período do relatório
            </h2>

            <p class="panel-subtitle">

                Este filtro aplica-se às vendas,
                produtos, descontos, IVA e pagamentos recebidos.

                As contas a receber mostram sempre
                a posição actual da loja.

            </p>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('reports.index') }}"
            id="report-filter-form"
        >

            <div class="report-filter-grid">

                <div class="form-group report-filter-item">

                    <label
                        for="report-period"
                        class="form-label"
                    >
                        Período
                    </label>

                    <select
                        name="period"
                        id="report-period"
                        class="form-control"
                        onchange="toggleCustomPeriod()"
                    >

                        <option
                            value="today"
                            @selected(
                                $period === 'today'
                            )
                        >
                            Hoje
                        </option>

                        <option
                            value="7d"
                            @selected(
                                $period === '7d'
                            )
                        >
                            Últimos 7 dias
                        </option>

                        <option
                            value="30d"
                            @selected(
                                $period === '30d'
                            )
                        >
                            Últimos 30 dias
                        </option>

                        <option
                            value="custom"
                            @selected(
                                $period === 'custom'
                            )
                        >
                            Personalizado
                        </option>

                    </select>

                </div>


                <div
                    class="
                        form-group
                        report-filter-item
                        custom-period-field
                    "
                >

                    <label
                        for="date-from"
                        class="form-label"
                    >
                        De
                    </label>

                    <input
                        id="date-from"
                        type="date"
                        name="date_from"
                        value="{{ request(
                            'date_from',
                            $dateFrom?->format('Y-m-d')
                        ) }}"
                        class="form-control"
                    >

                </div>


                <div
                    class="
                        form-group
                        report-filter-item
                        custom-period-field
                    "
                >

                    <label
                        for="date-to"
                        class="form-label"
                    >
                        Até
                    </label>

                    <input
                        id="date-to"
                        type="date"
                        name="date_to"
                        value="{{ request(
                            'date_to',
                            $dateTo?->format('Y-m-d')
                        ) }}"
                        class="form-control"
                    >

                </div>


                <div class="report-filter-actions">

                    <button
                        type="submit"
                        class="
                            topbar-button
                            report-primary-button
                        "
                    >
                        Aplicar
                    </button>

                </div>

            </div>


            @if ($errors->has('date_from'))

                <div class="report-filter-error">
                    {{ $errors->first('date_from') }}
                </div>

            @endif


            @if ($errors->has('date_to'))

                <div class="report-filter-error">
                    {{ $errors->first('date_to') }}
                </div>

            @endif

        </form>

    </div>

</div>


{{-- =========================================================================
     MÉTRICAS COMERCIAIS
============================================================================ --}}

<section class="metric-grid">

    {{-- VENDAS --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Vendas
            </div>

            <div class="metric-icon">
                ▣
            </div>

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

            {{ $periodLabel }}

            ·

            {{ $salesCount == 1
                ? '1 venda concluída'
                : $salesCount . ' vendas concluídas'
            }}

        </div>

    </article>


    {{-- TOTAL VENDIDO --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Total vendido
            </div>

            <div class="metric-icon">
                MT
            </div>

        </div>


        <div class="metric-value">

            {{ number_format(
                $revenue,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">
            MT · {{ $periodLabel }}
        </div>

    </article>


    {{-- RECEBIDO --}}

    <article class="metric-card">

        <div class="metric-card-header">

            <div class="metric-label">
                Valor recebido
            </div>

            <div class="metric-icon">
                ↓
            </div>

        </div>


        <div class="metric-value">

            {{ number_format(
                $paymentsReceived,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">

            MT ·

            {{ $paymentsCount }}

            {{ $paymentsCount == 1
                ? 'pagamento recebido'
                : 'pagamentos recebidos'
            }}

        </div>

    </article>


    {{-- TICKET MÉDIO --}}

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
                $averageTicket,
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


{{-- =========================================================================
     MÉTRICAS COMPLEMENTARES
============================================================================ --}}

<section
    class="metric-grid"
    style="margin-top:18px;"
>

    {{-- PRODUTOS --}}

    <article class="metric-card">

        <div class="metric-label">
            Produtos vendidos
        </div>


        <div class="metric-value">

            {{ rtrim(
                rtrim(
                    number_format(
                        (float) $productsSold,
                        3,
                        ',',
                        '.'
                    ),
                    '0'
                ),
                ','
            ) }}

        </div>


        <div class="metric-description">
            Quantidade total
        </div>

    </article>


    {{-- DESCONTOS --}}

    <article class="metric-card">

        <div class="metric-label">
            Descontos
        </div>


        <div class="metric-value">

            {{ number_format(
                $discountsTotal,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">
            MT concedidos
        </div>

    </article>


    {{-- IVA --}}

    <article class="metric-card">

        <div class="metric-label">
            IVA
        </div>


        <div class="metric-value">

            {{ number_format(
                $taxTotal,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">
            MT nas vendas do período
        </div>

    </article>


    {{-- DIFERENÇA --}}

    <article class="metric-card">

        <div class="metric-label">
            Vendido vs recebido
        </div>


        <div class="metric-value">

            {{ number_format(
                $revenue - $paymentsReceived,
                2,
                ',',
                '.'
            ) }}

        </div>


        <div class="metric-description">

            MT de diferença no período

        </div>

    </article>

</section>


{{-- =========================================================================
     CONTAS A RECEBER - POSIÇÃO ACTUAL
============================================================================ --}}

<section
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Contas a Receber
            </h2>

            <p class="panel-subtitle">

                Posição actual das dívidas pendentes da loja,
                independentemente do período seleccionado acima.

            </p>

        </div>


        <a
            href="{{ route('receivables.index') }}"
            class="topbar-button"
        >
            Abrir módulo
        </a>

    </div>


    <div class="panel-body">

        <div class="receivable-report-grid">

            {{-- TOTAL --}}

            <div class="receivable-report-card">

                <span>
                    Total por receber
                </span>

                <strong>

                    {{ number_format(
                        $receivablesTotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>

                <small>

                    {{ $receivablesCount }}

                    {{ $receivablesCount == 1
                        ? 'conta pendente'
                        : 'contas pendentes'
                    }}

                </small>

            </div>


            {{-- VENCIDO --}}

            <div
                class="
                    receivable-report-card
                    {{ $overdueCount > 0
                        ? 'receivable-report-card-danger'
                        : ''
                    }}
                "
            >

                <span>
                    Valor vencido
                </span>

                <strong>

                    {{ number_format(
                        $overdueTotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>

                <small>

                    {{ $overdueCount }}

                    {{ $overdueCount == 1
                        ? 'conta vencida'
                        : 'contas vencidas'
                    }}

                </small>

            </div>


            {{-- VENCE HOJE --}}

            <div
                class="
                    receivable-report-card
                    {{ $dueTodayCount > 0
                        ? 'receivable-report-card-warning'
                        : ''
                    }}
                "
            >

                <span>
                    Vence hoje
                </span>

                <strong>

                    {{ number_format(
                        $dueTodayTotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>

                <small>

                    {{ $dueTodayCount }}

                    {{ $dueTodayCount == 1
                        ? 'conta'
                        : 'contas'
                    }}

                </small>

            </div>


            {{-- FUTURO --}}

            <div class="receivable-report-card">

                <span>
                    A vencer
                </span>

                <strong>

                    {{ number_format(
                        $futureDueTotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>

                <small>

                    {{ $futureDueCount }}

                    {{ $futureDueCount == 1
                        ? 'conta futura'
                        : 'contas futuras'
                    }}

                </small>

            </div>

        </div>


        @if ($withoutDueDateCount > 0)

            <div class="receivable-no-date-warning">

                <strong>
                    Atenção:
                </strong>

                existem

                {{ $withoutDueDateCount }}

                {{ $withoutDueDateCount == 1
                    ? 'conta sem data de vencimento'
                    : 'contas sem data de vencimento'
                }},

                totalizando

                <strong>

                    {{ number_format(
                        $withoutDueDateTotal,
                        2,
                        ',',
                        '.'
                    ) }}

                    MT

                </strong>.

            </div>

        @endif


        {{-- PERCENTAGEM VENCIDA --}}

        <div class="receivable-health">

            <div>

                <span>
                    Percentagem do saldo já vencido
                </span>

                <strong>

                    {{ number_format(
                        $overduePercentage,
                        1,
                        ',',
                        '.'
                    ) }}

                    %

                </strong>

            </div>


            <div class="receivable-health-track">

                <div
                    class="receivable-health-bar"
                    style="
                        width:
                        {{ min(
                            100,
                            max(
                                0,
                                $overduePercentage
                            )
                        ) }}%;
                    "
                ></div>

            </div>

        </div>

    </div>

</section>


{{-- =========================================================================
     VENCIDAS + PRÓXIMOS VENCIMENTOS
============================================================================ --}}

<div class="report-main-grid">

    {{-- VENCIDAS --}}

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Pagamentos vencidos
                </h2>

                <p class="panel-subtitle">
                    Contas que ultrapassaram a data prevista.
                </p>

            </div>


            @if ($overdueCount > 0)

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

            @endif

        </div>


        <div class="panel-body">

            @forelse (
                $overdueReceivables
                as $sale
            )

                @php
                    $daysOverdue =
                        $sale->due_date
                            ? $sale->due_date
                                ->startOfDay()
                                ->diffInDays(
                                    today()
                                )
                            : 0;
                @endphp


                <div class="debt-list-row debt-list-row-danger">

                    <div>

                        <strong class="debt-list-title">

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


                        <div class="debt-overdue-days">

                            {{ $daysOverdue }}

                            {{ $daysOverdue == 1
                                ? 'dia de atraso'
                                : 'dias de atraso'
                            }}

                        </div>

                    </div>


                    <div class="debt-list-value">

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

            @empty

                <div class="empty-state">

                    Não existem pagamentos vencidos.

                </div>

            @endforelse

        </div>

    </section>


    {{-- PRÓXIMOS --}}

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Próximos vencimentos
                </h2>

                <p class="panel-subtitle">
                    Contas pendentes a partir de hoje.
                </p>

            </div>

        </div>


        <div class="panel-body">

            @forelse (
                $upcomingReceivables
                as $sale
            )

                @php
                    $isToday =
                        $sale->due_date
                        && $sale->due_date->isToday();
                @endphp


                <div
                    class="
                        debt-list-row
                        {{ $isToday
                            ? 'debt-list-row-warning'
                            : ''
                        }}
                    "
                >

                    <div>

                        <strong class="debt-list-title">

                            {{ $sale->customer?->name
                                ?? 'Cliente'
                            }}

                        </strong>


                        <div class="table-secondary">

                            {{ $sale->number }}

                            ·

                            {{ $sale->due_date
                                ?->format('d/m/Y')
                            }}

                        </div>


                        @if ($isToday)

                            <div class="debt-due-today-label">
                                Vence hoje
                            </div>

                        @endif

                    </div>


                    <div class="debt-list-value">

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

            @empty

                <div class="empty-state">

                    Não existem próximos vencimentos.

                </div>

            @endforelse

        </div>

    </section>

</div>


{{-- =========================================================================
     RESUMO DO PERÍODO
============================================================================ --}}

<div class="report-period-summary">

    <div>

        <span class="report-period-label">
            Período seleccionado
        </span>

        <strong class="report-period-value">
            {{ $periodLabel }}
        </strong>

        <span class="report-period-description">

            {{ $dateFrom?->format('d/m/Y') }}

            @if (
                $dateFrom?->format('Y-m-d')
                !== $dateTo?->format('Y-m-d')
            )

                a
                {{ $dateTo?->format('d/m/Y') }}

            @endif

        </span>

    </div>


    <div class="report-period-divider"></div>


    <div>

        <span class="report-period-label">
            Vendido
        </span>

        <strong class="report-period-value">

            {{ number_format(
                $revenue,
                2,
                ',',
                '.'
            ) }}

            <small>
                MT
            </small>

        </strong>

        <span class="report-period-description">
            Valor das vendas
        </span>

    </div>


    <div class="report-period-divider"></div>


    <div>

        <span class="report-period-label">
            Recebido
        </span>

        <strong class="report-period-value">

            {{ number_format(
                $paymentsReceived,
                2,
                ',',
                '.'
            ) }}

            <small>
                MT
            </small>

        </strong>

        <span class="report-period-description">
            Pagamentos realmente recebidos
        </span>

    </div>


    <div class="report-period-divider"></div>


    <div>

        <span class="report-period-label">
            Ticket médio
        </span>

        <strong class="report-period-value">

            {{ number_format(
                $averageTicket,
                2,
                ',',
                '.'
            ) }}

            <small>
                MT
            </small>

        </strong>

        <span class="report-period-description">
            Valor médio por venda
        </span>

    </div>

</div>


{{-- =========================================================================
     EVOLUÇÃO + PAGAMENTOS
============================================================================ --}}

<div class="report-main-grid">

    {{-- EVOLUÇÃO --}}

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Evolução das vendas
                </h2>

                <p class="panel-subtitle">
                    {{ $periodLabel }}
                </p>

            </div>

        </div>


        <div class="panel-body">

            @if ($dailySales->isNotEmpty())

                <div class="sales-chart">

                    @foreach (
                        $dailySales
                        as $day
                    )

                        @php
                            $dayTotal =
                                (float) $day['total'];

                            $percentage =
                                $maxDailyTotal > 0
                                    ? (
                                        $dayTotal
                                        / $maxDailyTotal
                                    ) * 100
                                    : 0;
                        @endphp


                        <div class="sales-chart-column">

                            <div class="sales-chart-value">

                                {{ number_format(
                                    $dayTotal,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </div>


                            <div class="sales-chart-track">

                                <div
                                    class="sales-chart-bar"
                                    style="
                                        height:
                                        {{ $dayTotal > 0
                                            ? max(
                                                5,
                                                $percentage
                                            )
                                            : 0
                                        }}%;
                                    "
                                ></div>

                            </div>


                            <div class="sales-chart-label">
                                {{ $day['label'] }}
                            </div>


                            <div class="sales-chart-count">

                                {{ $day['sales_count'] }}

                                {{ $day['sales_count'] == 1
                                    ? 'venda'
                                    : 'vendas'
                                }}

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="empty-state">

                    Não existem vendas neste período.

                </div>

            @endif

        </div>

    </section>


    {{-- MÉTODOS --}}

    <section class="panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Pagamentos recebidos
                </h2>

                <p class="panel-subtitle">
                    Por método · {{ $periodLabel }}
                </p>

            </div>

        </div>


        <div class="panel-body">

            @forelse (
                $paymentsByMethod
                as $payment
            )

                <div class="report-row">

                    <div class="report-payment-info">

                        <div class="report-payment-icon">

                            @switch(
                                $payment->method
                            )

                                @case('cash')
                                    MT
                                    @break

                                @case('mpesa')
                                    M
                                    @break

                                @case('emola')
                                    E
                                    @break

                                @case('card')
                                    C
                                    @break

                                @case('bank_transfer')
                                    B
                                    @break

                                @default
                                    •

                            @endswitch

                        </div>


                        <div>

                            <strong class="report-row-title">

                                {{ $paymentLabels[
                                    $payment->method
                                ] ?? $payment->method }}

                            </strong>


                            <div class="table-secondary">

                                {{ $payment->transactions }}

                                {{ $payment->transactions == 1
                                    ? 'transacção'
                                    : 'transacções'
                                }}

                            </div>

                        </div>

                    </div>


                    <strong class="report-row-value">

                        {{ number_format(
                            $payment->total,
                            2,
                            ',',
                            '.'
                        ) }}

                        <span>
                            MT
                        </span>

                    </strong>

                </div>

            @empty

                <div class="empty-state">

                    Não existem pagamentos neste período.

                </div>

            @endforelse

        </div>

    </section>

</div>


{{-- =========================================================================
     CLIENTES COM MAIOR DÍVIDA
============================================================================ --}}

<section
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Clientes com maior saldo pendente
            </h2>

            <p class="panel-subtitle">

                Posição actual · todas as dívidas pendentes.

            </p>

        </div>


        <a
            href="{{ route('receivables.index') }}"
            class="topbar-button"
        >
            Contas a Receber
        </a>

    </div>


    <div class="panel-body report-table-body">

        <div class="report-table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Contas</th>
                        <th>Vencimento mais próximo</th>
                        <th>Saldo</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $topDebtors
                        as $index => $debtor
                    )

                        @php
                            $nextDueDate =
                                $debtor->next_due_date
                                    ? \Carbon\Carbon::parse(
                                        $debtor->next_due_date
                                    )
                                    : null;

                            $debtorOverdue =
                                $nextDueDate
                                && $nextDueDate->isPast()
                                && ! $nextDueDate->isToday();
                        @endphp


                        <tr>

                            <td>

                                <span class="report-ranking">
                                    {{ $index + 1 }}
                                </span>

                            </td>


                            <td>

                                <strong>

                                    {{ $debtor
                                        ->customer
                                        ?->name
                                        ?? 'Cliente removido'
                                    }}

                                </strong>


                                @if (
                                    $debtor
                                        ->customer
                                        ?->phone
                                )

                                    <div class="table-secondary">

                                        {{ $debtor
                                            ->customer
                                            ->phone
                                        }}

                                    </div>

                                @endif

                            </td>


                            <td>
                                {{ $debtor->debts_count }}
                            </td>


                            <td>

                                @if ($nextDueDate)

                                    <strong
                                        @if ($debtorOverdue)
                                            style="color:#b42318;"
                                        @endif
                                    >

                                        {{ $nextDueDate
                                            ->format('d/m/Y')
                                        }}

                                    </strong>


                                    @if ($debtorOverdue)

                                        <div
                                            class="table-secondary"
                                            style="color:#b42318;"
                                        >
                                            Vencida
                                        </div>

                                    @elseif (
                                        $nextDueDate->isToday()
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


                            <td>

                                <strong
                                    style="color:#8a5c00;"
                                >

                                    {{ number_format(
                                        $debtor->total_due,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    MT

                                </strong>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="report-empty-table"
                            >

                                <strong>
                                    Não existem clientes com dívida.
                                </strong>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>


{{-- =========================================================================
     PRODUTOS MAIS VENDIDOS
============================================================================ --}}

<section
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Produtos mais vendidos
            </h2>

            <p class="panel-subtitle">
                Top 10 por quantidade · {{ $periodLabel }}
            </p>

        </div>

    </div>


    <div class="panel-body report-table-body">

        <div class="report-table-wrapper">

            <table class="data-table">

                <thead>

                    <tr>

                        <th style="width:60px;">
                            #
                        </th>

                        <th>
                            Produto
                        </th>

                        <th>
                            Quantidade
                        </th>

                        <th>
                            Receita
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $topProducts
                        as $index => $row
                    )

                        <tr>

                            <td>

                                <span class="report-ranking">
                                    {{ $index + 1 }}
                                </span>

                            </td>


                            <td>

                                <strong>

                                    {{ $row->product?->name
                                        ?? 'Produto removido'
                                    }}

                                </strong>


                                @if ($row->product?->sku)

                                    <div class="table-secondary">

                                        SKU:
                                        {{ $row->product->sku }}

                                    </div>

                                @endif

                            </td>


                            <td>

                                <strong>

                                    {{ rtrim(
                                        rtrim(
                                            number_format(
                                                (float) $row
                                                    ->total_quantity,
                                                3,
                                                ',',
                                                '.'
                                            ),
                                            '0'
                                        ),
                                        ','
                                    ) }}

                                </strong>


                                @if (
                                    $row
                                        ->product
                                        ?->unit
                                        ?->symbol
                                )

                                    <span class="table-secondary">

                                        {{ $row
                                            ->product
                                            ->unit
                                            ->symbol
                                        }}

                                    </span>

                                @endif

                            </td>


                            <td>

                                <strong>

                                    {{ number_format(
                                        $row->total_revenue,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    MT

                                </strong>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="report-empty-table"
                            >

                                <strong>
                                    Não existem vendas neste período.
                                </strong>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection


@section('styles')

/* ==========================================================================
   CABEÇALHO
============================================================================ */

.report-page-heading {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    flex-wrap:wrap;
}

.report-heading-actions {
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.report-primary-button {
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}


/* ==========================================================================
   ALERTA VENCIDAS
============================================================================ */

.receivables-warning {
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

.receivables-warning-icon {
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

.receivables-warning-content {
    min-width:0;
    flex:1;
    display:flex;
    flex-direction:column;
    gap:3px;
}

.receivables-warning-content > strong {
    font-size:13px;
}

.receivables-warning-content span {
    font-size:11px;
    line-height:1.5;
}

.receivables-warning-button {
    border-color:#f2c3c3;
    background:#fff;
    color:#b42318;
}


/* ==========================================================================
   VENCE HOJE
============================================================================ */

.receivables-due-today {
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

.receivables-due-today a {
    color:#8a5c00;
    font-weight:850;
}


/* ==========================================================================
   FILTROS
============================================================================ */

.report-filter-panel {
    margin-bottom:22px;
}

.report-filter-grid {
    display:grid;
    grid-template-columns:
        minmax(180px,1fr)
        minmax(150px,.8fr)
        minmax(150px,.8fr)
        auto;
    gap:12px;
    align-items:end;
}

.report-filter-item {
    margin:0;
}

.report-filter-actions {
    display:flex;
    align-items:flex-end;
}

.report-filter-actions .topbar-button {
    min-height:46px;
    padding-left:22px;
    padding-right:22px;
}

.report-filter-error {
    margin-top:10px;
    color:#b42318;
    font-size:12px;
}


/* ==========================================================================
   CONTAS A RECEBER
============================================================================ */

.receivable-report-grid {
    display:grid;
    grid-template-columns:
        repeat(4,minmax(0,1fr));
    gap:16px;
}

.receivable-report-card {
    display:flex;
    flex-direction:column;
    padding:18px;
    border:1px solid var(--border);
    border-radius:13px;
    background:#fafcfb;
}

.receivable-report-card span {
    color:var(--muted);
    font-size:11px;
    font-weight:750;
}

.receivable-report-card strong {
    margin-top:8px;
    font-size:22px;
}

.receivable-report-card small {
    margin-top:5px;
    color:var(--muted);
}

.receivable-report-card-danger {
    border-color:#f2c3c3;
    background:#fff0f0;
}

.receivable-report-card-danger span,
.receivable-report-card-danger strong,
.receivable-report-card-danger small {
    color:#b42318;
}

.receivable-report-card-warning {
    border-color:#f0d58d;
    background:#fff7df;
}

.receivable-report-card-warning span,
.receivable-report-card-warning strong,
.receivable-report-card-warning small {
    color:#8a5c00;
}

.receivable-no-date-warning {
    margin-top:16px;
    padding:12px 14px;
    border-radius:10px;
    background:#f6f7f6;
    color:var(--muted);
    font-size:11px;
}

.receivable-no-date-warning strong {
    color:var(--text);
}

.receivable-health {
    margin-top:20px;
}

.receivable-health > div:first-child {
    display:flex;
    justify-content:space-between;
    gap:20px;
    margin-bottom:8px;
    color:var(--muted);
    font-size:11px;
}

.receivable-health > div:first-child strong {
    color:var(--text);
}

.receivable-health-track {
    height:8px;
    border-radius:999px;
    background:#eef1ef;
    overflow:hidden;
}

.receivable-health-bar {
    height:100%;
    border-radius:999px;
    background:#b42318;
}


/* ==========================================================================
   LISTAS DE DÍVIDAS
============================================================================ */

.debt-list-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:14px 0;
    border-bottom:1px solid var(--border);
}

.debt-list-row:first-child {
    padding-top:0;
}

.debt-list-row:last-child {
    padding-bottom:0;
    border-bottom:0;
}

.debt-list-title {
    display:block;
    font-size:13px;
}

.debt-list-row-danger {
    border-left:3px solid #b42318;
    padding-left:12px;
}

.debt-list-row-warning {
    border-left:3px solid #c38a00;
    padding-left:12px;
}

.debt-overdue-days {
    margin-top:4px;
    color:#b42318;
    font-size:10px;
    font-weight:800;
}

.debt-due-today-label {
    margin-top:4px;
    color:#8a5c00;
    font-size:10px;
    font-weight:800;
}

.debt-list-value {
    flex:0 0 auto;
    display:flex;
    flex-direction:column;
    align-items:flex-end;
    gap:4px;
}

.debt-list-value strong {
    color:#8a5c00;
    font-size:13px;
}

.debt-list-value a {
    color:var(--primary-dark);
    font-size:10px;
    font-weight:800;
}


/* ==========================================================================
   RESUMO DO PERÍODO
============================================================================ */

.report-period-summary {
    display:flex;
    align-items:center;
    gap:32px;
    margin-top:22px;
    padding:20px 24px;
    border:1px solid var(--border);
    border-radius:17px;
    background:var(--surface);
    box-shadow:var(--shadow);
}

.report-period-summary
> div:not(.report-period-divider) {
    display:flex;
    flex-direction:column;
}

.report-period-label {
    color:var(--muted);
    font-size:12px;
    font-weight:700;
}

.report-period-value {
    margin-top:5px;
    font-size:23px;
    letter-spacing:-0.03em;
}

.report-period-value small {
    font-size:12px;
    font-weight:700;
}

.report-period-description {
    margin-top:4px;
    color:var(--muted);
    font-size:11px;
}

.report-period-divider {
    width:1px;
    align-self:stretch;
    background:var(--border);
}


/* ==========================================================================
   GRID PRINCIPAL
============================================================================ */

.report-main-grid {
    display:grid;
    grid-template-columns:
        minmax(0,1.65fr)
        minmax(300px,.7fr);
    gap:22px;
    margin-top:22px;
}


/* ==========================================================================
   GRÁFICO
============================================================================ */

.sales-chart {
    height:300px;
    display:flex;
    align-items:stretch;
    justify-content:space-between;
    gap:14px;
    padding-top:20px;
    overflow-x:auto;
}

.sales-chart-column {
    min-width:55px;
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:center;
}

.sales-chart-value {
    height:24px;
    color:var(--muted);
    font-size:11px;
    font-weight:700;
    white-space:nowrap;
}

.sales-chart-track {
    width:100%;
    max-width:58px;
    flex:1;
    display:flex;
    align-items:flex-end;
    border-radius:9px 9px 3px 3px;
    background:#f0f4f2;
    overflow:hidden;
}

.sales-chart-bar {
    width:100%;
    min-height:0;
    border-radius:9px 9px 3px 3px;
    background:var(--primary);
    transition:height .3s ease;
}

.sales-chart-label {
    margin-top:9px;
    font-size:12px;
    font-weight:800;
}

.sales-chart-count {
    min-height:16px;
    margin-top:3px;
    color:var(--muted);
    font-size:10px;
    white-space:nowrap;
}


/* ==========================================================================
   MÉTODOS
============================================================================ */

.report-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    padding:15px 0;
    border-bottom:1px solid var(--border);
}

.report-row:first-child {
    padding-top:0;
}

.report-row:last-child {
    padding-bottom:0;
    border-bottom:0;
}

.report-payment-info {
    min-width:0;
    display:flex;
    align-items:center;
    gap:12px;
}

.report-payment-icon {
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    border-radius:10px;
    background:var(--primary-soft);
    color:var(--primary-dark);
    font-size:11px;
    font-weight:850;
}

.report-row-title {
    display:block;
    overflow:hidden;
    font-size:13px;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.report-row-value {
    flex:0 0 auto;
    font-size:14px;
}

.report-row-value span {
    color:var(--muted);
    font-size:10px;
}


/* ==========================================================================
   TABELA
============================================================================ */

.report-table-body {
    padding:0;
}

.report-table-wrapper {
    overflow-x:auto;
}

.report-table-wrapper
.data-table th:first-child,
.report-table-wrapper
.data-table td:first-child {
    padding-left:22px;
}

.report-table-wrapper
.data-table th:last-child,
.report-table-wrapper
.data-table td:last-child {
    padding-right:22px;
}

.report-ranking {
    width:28px;
    height:28px;
    display:inline-grid;
    place-items:center;
    border-radius:8px;
    background:var(--primary-soft);
    color:var(--primary-dark);
    font-size:11px;
    font-weight:850;
}

.report-empty-table {
    padding:55px 25px !important;
    color:var(--muted);
    text-align:center;
}


/* ==========================================================================
   RESPONSIVO
============================================================================ */

@media (max-width:1150px) {

    .receivable-report-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:1100px) {

    .report-main-grid {
        grid-template-columns:1fr;
    }

}

@media (max-width:850px) {

    .report-filter-grid {
        grid-template-columns:1fr 1fr;
    }

}

@media (max-width:760px) {

    .report-heading-actions {
        width:100%;
    }

    .report-period-summary {
        align-items:flex-start;
        gap:18px;
    }

    .receivables-warning {
        align-items:flex-start;
        flex-wrap:wrap;
    }

    .receivables-warning-button {
        margin-left:52px;
    }

}

@media (max-width:600px) {

    .receivable-report-grid {
        grid-template-columns:1fr;
    }

    .receivables-due-today {
        align-items:flex-start;
        flex-direction:column;
    }

}

@media (max-width:560px) {

    .report-filter-grid {
        grid-template-columns:1fr;
    }

}

@media (max-width:520px) {

    .report-period-summary {
        flex-direction:column;
    }

    .report-period-divider {
        width:100%;
        height:1px;
    }

    .debt-list-row {
        align-items:flex-start;
        flex-direction:column;
    }

    .debt-list-value {
        align-items:flex-start;
    }

}

@endsection


@section('scripts')

<script>

    function toggleCustomPeriod() {

        const periodSelect =
            document.getElementById(
                'report-period'
            );

        if (! periodSelect) {
            return;
        }

        const customFields =
            document.querySelectorAll(
                '.custom-period-field'
            );

        const isCustom =
            periodSelect.value
            === 'custom';

        customFields.forEach(
            function (field) {

                field.style.display =
                    isCustom
                        ? 'block'
                        : 'none';

                const input =
                    field.querySelector(
                        'input'
                    );

                if (input) {

                    input.required =
                        isCustom;
                }
            }
        );
    }


    document.addEventListener(
        'DOMContentLoaded',
        function () {

            toggleCustomPeriod();

        }
    );

</script>

@endsection