@extends('layouts.app')

@section('title', $sale->number . ' | Contas a Receber')
@section('topbar-title', 'Conta a Receber')

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

    $isPaid =
        $sale->payment_status === 'paid'
        || (float) $sale->amount_due <= 0;

    $isOverdue =
        ! $isPaid
        && $sale->due_date
        && $sale->due_date->isPast()
        && ! $sale->due_date->isToday();
@endphp


{{-- =========================================================================
     CABEÇALHO
============================================================================ --}}

<div class="page-heading">

    <div
        style="
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            gap:20px;
            flex-wrap:wrap;
        "
    >

        <div>

            <h1>
                {{ $sale->number }}
            </h1>


            <p>

                {{ $sale->customer?->name
                    ?? 'Cliente não identificado'
                }}

                @if ($sale->store)

                    · {{ $sale->store->name }}

                @endif

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
                Voltar
            </a>


            <a
                href="{{ route(
                    'sales.show',
                    $sale
                ) }}"
                class="topbar-button"
            >
                Ver venda
            </a>


            <a
                href="{{ route(
                    'pos.receipt',
                    $sale
                ) }}"
                class="topbar-button"
            >
                Recibo
            </a>

        </div>

    </div>

</div>


{{-- =========================================================================
     ESTADO
============================================================================ --}}

@if ($isPaid)

    <div class="receivable-alert receivable-alert-success">

        <div class="receivable-alert-icon">
            ✓
        </div>

        <div>

            <strong>
                Conta liquidada
            </strong>

            <div>
                Esta venda encontra-se totalmente paga.
            </div>

        </div>

    </div>

@elseif ($isOverdue)

    <div class="receivable-alert receivable-alert-danger">

        <div class="receivable-alert-icon">
            !
        </div>

        <div>

            <strong>
                Pagamento vencido
            </strong>

            <div>

                O vencimento desta conta foi em

                {{ $sale->due_date->format('d/m/Y') }}.

            </div>

        </div>

    </div>

@elseif (
    $sale->due_date
    && $sale->due_date->isToday()
)

    <div class="receivable-alert receivable-alert-warning">

        <div class="receivable-alert-icon">
            !
        </div>

        <div>

            <strong>
                Vence hoje
            </strong>

            <div>
                A data prevista de pagamento é hoje.
            </div>

        </div>

    </div>

@endif


{{-- =========================================================================
     MÉTRICAS
============================================================================ --}}

<div class="receivable-detail-metrics">

    {{-- Total --}}

    <article class="metric-card">

        <div class="metric-label">
            Total da venda
        </div>

        <div class="metric-value">

            {{ number_format(
                $sale->total,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">
            MT
        </div>

    </article>


    {{-- Pago --}}

    <article class="metric-card">

        <div class="metric-label">
            Total pago
        </div>

        <div
            class="metric-value"
            style="color:var(--primary-dark);"
        >

            {{ number_format(
                $sale->paid_total,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">
            MT recebidos
        </div>

    </article>


    {{-- Saldo --}}

    <article class="metric-card">

        <div class="metric-label">
            Saldo por receber
        </div>

        <div
            class="metric-value"
            @if (! $isPaid)
                style="color:#8a5c00;"
            @endif
        >

            {{ number_format(
                $sale->amount_due,
                2,
                ',',
                '.'
            ) }}

        </div>

        <div class="metric-description">

            {{ $isPaid
                ? 'Venda liquidada'
                : 'MT por liquidar'
            }}

        </div>

    </article>


    {{-- Estado --}}

    <article class="metric-card">

        <div class="metric-label">
            Estado
        </div>


        <div
            class="metric-value"
            style="
                margin-top:22px;
                font-size:18px;
            "
        >

            @if ($isPaid)

                <span class="status-badge">
                    Pago
                </span>

            @elseif (
                $sale->payment_status === 'partial'
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

        </div>


        <div class="metric-description">

            @if (
                ! $isPaid
                && $sale->due_date
            )

                Vencimento:
                {{ $sale->due_date->format('d/m/Y') }}

            @elseif ($isPaid)

                Sem saldo pendente

            @else

                Sem vencimento definido

            @endif

        </div>

    </article>

</div>


{{-- =========================================================================
     INFORMAÇÕES + NOVO PAGAMENTO
============================================================================ --}}

<div class="receivable-main-grid">

    {{-- =====================================================================
         INFORMAÇÕES
    ====================================================================== --}}

    <div class="panel">

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Dados da conta
                </h2>

                <p class="panel-subtitle">
                    Informações da venda e do cliente.
                </p>

            </div>

        </div>


        <div class="panel-body">

            <div class="receivable-info-grid">

                {{-- Cliente --}}

                <div>

                    <span class="receivable-info-label">
                        Cliente
                    </span>

                    <strong>
                        {{ $sale->customer?->name
                            ?? '—'
                        }}
                    </strong>

                </div>


                {{-- NUIT --}}

                <div>

                    <span class="receivable-info-label">
                        NUIT
                    </span>

                    <strong>
                        {{ $sale->customer?->nuit
                            ?? '—'
                        }}
                    </strong>

                </div>


                {{-- Contacto --}}

                <div>

                    <span class="receivable-info-label">
                        Contacto
                    </span>

                    <strong>
                        {{ $sale->customer?->phone
                            ?? '—'
                        }}
                    </strong>

                </div>


                {{-- Email --}}

                <div>

                    <span class="receivable-info-label">
                        E-mail
                    </span>

                    <strong>
                        {{ $sale->customer?->email
                            ?? '—'
                        }}
                    </strong>

                </div>


                {{-- Venda --}}

                <div>

                    <span class="receivable-info-label">
                        Venda
                    </span>

                    <strong>
                        {{ $sale->number }}
                    </strong>

                </div>


                {{-- Data --}}

                <div>

                    <span class="receivable-info-label">
                        Data da venda
                    </span>

                    <strong>

                        {{ $sale->completed_at
                            ?->format('d/m/Y H:i')
                            ?? $sale->created_at
                                ->format('d/m/Y H:i')
                        }}

                    </strong>

                </div>


                {{-- Operador --}}

                <div>

                    <span class="receivable-info-label">
                        Operador
                    </span>

                    <strong>
                        {{ $sale->user?->name
                            ?? '—'
                        }}
                    </strong>

                </div>


                {{-- Loja --}}

                <div>

                    <span class="receivable-info-label">
                        Loja
                    </span>

                    <strong>
                        {{ $sale->store?->name
                            ?? '—'
                        }}
                    </strong>

                </div>


                {{-- Vencimento --}}

                <div>

                    <span class="receivable-info-label">
                        Vencimento
                    </span>

                    <strong
                        @if ($isOverdue)
                            style="color:#b42318;"
                        @endif
                    >

                        {{ $sale->due_date
                            ?->format('d/m/Y')
                            ?? '—'
                        }}

                    </strong>

                </div>


                {{-- Situação --}}

                <div>

                    <span class="receivable-info-label">
                        Situação
                    </span>

                    <strong>

                        @if ($isPaid)

                            Liquidada

                        @elseif ($isOverdue)

                            Vencida

                        @elseif (
                            $sale->due_date
                            && $sale->due_date->isToday()
                        )

                            Vence hoje

                        @else

                            Em aberto

                        @endif

                    </strong>

                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================================
         REGISTAR PAGAMENTO
    ====================================================================== --}}

    <div>

        @if (! $isPaid)

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <h2 class="panel-title">
                            Registar pagamento
                        </h2>

                        <p class="panel-subtitle">
                            Receber parte ou totalidade do saldo.
                        </p>

                    </div>

                </div>


                <div class="panel-body">

                    @if ($errors->any())

                        <div class="receivable-form-errors">

                            @foreach (
                                $errors->all()
                                as $error
                            )

                                <div>
                                    {{ $error }}
                                </div>

                            @endforeach

                        </div>

                    @endif


                    <form
                        method="POST"
                        action="{{ route(
                            'receivables.payments.store',
                            $sale
                        ) }}"
                    >

                        @csrf


                        {{-- Saldo --}}

                        <div class="payment-balance-box">

                            <span>
                                Saldo actual
                            </span>

                            <strong>

                                {{ number_format(
                                    $sale->amount_due,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </strong>

                        </div>


                        {{-- Valor --}}

                        <div class="form-group">

                            <label class="form-label">
                                Valor a receber *
                            </label>

                            <input
                                type="number"
                                name="amount"
                                value="{{ old('amount') }}"
                                class="form-control"
                                min="0.01"
                                max="{{ $sale->amount_due }}"
                                step="0.01"
                                required
                            >

                            <div class="payment-hint">

                                Máximo:

                                {{ number_format(
                                    $sale->amount_due,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </div>

                        </div>


                        {{-- Método --}}

                        <div class="form-group">

                            <label class="form-label">
                                Método de pagamento *
                            </label>

                            <select
                                name="method"
                                class="form-control"
                                required
                            >

                                @foreach (
                                    $paymentLabels
                                    as $value => $label
                                )

                                    <option
                                        value="{{ $value }}"
                                        @selected(
                                            old(
                                                'method',
                                                'cash'
                                            ) === $value
                                        )
                                    >
                                        {{ $label }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Referência --}}

                        <div class="form-group">

                            <label class="form-label">
                                Referência
                            </label>

                            <input
                                type="text"
                                name="reference"
                                value="{{ old('reference') }}"
                                class="form-control"
                                placeholder="Opcional"
                            >

                            <div class="payment-hint">

                                Ex.: referência M-Pesa,
                                transferência ou talão.

                            </div>

                        </div>


                        <button
                            type="submit"
                            class="topbar-button"
                            style="
                                width:100%;
                                min-height:48px;
                                background:var(--primary);
                                color:#fff;
                                border-color:var(--primary);
                            "
                        >
                            Registar pagamento
                        </button>

                    </form>

                </div>

            </div>

        @else

            <div class="panel">

                <div class="panel-body">

                    <div class="paid-account-box">

                        <div class="paid-account-icon">
                            ✓
                        </div>

                        <strong>
                            Conta totalmente liquidada
                        </strong>

                        <span>
                            Não existem valores pendentes nesta venda.
                        </span>

                    </div>

                </div>

            </div>

        @endif

    </div>

</div>


{{-- =========================================================================
     HISTÓRICO DE PAGAMENTOS
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Histórico de pagamentos
            </h2>

            <p class="panel-subtitle">
                Todos os pagamentos associados a esta venda.
            </p>

        </div>

    </div>


    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Data</th>
                        <th>Método</th>
                        <th>Valor</th>
                        <th>Referência</th>
                        <th>Operador</th>
                        <th>Caixa</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse (
                        $sale->payments
                            ->sortByDesc(
                                fn ($payment) =>
                                    $payment->paid_at
                                    ?? $payment->created_at
                            )
                        as $payment
                    )

                        <tr>

                            {{-- Data --}}

                            <td>

                                {{ $payment->paid_at
                                    ?->format('d/m/Y H:i')
                                    ?? $payment->created_at
                                        ->format('d/m/Y H:i')
                                }}

                            </td>


                            {{-- Método --}}

                            <td>

                                {{ $paymentLabels[
                                    $payment->method
                                ] ?? $payment->method }}

                            </td>


                            {{-- Valor --}}

                            <td>

                                <strong>

                                    {{ number_format(
                                        $payment->amount,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                    MT

                                </strong>

                            </td>


                            {{-- Referência --}}

                            <td>

                                {{ $payment->reference
                                    ?? '—'
                                }}

                            </td>


                            {{-- Operador --}}

                            <td>

                                {{ $payment->user?->name
                                    ?? '—'
                                }}

                            </td>


                            {{-- Caixa --}}

                            <td>

                                @if ($payment->cashRegister)

                                    #{{ $payment
                                        ->cashRegister
                                        ->id
                                    }}

                                @else

                                    —

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                style="
                                    padding:45px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem pagamentos
                                associados a esta venda.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- =========================================================================
     PRODUTOS DA VENDA
============================================================================ --}}

<div
    class="panel"
    style="margin-top:22px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Produtos da venda
            </h2>

            <p class="panel-subtitle">
                Conteúdo da venda original.
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
                        <th>Preço</th>
                        <th>Desconto</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>

                </thead>


                <tbody>

                    @foreach (
                        $sale->items
                        as $item
                    )

                        <tr>

                            <td>

                                <strong>

                                    {{ $item->product?->name
                                        ?? 'Produto removido'
                                    }}

                                </strong>

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


                                @if (
                                    $item->product
                                        ?->unit
                                        ?->symbol
                                )

                                    {{ $item->product
                                        ->unit
                                        ->symbol
                                    }}

                                @endif

                            </td>


                            <td>

                                {{ number_format(
                                    $item->unit_price,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </td>


                            <td>

                                {{ number_format(
                                    $item->discount ?? 0,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                MT

                            </td>


                            <td>

                                {{ number_format(
                                    $item->tax_total,
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

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection


@section('styles')

.receivable-detail-metrics {
    display:grid;
    grid-template-columns:
        repeat(4,minmax(0,1fr));
    gap:18px;
}

.receivable-main-grid {
    display:grid;
    grid-template-columns:
        minmax(0,1.4fr)
        minmax(320px,.65fr);
    gap:22px;
    margin-top:22px;
    align-items:start;
}


/* =========================================================================
   ALERTAS
============================================================================ */

.receivable-alert {
    display:flex;
    align-items:center;
    gap:13px;
    margin-bottom:22px;
    padding:15px 17px;
    border-radius:12px;
    font-size:12px;
}

.receivable-alert strong {
    display:block;
    margin-bottom:3px;
    font-size:13px;
}

.receivable-alert-icon {
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    border-radius:10px;
    font-size:17px;
    font-weight:900;
}

.receivable-alert-success {
    background:var(--primary-soft);
    color:var(--primary-dark);
}

.receivable-alert-success
.receivable-alert-icon {
    background:var(--primary);
    color:#fff;
}

.receivable-alert-danger {
    background:#fff0f0;
    color:#b42318;
}

.receivable-alert-danger
.receivable-alert-icon {
    background:#b42318;
    color:#fff;
}

.receivable-alert-warning {
    background:#fff7df;
    color:#8a5c00;
}

.receivable-alert-warning
.receivable-alert-icon {
    background:#8a5c00;
    color:#fff;
}


/* =========================================================================
   DADOS
============================================================================ */

.receivable-info-grid {
    display:grid;
    grid-template-columns:
        repeat(2,minmax(0,1fr));
    gap:24px;
}

.receivable-info-grid > div {
    display:flex;
    flex-direction:column;
    gap:6px;
}

.receivable-info-label {
    color:var(--muted);
    font-size:10px;
    font-weight:750;
    text-transform:uppercase;
    letter-spacing:.05em;
}


/* =========================================================================
   ESTADOS
============================================================================ */

.receivable-status-partial {
    background:#fff7df;
    color:#8a5c00;
}

.receivable-status-credit {
    background:#fff0f0;
    color:#b42318;
}


/* =========================================================================
   FORM PAGAMENTO
============================================================================ */

.payment-balance-box {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-bottom:20px;
    padding:15px;
    border-radius:11px;
    background:#fff7df;
    color:#8a5c00;
}

.payment-balance-box strong {
    font-size:18px;
}

.receivable-form-errors {
    display:grid;
    gap:5px;
    margin-bottom:18px;
    padding:12px 14px;
    border-radius:10px;
    background:#fff0f0;
    color:#b42318;
    font-size:12px;
}

.payment-hint {
    margin-top:6px;
    color:var(--muted);
    font-size:11px;
    line-height:1.5;
}


/* =========================================================================
   LIQUIDADA
============================================================================ */

.paid-account-box {
    display:flex;
    flex-direction:column;
    align-items:center;
    padding:35px 15px;
    text-align:center;
}

.paid-account-icon {
    width:52px;
    height:52px;
    display:grid;
    place-items:center;
    margin-bottom:14px;
    border-radius:50%;
    background:var(--primary-soft);
    color:var(--primary-dark);
    font-size:23px;
    font-weight:900;
}

.paid-account-box strong {
    font-size:15px;
}

.paid-account-box span {
    margin-top:7px;
    color:var(--muted);
    font-size:12px;
}


@media (max-width:1100px) {

    .receivable-detail-metrics {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

    .receivable-main-grid {
        grid-template-columns:1fr;
    }

}

@media (max-width:600px) {

    .receivable-detail-metrics,
    .receivable-info-grid {
        grid-template-columns:1fr;
    }

}

@endsection