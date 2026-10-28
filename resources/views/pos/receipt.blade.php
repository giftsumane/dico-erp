@extends('layouts.app')

@section('title', $sale->number . ' | DICO')
@section('topbar-title', 'Venda concluída')

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

    $companyName =
        $company->trade_name
        ?: $company->legal_name
        ?: $company->name;
@endphp


<div class="receipt-actions no-print">

    <a
        href="{{ route('pos.index') }}"
        class="topbar-button"
    >
        ← Nova venda
    </a>

    <button
        type="button"
        class="topbar-button receipt-print-button"
        onclick="window.print()"
    >
        Imprimir recibo
    </button>

</div>


<div class="receipt-wrapper">

    <div class="receipt">

        {{-- =============================================================
             EMPRESA
        ============================================================== --}}

        <div class="receipt-company">

            <div class="receipt-company-name">
                {{ $companyName }}
            </div>

            @if (
                $company->legal_name
                && $company->legal_name !== $companyName
            )
                <div class="receipt-company-legal">
                    {{ $company->legal_name }}
                </div>
            @endif


            @if ($company->nuit)

                <div class="receipt-company-info">
                    NUIT: {{ $company->nuit }}
                </div>

            @endif


            @if ($company->address)

                <div class="receipt-company-info">
                    {{ $company->address }}
                </div>

            @endif


            @if ($company->phone)

                <div class="receipt-company-info">
                    Tel: {{ $company->phone }}
                </div>

            @endif


            @if ($company->email)

                <div class="receipt-company-info">
                    {{ $company->email }}
                </div>

            @endif

        </div>


        <div class="receipt-divider"></div>


        {{-- =============================================================
             LOJA
        ============================================================== --}}

        <div class="receipt-section">

            <div class="receipt-section-title">
                Loja
            </div>

            <div class="receipt-info-row">
                <span>Estabelecimento</span>
                <strong>
                    {{ $sale->store?->name ?? '—' }}
                </strong>
            </div>

            @if ($sale->store?->address)

                <div class="receipt-info-row">
                    <span>Endereço</span>
                    <strong>
                        {{ $sale->store->address }}
                    </strong>
                </div>

            @endif

        </div>


        <div class="receipt-divider"></div>


        {{-- =============================================================
             VENDA
        ============================================================== --}}

        <div class="receipt-section">

            <div class="receipt-section-title">
                Venda
            </div>

            <div class="receipt-info-row">
                <span>Número</span>
                <strong>{{ $sale->number }}</strong>
            </div>

            <div class="receipt-info-row">
                <span>Data</span>
                <strong>
                    {{ $sale->completed_at
                        ?->format('d/m/Y H:i')
                        ?? $sale->created_at->format('d/m/Y H:i')
                    }}
                </strong>
            </div>

            <div class="receipt-info-row">
                <span>Operador</span>
                <strong>
                    {{ $sale->user?->name ?? '—' }}
                </strong>
            </div>

            <div class="receipt-info-row">
                <span>Cliente</span>
                <strong>
                    {{ $sale->customer?->name
                        ?? 'Consumidor final'
                    }}
                </strong>
            </div>

            @if ($sale->customer?->nuit)

                <div class="receipt-info-row">
                    <span>NUIT cliente</span>
                    <strong>
                        {{ $sale->customer->nuit }}
                    </strong>
                </div>

            @endif

        </div>


        <div class="receipt-divider"></div>


        {{-- =============================================================
             PRODUTOS
        ============================================================== --}}

        <div class="receipt-products">

            @foreach ($sale->items as $item)

                <div class="receipt-product">

                    <div class="receipt-product-name">

                        {{ $item->product?->name
                            ?? 'Produto'
                        }}

                    </div>


                    @if ($item->product?->sku)

                        <div class="receipt-product-sku">
                            SKU: {{ $item->product->sku }}
                        </div>

                    @endif


                    <div class="receipt-product-line">

                        <div>

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

                            @if ($item->product?->unit?->symbol)

                                {{ $item->product->unit->symbol }}

                            @endif

                            ×

                            {{ number_format(
                                $item->unit_price,
                                2,
                                ',',
                                '.'
                            ) }}

                        </div>


                        <strong>

                            {{ number_format(
                                $item->total,
                                2,
                                ',',
                                '.'
                            ) }}

                        </strong>

                    </div>


                    @if ((float) $item->tax_total > 0)

                        <div class="receipt-tax-note">

                            IVA:
                            {{ number_format(
                                $item->tax_total,
                                2,
                                ',',
                                '.'
                            ) }}
                            {{ $company->currency ?? 'MZN' }}

                        </div>

                    @endif

                </div>

            @endforeach

        </div>


        <div class="receipt-divider"></div>


        {{-- =============================================================
             TOTAIS
        ============================================================== --}}

        <div class="receipt-totals">

            <div class="receipt-total-row">

                <span>Subtotal</span>

                <span>
                    {{ number_format(
                        $sale->subtotal,
                        2,
                        ',',
                        '.'
                    ) }}
                    {{ $company->currency ?? 'MZN' }}
                </span>

            </div>


            @if ((float) $sale->discount_total > 0)

                <div class="receipt-total-row">

                    <span>Desconto</span>

                    <span>
                        -
                        {{ number_format(
                            $sale->discount_total,
                            2,
                            ',',
                            '.'
                        ) }}
                        {{ $company->currency ?? 'MZN' }}
                    </span>

                </div>

            @endif


            <div class="receipt-total-row">

                <span>IVA</span>

                <span>
                    {{ number_format(
                        $sale->tax_total,
                        2,
                        ',',
                        '.'
                    ) }}
                    {{ $company->currency ?? 'MZN' }}
                </span>

            </div>


            <div class="receipt-total-row receipt-grand-total">

                <span>Total</span>

                <span>
                    {{ number_format(
                        $sale->total,
                        2,
                        ',',
                        '.'
                    ) }}
                    {{ $company->currency ?? 'MZN' }}
                </span>

            </div>

        </div>


        <div class="receipt-divider"></div>


        {{-- =============================================================
             PAGAMENTOS
        ============================================================== --}}

        <div class="receipt-section">

            <div class="receipt-section-title">
                Pagamento
            </div>

            @foreach ($sale->payments as $payment)

                <div class="receipt-info-row">

                    <span>

                        {{ $paymentLabels[$payment->method]
                            ?? $payment->method
                        }}

                    </span>

                    <strong>

                        {{ number_format(
                            $payment->amount,
                            2,
                            ',',
                            '.'
                        ) }}

                        {{ $company->currency ?? 'MZN' }}

                    </strong>

                </div>


                @if ($payment->reference)

                    <div class="receipt-payment-reference">

                        Ref:
                        {{ $payment->reference }}

                    </div>

                @endif

            @endforeach


            @if ((float) $sale->paid_total > 0)

                <div class="receipt-info-row">

                    <span>Valor recebido</span>

                    <strong>

                        {{ number_format(
                            $sale->paid_total,
                            2,
                            ',',
                            '.'
                        ) }}

                        {{ $company->currency ?? 'MZN' }}

                    </strong>

                </div>

            @endif


            @if ((float) $sale->change_total > 0)

                <div class="receipt-info-row">

                    <span>Troco</span>

                    <strong>

                        {{ number_format(
                            $sale->change_total,
                            2,
                            ',',
                            '.'
                        ) }}

                        {{ $company->currency ?? 'MZN' }}

                    </strong>

                </div>

            @endif

        </div>


        {{-- =============================================================
             RODAPÉ
        ============================================================== --}}

        <div class="receipt-footer">

            @if ($company->receipt_footer)

                <div class="receipt-footer-message">

                    {!! nl2br(
                        e($company->receipt_footer)
                    ) !!}

                </div>

            @else

                <div class="receipt-footer-message">
                    Obrigado pela preferência.
                </div>

            @endif


            <div class="receipt-footer-system">
                Emitido através do DICO
            </div>

        </div>

    </div>

</div>

@endsection


@section('styles')

.receipt-actions {
    max-width:520px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin:0 auto 18px;
}

.receipt-print-button {
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}

.receipt-wrapper {
    display:flex;
    justify-content:center;
}

.receipt {
    width:100%;
    max-width:520px;
    padding:30px;
    border:1px solid var(--border);
    border-radius:16px;
    background:#fff;
    box-shadow:var(--shadow);
    font-family:
        Arial,
        Helvetica,
        sans-serif;
    font-size:12px;
}

.receipt-company {
    text-align:center;
}

.receipt-company-name {
    font-size:21px;
    font-weight:900;
    letter-spacing:-0.02em;
}

.receipt-company-legal {
    margin-top:5px;
    font-size:12px;
    font-weight:700;
}

.receipt-company-info {
    margin-top:4px;
    color:#555;
    line-height:1.4;
}

.receipt-divider {
    margin:20px 0;
    border-top:1px dashed #bbb;
}

.receipt-section-title {
    margin-bottom:10px;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.08em;
}

.receipt-info-row {
    display:flex;
    justify-content:space-between;
    gap:20px;
    padding:4px 0;
}

.receipt-info-row span {
    color:#666;
}

.receipt-info-row strong {
    text-align:right;
}

.receipt-product {
    padding:10px 0;
    border-bottom:1px dotted #ddd;
}

.receipt-product:last-child {
    border-bottom:0;
}

.receipt-product-name {
    font-size:12px;
    font-weight:800;
}

.receipt-product-sku {
    margin-top:3px;
    color:#777;
    font-size:9px;
}

.receipt-product-line {
    display:flex;
    justify-content:space-between;
    gap:15px;
    margin-top:5px;
}

.receipt-tax-note {
    margin-top:4px;
    color:#777;
    font-size:10px;
}

.receipt-totals {
    display:grid;
    gap:8px;
}

.receipt-total-row {
    display:flex;
    justify-content:space-between;
    gap:20px;
}

.receipt-grand-total {
    margin-top:4px;
    padding-top:12px;
    border-top:1px solid #222;
    font-size:17px;
    font-weight:900;
}

.receipt-payment-reference {
    margin:3px 0 7px;
    color:#777;
    font-size:10px;
    text-align:right;
}

.receipt-footer {
    margin-top:30px;
    text-align:center;
}

.receipt-footer-message {
    font-size:11px;
    line-height:1.6;
}

.receipt-footer-system {
    margin-top:15px;
    color:#888;
    font-size:9px;
}

@media print {

    body {
        background:#fff !important;
    }

    .sidebar,
    .topbar,
    .no-print {
        display:none !important;
    }

    .main-area {
        margin-left:0 !important;
    }

    .content {
        padding:0 !important;
    }

    .receipt-wrapper {
        display:block;
    }

    .receipt {
        width:80mm;
        max-width:80mm;
        margin:0;
        padding:5mm;
        border:0;
        border-radius:0;
        box-shadow:none;
    }

}

@endsection