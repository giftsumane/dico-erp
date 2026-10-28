@extends('layouts.app')

@section('title', 'POS | DICO')
@section('topbar-title', 'Ponto de Venda')

@section('content')

<div class="page-heading">
    <h1>Ponto de Venda</h1>

    <p>
        Loja:
        <strong>{{ $activeStore?->name }}</strong>
    </p>
</div>


@if ($errors->any())

    <div
        style="
            margin-bottom:20px;
            padding:14px 16px;
            background:#fff0f0;
            color:#b42318;
            border-radius:12px;
        "
    >

        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach

    </div>

@endif


<div class="pos-grid">

    {{-- =====================================================================
         PRODUTOS
    ====================================================================== --}}

    <section>

        <div class="panel">

            <div class="panel-header">

                <div>
                    <h2 class="panel-title">
                        Produtos
                    </h2>

                    <p class="panel-subtitle">
                        Pesquise por nome, SKU ou código de barras.
                    </p>
                </div>

            </div>


            <div class="panel-body">

                <form
                    method="GET"
                    action="{{ route('pos.index') }}"
                    style="
                        display:flex;
                        gap:10px;
                        margin-bottom:20px;
                    "
                >

                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Pesquisar produto..."
                        autofocus
                    >

                    <button
                        type="submit"
                        class="topbar-button"
                    >
                        Pesquisar
                    </button>

                </form>


                <div class="pos-products">

                    @forelse ($products as $product)

                        @php
                            $availableStock = (float) (
                                $stockMap[$product->id] ?? 0
                            );

                            $variantLabel = null;

                            if ($product->isVariant()) {
                                $variantLabel = $product
                                    ->attributeValues
                                    ->sortBy(
                                        fn ($value) =>
                                            $value->attribute?->position ?? 0
                                    )
                                    ->pluck('value')
                                    ->implode(' / ');
                            }

                            $productData = [
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => (float) $product->sale_price,
                                'tax' => (float) $product->tax_rate,
                                'stock' => $availableStock,
                                'track_stock' => (bool) $product->track_stock,
                                'unit' => $product->unit?->symbol,
                                'type' => $product->product_type,
                                'sku' => $product->sku,
                                'parent_name' => $product->parent?->name,
                                'variant_label' => $variantLabel,
                            ];
                        @endphp


                        <button
                            type="button"
                            class="pos-product"
                            onclick="addToCart({{ Js::from($productData) }})"
                            @disabled(
                                $product->track_stock
                                && $availableStock <= 0
                            )
                        >

                            <strong>
                                {{ $product->name }}
                            </strong>


                            @if ($product->isVariant())

                                <div class="pos-product-meta">

                                    <span class="pos-product-badge">
                                        Variante
                                    </span>

                                    @if ($variantLabel)
                                        <small>
                                            {{ $variantLabel }}
                                        </small>
                                    @endif

                                </div>

                            @elseif ($product->isService())

                                <div class="pos-product-meta">

                                    <span class="pos-product-badge">
                                        Serviço
                                    </span>

                                </div>

                            @endif


                            @if ($product->sku)

                                <small class="pos-product-sku">
                                    SKU: {{ $product->sku }}
                                </small>

                            @endif


                            <span>
                                {{ number_format(
                                    $product->sale_price,
                                    2,
                                    ',',
                                    '.'
                                ) }}
                                MT
                            </span>


                            <small>

                                @if ($product->track_stock)

                                    Stock:

                                    {{ rtrim(
                                        rtrim(
                                            number_format(
                                                $availableStock,
                                                3,
                                                ',',
                                                '.'
                                            ),
                                            '0'
                                        ),
                                        ','
                                    ) }}

                                @else

                                    Sem controlo de stock

                                @endif

                            </small>

                        </button>

                    @empty

                        <div class="empty-state">
                            Nenhum produto encontrado.
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </section>


    {{-- =====================================================================
         CARRINHO
    ====================================================================== --}}

    <aside>

        <form
            method="POST"
            action="{{ route('pos.complete') }}"
            id="checkout-form"
        >

            @csrf


            <div class="panel">

                <div class="panel-header">

                    <div>
                        <h2 class="panel-title">
                            Carrinho
                        </h2>

                        <p class="panel-subtitle">
                            Venda actual
                        </p>
                    </div>

                </div>


                <div class="panel-body">

                    <div
                        id="cart-empty"
                        class="empty-state"
                    >
                        Nenhum produto no carrinho.
                    </div>


                    <div id="cart-items"></div>


                    <div
                        id="cart-inputs"
                        style="display:none;"
                    ></div>


                    {{-- =====================================================
                         DESCONTO GLOBAL
                    ====================================================== --}}

                    <div class="pos-discount-section">

                        <div class="discount-section-title">
                            Desconto global
                        </div>

                        <div class="payment-hint discount-section-description">
                            Aplicado depois dos descontos individuais.
                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Tipo de desconto
                            </label>

                            <select
                                name="discount_type"
                                id="discount-type"
                                class="form-control"
                                onchange="toggleDiscountFields()"
                            >

                                <option
                                    value="none"
                                    @selected(
                                        old(
                                            'discount_type',
                                            'none'
                                        ) === 'none'
                                    )
                                >
                                    Sem desconto global
                                </option>

                                <option
                                    value="percentage"
                                    @selected(
                                        old('discount_type')
                                        === 'percentage'
                                    )
                                >
                                    Percentagem (%)
                                </option>

                                <option
                                    value="fixed"
                                    @selected(
                                        old('discount_type')
                                        === 'fixed'
                                    )
                                >
                                    Valor fixo (MT)
                                </option>

                            </select>

                        </div>


                        <div
                            class="form-group"
                            id="discount-value-group"
                            style="display:none;"
                        >

                            <label
                                class="form-label"
                                id="discount-value-label"
                            >
                                Desconto
                            </label>

                            <input
                                type="number"
                                name="discount_value"
                                id="discount-value"
                                value="{{ old('discount_value') }}"
                                class="form-control"
                                min="0"
                                step="0.01"
                                oninput="renderCart()"
                            >

                            <div
                                id="discount-hint"
                                class="payment-hint"
                            ></div>

                        </div>

                    </div>


                    {{-- =====================================================
                         TOTAIS
                    ====================================================== --}}

                    <div class="pos-totals">

                        <div>
                            <span>
                                Subtotal
                            </span>

                            <strong id="subtotal-value">
                                0,00 MT
                            </strong>
                        </div>


                        <div
                            id="item-discount-row"
                            class="discount-total-row"
                            style="display:none;"
                        >
                            <span>
                                Descontos dos produtos
                            </span>

                            <strong id="item-discount-value">
                                - 0,00 MT
                            </strong>
                        </div>


                        <div
                            id="global-discount-row"
                            class="discount-total-row"
                            style="display:none;"
                        >
                            <span>
                                Desconto global
                            </span>

                            <strong id="global-discount-value">
                                - 0,00 MT
                            </strong>
                        </div>


                        <div
                            id="discount-row"
                            class="discount-total-row discount-total-final"
                            style="display:none;"
                        >
                            <span>
                                Desconto total
                            </span>

                            <strong id="discount-total-value">
                                - 0,00 MT
                            </strong>
                        </div>


                        <div>
                            <span>
                                IVA
                            </span>

                            <strong id="tax-value">
                                0,00 MT
                            </strong>
                        </div>


                        <div class="pos-grand-total">
                            <span>
                                Total
                            </span>

                            <strong id="total-value">
                                0,00 MT
                            </strong>
                        </div>

                    </div>


                    {{-- =====================================================
                         PAGAMENTO
                    ====================================================== --}}

                    <div class="pos-payment-section">


                        {{-- Cliente --}}

                        <div class="form-group">

                            <label class="form-label">
                                Cliente
                            </label>

                            <select
                                name="customer_id"
                                id="customer-id"
                                class="form-control"
                            >

                                <option value="">
                                    Consumidor final
                                </option>

                                @foreach ($customers as $customer)

                                    <option
                                        value="{{ $customer->id }}"
                                        @selected(
                                            old('customer_id')
                                            == $customer->id
                                        )
                                    >
                                        {{ $customer->name }}
                                    </option>

                                @endforeach

                            </select>


                            <div
                                id="customer-credit-hint"
                                class="payment-hint"
                                style="display:none;"
                            >
                                É obrigatório seleccionar um cliente
                                quando existir saldo em dívida.
                            </div>

                        </div>


                        {{-- Condição de pagamento --}}

                        <div class="form-group">

                            <label class="form-label">
                                Condição de pagamento
                            </label>

                            <select
                                name="payment_type"
                                id="payment-type"
                                class="form-control"
                                onchange="togglePaymentType()"
                                required
                            >

                                <option
                                    value="full"
                                    @selected(
                                        old(
                                            'payment_type',
                                            'full'
                                        ) === 'full'
                                    )
                                >
                                    Pagamento total
                                </option>

                                <option
                                    value="partial"
                                    @selected(
                                        old('payment_type')
                                        === 'partial'
                                    )
                                >
                                    Pagamento parcial / depósito
                                </option>

                                <option
                                    value="credit"
                                    @selected(
                                        old('payment_type')
                                        === 'credit'
                                    )
                                >
                                    Venda a crédito
                                </option>

                            </select>

                        </div>


                        {{-- Resumo condição --}}

                        <div class="payment-condition-box">

                            <div>

                                <span class="payment-condition-label">
                                    Condição
                                </span>

                                <strong id="payment-condition-label">
                                    Pagamento total
                                </strong>

                            </div>


                            <div>

                                <span class="payment-condition-label">
                                    Saldo previsto
                                </span>

                                <strong id="remaining-value">
                                    0,00 MT
                                </strong>

                            </div>

                        </div>


                        {{-- Data vencimento --}}

                        <div
                            class="form-group"
                            id="due-date-group"
                            style="display:none;"
                        >

                            <label class="form-label">
                                Data prevista de pagamento
                            </label>

                            <input
                                type="date"
                                name="due_date"
                                id="due-date"
                                value="{{ old('due_date') }}"
                                class="form-control"
                            >

                        </div>


                        {{-- Método pagamento --}}

                        <div
                            class="form-group"
                            id="payment-method-group"
                        >

                            <label class="form-label">
                                Método de pagamento
                            </label>

                            <select
                                name="payment_method"
                                id="payment-method"
                                class="form-control"
                                onchange="togglePaymentFields()"
                                required
                            >

                                <option
                                    value="cash"
                                    @selected(
                                        old(
                                            'payment_method',
                                            'cash'
                                        ) === 'cash'
                                    )
                                >
                                    Dinheiro
                                </option>

                                <option
                                    value="mpesa"
                                    @selected(
                                        old('payment_method')
                                        === 'mpesa'
                                    )
                                >
                                    M-Pesa
                                </option>

                                <option
                                    value="emola"
                                    @selected(
                                        old('payment_method')
                                        === 'emola'
                                    )
                                >
                                    e-Mola
                                </option>

                                <option
                                    value="card"
                                    @selected(
                                        old('payment_method')
                                        === 'card'
                                    )
                                >
                                    Cartão
                                </option>

                                <option
                                    value="bank_transfer"
                                    @selected(
                                        old('payment_method')
                                        === 'bank_transfer'
                                    )
                                >
                                    Transferência
                                </option>

                                <option
                                    value="other"
                                    @selected(
                                        old('payment_method')
                                        === 'other'
                                    )
                                >
                                    Outro
                                </option>

                            </select>

                        </div>


                        {{-- Valor recebido --}}

                        <div
                            class="form-group"
                            id="amount-received-group"
                        >

                            <label
                                class="form-label"
                                id="amount-received-label"
                            >
                                Valor recebido
                            </label>

                            <input
                                type="number"
                                name="amount_received"
                                id="amount-received"
                                value="{{ old('amount_received') }}"
                                class="form-control"
                                min="0"
                                step="0.01"
                                oninput="calculatePaymentSummary()"
                            >

                            <div
                                id="amount-received-hint"
                                class="payment-hint"
                            >
                                Informe o valor entregue pelo cliente.
                            </div>

                        </div>


                        {{-- Troco --}}

                        <div
                            id="change-box"
                            class="payment-summary-box"
                        >

                            <span>
                                Troco
                            </span>

                            <strong id="change-value">
                                0,00 MT
                            </strong>

                        </div>


                        {{-- Saldo --}}

                        <div
                            id="debt-box"
                            class="
                                payment-summary-box
                                payment-debt-box
                            "
                            style="display:none;"
                        >

                            <span>
                                Saldo em dívida
                            </span>

                            <strong id="debt-value">
                                0,00 MT
                            </strong>

                        </div>


                        {{-- Referência --}}

                        <div
                            class="form-group"
                            id="payment-reference-group"
                        >

                            <label class="form-label">
                                Referência do pagamento
                            </label>

                            <input
                                type="text"
                                name="payment_reference"
                                value="{{ old(
                                    'payment_reference'
                                ) }}"
                                class="form-control"
                                placeholder="Opcional"
                            >

                        </div>


                        <button
                            type="submit"
                            id="complete-sale-button"
                            class="topbar-button"
                            style="
                                width:100%;
                                min-height:52px;
                                background:var(--primary);
                                color:white;
                                border-color:var(--primary);
                            "
                            disabled
                        >
                            Finalizar venda
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </aside>

</div>

@endsection


@section('styles')

.pos-grid {
    display:grid;
    grid-template-columns:
        minmax(0,1.5fr)
        minmax(380px,.75fr);
    gap:22px;
    align-items:start;
}

.pos-products {
    display:grid;
    grid-template-columns:
        repeat(3,minmax(0,1fr));
    gap:12px;
}

.pos-product {
    min-height:125px;
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
    border:1px solid var(--border);
    border-radius:14px;
    padding:16px;
    background:#fff;
    color:var(--text);
    text-align:left;
    cursor:pointer;
}

.pos-product:hover {
    border-color:var(--primary);
}

.pos-product:disabled {
    opacity:.45;
    cursor:not-allowed;
}

.pos-product strong {
    font-size:14px;
}

.pos-product span {
    color:var(--primary-dark);
    font-size:18px;
    font-weight:850;
}

.pos-product small {
    color:var(--muted);
}


/* =========================================================================
   IDENTIFICAÇÃO DE PRODUTOS / VARIANTES
============================================================================ */

.pos-product-meta {
    display:flex;
    align-items:center;
    gap:7px;
    flex-wrap:wrap;
}

.pos-product-meta small {
    color:var(--muted);
    font-size:11px;
}

.pos-product-badge {
    display:inline-flex !important;
    align-items:center;
    width:auto;
    padding:4px 7px;
    border-radius:999px;
    background:var(--primary-soft);
    color:var(--primary-dark) !important;
    font-size:10px !important;
    font-weight:800 !important;
    line-height:1;
}

.pos-product-sku {
    color:var(--muted);
    font-size:10px !important;
}


/* =========================================================================
   CARRINHO
============================================================================ */

.cart-item {
    padding:16px 0;
    border-bottom:1px solid var(--border);
}

.cart-item-header {
    display:flex;
    justify-content:space-between;
    gap:15px;
}

.cart-item-name {
    font-size:13px;
    font-weight:800;
}

.cart-item-price {
    font-size:13px;
    font-weight:800;
    text-align:right;
}

.cart-item-original-price {
    margin-top:3px;
    color:var(--muted);
    font-size:10px;
}

.cart-controls {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-top:10px;
}

.cart-quantity-controls {
    display:flex;
    align-items:center;
    gap:7px;
}

.cart-button {
    width:31px;
    height:31px;
    border:1px solid var(--border);
    border-radius:8px;
    background:#fff;
    cursor:pointer;
}

.cart-button:hover {
    border-color:var(--primary);
}

.cart-remove {
    border:0;
    background:transparent;
    color:#b42318;
    cursor:pointer;
    font-size:12px;
}


/* =========================================================================
   DESCONTO INDIVIDUAL
============================================================================ */

.cart-discount {
    margin-top:12px;
    padding:12px;
    border:1px solid var(--border);
    border-radius:10px;
    background:#fafcfb;
}

.cart-discount-title {
    margin-bottom:8px;
    color:var(--muted);
    font-size:10px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.05em;
}

.cart-discount-grid {
    display:grid;
    grid-template-columns:1fr .8fr;
    gap:8px;
}

.cart-discount-grid select,
.cart-discount-grid input {
    min-height:38px;
    height:38px;
    padding:0 10px;
    font-size:12px;
}

.cart-item-discount-summary {
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-top:9px;
    color:#8a5c00;
    font-size:11px;
}

.cart-item-discount-summary strong {
    color:#8a5c00;
}


/* =========================================================================
   DESCONTO GLOBAL
============================================================================ */

.pos-discount-section {
    margin-top:22px;
    padding:18px;
    border:1px solid var(--border);
    border-radius:12px;
    background:#fafcfb;
}

.discount-section-title {
    font-size:13px;
    font-weight:850;
}

.discount-section-description {
    margin-bottom:16px;
}

.discount-total-row {
    color:#8a5c00 !important;
}

.discount-total-row strong {
    color:#8a5c00;
}

.discount-total-final {
    padding-top:8px;
    border-top:1px dashed var(--border);
    font-weight:800;
}


/* =========================================================================
   TOTAIS E PAGAMENTO
============================================================================ */

.pos-totals {
    display:grid;
    gap:10px;
    margin-top:22px;
}

.pos-totals > div {
    display:flex;
    justify-content:space-between;
    gap:20px;
    color:var(--muted);
    font-size:13px;
}

.pos-grand-total {
    margin-top:5px;
    padding-top:14px;
    border-top:1px solid var(--border);
    color:var(--text) !important;
    font-size:20px !important;
}

.pos-payment-section {
    margin-top:22px;
    padding-top:20px;
    border-top:1px solid var(--border);
}

.payment-hint {
    margin-top:6px;
    color:var(--muted);
    font-size:11px;
    line-height:1.5;
}

.payment-condition-box {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin-bottom:20px;
    padding:14px;
    border:1px solid var(--border);
    border-radius:11px;
    background:#fafcfb;
}

.payment-condition-box > div {
    display:flex;
    flex-direction:column;
    gap:5px;
}

.payment-condition-label {
    color:var(--muted);
    font-size:10px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.payment-condition-box strong {
    font-size:13px;
}

.payment-summary-box {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:20px;
    margin-bottom:20px;
    padding:13px 14px;
    border-radius:10px;
    background:var(--primary-soft);
}

.payment-debt-box {
    background:#fff7e6;
    color:#8a5c00;
}

.payment-debt-box strong {
    color:#8a5c00;
}


@media (max-width:1100px) {

    .pos-grid {
        grid-template-columns:1fr;
    }

}

@media (max-width:760px) {

    .pos-products {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:480px) {

    .pos-products,
    .cart-discount-grid,
    .payment-condition-box {
        grid-template-columns:1fr;
    }

}

@endsection


@section('scripts')

<script>

    let cart = [];


    /*
    |--------------------------------------------------------------------------
    | Formatação
    |--------------------------------------------------------------------------
    */

    function formatMoney(value) {

        return new Intl.NumberFormat(
            'pt-PT',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(
            Number(value || 0)
        ) + ' MT';
    }


    function formatQuantity(value) {

        return Number(value)
            .toFixed(3)
            .replace(/0+$/, '')
            .replace(/\.$/, '');
    }


    function roundMoney(value) {

        return Math.round(
            (Number(value) + Number.EPSILON)
            * 100
        ) / 100;
    }


    /*
    |--------------------------------------------------------------------------
    | Carrinho
    |--------------------------------------------------------------------------
    */

    function addToCart(product) {

        const existing = cart.find(
            item => item.id === product.id
        );

        if (existing) {

            if (
                product.track_stock
                && existing.quantity + 1
                    > product.stock
            ) {
                alert('Stock insuficiente.');
                return;
            }

            existing.quantity += 1;

        } else {

            if (
                product.track_stock
                && product.stock < 1
            ) {
                alert('Produto sem stock.');
                return;
            }

            cart.push({
                ...product,
                quantity: 1,

                /*
                 * Desconto individual.
                 */
                discount_type: 'none',
                discount_value: 0
            });
        }

        renderCart();
    }


    function changeQuantity(
        productId,
        amount
    ) {

        const item = cart.find(
            item => item.id === productId
        );

        if (! item) {
            return;
        }

        const next =
            item.quantity + amount;

        if (next <= 0) {

            removeFromCart(productId);

            return;
        }

        if (
            item.track_stock
            && next > item.stock
        ) {

            alert('Stock insuficiente.');

            return;
        }

        item.quantity = next;

        /*
         * Se o desconto fixo ficar superior
         * ao valor da linha, será limitado
         * visualmente no cálculo.
         */

        renderCart();
    }


    function removeFromCart(productId) {

        cart = cart.filter(
            item => item.id !== productId
        );

        renderCart();
    }


    /*
    |--------------------------------------------------------------------------
    | Alterar desconto individual
    |--------------------------------------------------------------------------
    */

    function changeItemDiscountType(
        productId,
        value
    ) {

        const item = cart.find(
            item => item.id === productId
        );

        if (! item) {
            return;
        }

        item.discount_type =
            value;

        /*
         * Ao mudar tipo, limpamos o valor
         * para evitar, por exemplo,
         * passar 500 MT para 500%.
         */

        item.discount_value =
            0;

        renderCart();
    }


    function changeItemDiscountValue(
        productId,
        value
    ) {

        const item = cart.find(
            item => item.id === productId
        );

        if (! item) {
            return;
        }

        let numericValue =
            parseFloat(
                value || 0
            );

        if (
            ! Number.isFinite(
                numericValue
            )
            || numericValue < 0
        ) {
            numericValue = 0;
        }

        if (
            item.discount_type
            === 'percentage'
        ) {
            numericValue =
                Math.min(
                    numericValue,
                    100
                );
        }

        item.discount_value =
            numericValue;

        /*
         * Não chamamos renderCart aqui,
         * porque iria reconstruir o input
         * enquanto o utilizador escreve.
         *
         * Actualizamos apenas os valores.
         */
        updateTotals();
    }


    /*
    |--------------------------------------------------------------------------
    | Desconto individual calculado
    |--------------------------------------------------------------------------
    */

    function getItemDiscount(item) {

        const lineSubtotal =
            roundMoney(
                item.price
                * item.quantity
            );

        let discount = 0;

        if (
            item.discount_type
            === 'percentage'
        ) {

            const percentage =
                Math.min(
                    Math.max(
                        Number(
                            item.discount_value
                            || 0
                        ),
                        0
                    ),
                    100
                );

            discount =
                roundMoney(
                    lineSubtotal
                    * (
                        percentage
                        / 100
                    )
                );
        }


        if (
            item.discount_type
            === 'fixed'
        ) {

            discount =
                roundMoney(
                    Math.min(
                        Math.max(
                            Number(
                                item.discount_value
                                || 0
                            ),
                            0
                        ),
                        lineSubtotal
                    )
                );
        }


        return discount;
    }


    /*
    |--------------------------------------------------------------------------
    | Desconto global
    |--------------------------------------------------------------------------
    */

    function toggleDiscountFields() {

        const type =
            document.getElementById(
                'discount-type'
            ).value;

        const group =
            document.getElementById(
                'discount-value-group'
            );

        const input =
            document.getElementById(
                'discount-value'
            );

        const label =
            document.getElementById(
                'discount-value-label'
            );

        const hint =
            document.getElementById(
                'discount-hint'
            );


        if (
            type === 'none'
        ) {

            group.style.display =
                'none';

            input.value =
                '';

        } else {

            group.style.display =
                'block';


            if (
                type === 'percentage'
            ) {

                label.textContent =
                    'Percentagem de desconto (%)';

                hint.textContent =
                    'Percentagem aplicada depois dos descontos dos produtos.';

                input.max =
                    '100';

                input.placeholder =
                    'Ex.: 10';

            } else {

                label.textContent =
                    'Valor do desconto (MT)';

                hint.textContent =
                    'Valor aplicado depois dos descontos dos produtos.';

                input.removeAttribute(
                    'max'
                );

                input.placeholder =
                    'Ex.: 500';
            }
        }

        updateTotals();
    }


    /*
    |--------------------------------------------------------------------------
    | Totais
    |--------------------------------------------------------------------------
    */

    function getTotals() {

        /*
        |--------------------------------------------------------------------------
        | 1. Subtotal bruto
        |--------------------------------------------------------------------------
        */

        let subtotal = 0;

        cart.forEach(
            item => {

                subtotal +=
                    roundMoney(
                        item.price
                        * item.quantity
                    );
            }
        );

        subtotal =
            roundMoney(subtotal);


        /*
        |--------------------------------------------------------------------------
        | 2. Descontos individuais
        |--------------------------------------------------------------------------
        */

        let itemDiscountTotal = 0;

        const preparedItems =
            cart.map(
                item => {

                    const lineSubtotal =
                        roundMoney(
                            item.price
                            * item.quantity
                        );

                    const itemDiscount =
                        getItemDiscount(item);

                    const netAfterItemDiscount =
                        Math.max(
                            0,
                            roundMoney(
                                lineSubtotal
                                - itemDiscount
                            )
                        );

                    itemDiscountTotal +=
                        itemDiscount;

                    return {
                        item,
                        lineSubtotal,
                        itemDiscount,
                        netAfterItemDiscount
                    };
                }
            );


        itemDiscountTotal =
            roundMoney(
                itemDiscountTotal
            );


        /*
        |--------------------------------------------------------------------------
        | Base para desconto global
        |--------------------------------------------------------------------------
        */

        const subtotalAfterItemDiscounts =
            roundMoney(
                subtotal
                - itemDiscountTotal
            );


        /*
        |--------------------------------------------------------------------------
        | 3. Desconto global
        |--------------------------------------------------------------------------
        */

        const globalType =
            document.getElementById(
                'discount-type'
            )?.value
            ?? 'none';


        const globalValue =
            parseFloat(
                document.getElementById(
                    'discount-value'
                )?.value
                || 0
            );


        let globalDiscount = 0;


        if (
            globalType
            === 'percentage'
        ) {

            const percentage =
                Math.min(
                    Math.max(
                        globalValue,
                        0
                    ),
                    100
                );

            globalDiscount =
                roundMoney(
                    subtotalAfterItemDiscounts
                    * (
                        percentage
                        / 100
                    )
                );
        }


        if (
            globalType
            === 'fixed'
        ) {

            globalDiscount =
                roundMoney(
                    Math.min(
                        Math.max(
                            globalValue,
                            0
                        ),
                        subtotalAfterItemDiscounts
                    )
                );
        }


        /*
        |--------------------------------------------------------------------------
        | 4. Distribuir desconto global
        |--------------------------------------------------------------------------
        */

        let tax = 0;
        let total = 0;

        let distributedGlobalDiscount = 0;


        preparedItems.forEach(
            (prepared, index) => {

                let globalLineDiscount = 0;

                if (
                    globalDiscount > 0
                    && subtotalAfterItemDiscounts > 0
                ) {

                    if (
                        index
                        === preparedItems.length - 1
                    ) {

                        globalLineDiscount =
                            roundMoney(
                                globalDiscount
                                - distributedGlobalDiscount
                            );

                    } else {

                        const proportion =
                            prepared.netAfterItemDiscount
                            / subtotalAfterItemDiscounts;

                        globalLineDiscount =
                            roundMoney(
                                globalDiscount
                                * proportion
                            );

                        distributedGlobalDiscount =
                            roundMoney(
                                distributedGlobalDiscount
                                + globalLineDiscount
                            );
                    }
                }


                const totalLineDiscount =
                    roundMoney(
                        prepared.itemDiscount
                        + globalLineDiscount
                    );


                const taxable =
                    Math.max(
                        0,
                        roundMoney(
                            prepared.lineSubtotal
                            - totalLineDiscount
                        )
                    );


                const lineTax =
                    roundMoney(
                        taxable
                        * (
                            Number(
                                prepared.item.tax
                            )
                            / 100
                        )
                    );


                const lineTotal =
                    roundMoney(
                        taxable
                        + lineTax
                    );


                tax +=
                    lineTax;

                total +=
                    lineTotal;
            }
        );


        tax =
            roundMoney(tax);

        total =
            roundMoney(total);


        const discountTotal =
            roundMoney(
                itemDiscountTotal
                + globalDiscount
            );


        return {
            subtotal,
            itemDiscountTotal,
            subtotalAfterItemDiscounts,
            globalDiscount,
            discountTotal,
            tax,
            total
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Render carrinho
    |--------------------------------------------------------------------------
    */

    function renderCart() {

        const container =
            document.getElementById(
                'cart-items'
            );

        const inputContainer =
            document.getElementById(
                'cart-inputs'
            );

        const empty =
            document.getElementById(
                'cart-empty'
            );

        const completeButton =
            document.getElementById(
                'complete-sale-button'
            );


        container.innerHTML =
            '';

        inputContainer.innerHTML =
            '';


        if (
            cart.length === 0
        ) {

            empty.style.display =
                'block';

            completeButton.disabled =
                true;

        } else {

            empty.style.display =
                'none';

            completeButton.disabled =
                false;
        }


        cart.forEach(
            (item, index) => {

                const lineSubtotal =
                    roundMoney(
                        item.price
                        * item.quantity
                    );


                const itemDiscount =
                    getItemDiscount(item);


                const lineAfterDiscount =
                    roundMoney(
                        lineSubtotal
                        - itemDiscount
                    );


                const discountValueDisplay =
                    item.discount_value
                    || '';


                const discountInputStyle =
                    item.discount_type
                    === 'none'
                        ? 'display:none;'
                        : '';


                const discountLabel =
                    item.discount_type
                    === 'percentage'
                        ? '%'
                        : 'MT';


                container.innerHTML += `
                    <div class="cart-item">

                        <div class="cart-item-header">

                            <div>

                                <div class="cart-item-name">
                                    ${item.name}
                                </div>

                                <div class="cart-item-original-price">
                                    ${formatMoney(item.price)}
                                    por unidade
                                </div>

                            </div>

                            <div class="cart-item-price">

                                ${
                                    itemDiscount > 0
                                        ? `
                                            <div>
                                                ${formatMoney(
                                                    lineAfterDiscount
                                                )}
                                            </div>

                                            <div
                                                class="cart-item-original-price"
                                                style="
                                                    text-decoration:
                                                    line-through;
                                                "
                                            >
                                                ${formatMoney(
                                                    lineSubtotal
                                                )}
                                            </div>
                                        `
                                        : formatMoney(
                                            lineSubtotal
                                        )
                                }

                            </div>

                        </div>


                        <div class="cart-controls">

                            <div class="cart-quantity-controls">

                                <button
                                    type="button"
                                    class="cart-button"
                                    onclick="
                                        changeQuantity(
                                            ${item.id},
                                            -1
                                        )
                                    "
                                >
                                    −
                                </button>

                                <strong>
                                    ${formatQuantity(
                                        item.quantity
                                    )}
                                </strong>

                                <button
                                    type="button"
                                    class="cart-button"
                                    onclick="
                                        changeQuantity(
                                            ${item.id},
                                            1
                                        )
                                    "
                                >
                                    +
                                </button>

                            </div>


                            <button
                                type="button"
                                class="cart-remove"
                                onclick="
                                    removeFromCart(
                                        ${item.id}
                                    )
                                "
                            >
                                Remover
                            </button>

                        </div>


                        <div class="cart-discount">

                            <div class="cart-discount-title">
                                Desconto deste produto
                            </div>


                            <div class="cart-discount-grid">

                                <select
                                    class="form-control"
                                    onchange="
                                        changeItemDiscountType(
                                            ${item.id},
                                            this.value
                                        )
                                    "
                                >

                                    <option
                                        value="none"
                                        ${
                                            item.discount_type
                                            === 'none'
                                                ? 'selected'
                                                : ''
                                        }
                                    >
                                        Sem desconto
                                    </option>

                                    <option
                                        value="percentage"
                                        ${
                                            item.discount_type
                                            === 'percentage'
                                                ? 'selected'
                                                : ''
                                        }
                                    >
                                        Percentagem (%)
                                    </option>

                                    <option
                                        value="fixed"
                                        ${
                                            item.discount_type
                                            === 'fixed'
                                                ? 'selected'
                                                : ''
                                        }
                                    >
                                        Valor fixo (MT)
                                    </option>

                                </select>


                                <input
                                    type="number"
                                    class="form-control"
                                    min="0"
                                    ${
                                        item.discount_type
                                        === 'percentage'
                                            ? 'max="100"'
                                            : ''
                                    }
                                    step="0.01"
                                    value="${discountValueDisplay}"
                                    placeholder="
                                        ${
                                            item.discount_type
                                            === 'percentage'
                                                ? 'Ex.: 10'
                                                : 'Ex.: 100'
                                        }
                                    "
                                    style="
                                        ${discountInputStyle}
                                    "
                                    oninput="
                                        changeItemDiscountValue(
                                            ${item.id},
                                            this.value
                                        )
                                    "
                                >

                            </div>


                            ${
                                itemDiscount > 0
                                    ? `
                                        <div
                                            class="
                                                cart-item-discount-summary
                                            "
                                        >

                                            <span>
                                                Desconto
                                            </span>

                                            <strong>
                                                -
                                                ${formatMoney(
                                                    itemDiscount
                                                )}
                                            </strong>

                                        </div>
                                    `
                                    : ''
                            }

                        </div>

                    </div>
                `;


                /*
                |--------------------------------------------------------------------------
                | Inputs enviados ao Laravel
                |--------------------------------------------------------------------------
                */

                inputContainer.innerHTML += `
                    <input
                        type="hidden"
                        name="items[${index}][product_id]"
                        value="${item.id}"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][quantity]"
                        value="${item.quantity}"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][discount_type]"
                        value="${item.discount_type}"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][discount_value]"
                        value="${item.discount_value || 0}"
                    >
                `;
            }
        );


        updateTotals();
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar apenas os totais
    |--------------------------------------------------------------------------
    */

    function updateTotals() {

        const totals =
            getTotals();


        document.getElementById(
            'subtotal-value'
        ).textContent =
            formatMoney(
                totals.subtotal
            );


        document.getElementById(
            'tax-value'
        ).textContent =
            formatMoney(
                totals.tax
            );


        document.getElementById(
            'total-value'
        ).textContent =
            formatMoney(
                totals.total
            );


        /*
        |--------------------------------------------------------------------------
        | Desconto individual
        |--------------------------------------------------------------------------
        */

        const itemDiscountRow =
            document.getElementById(
                'item-discount-row'
            );


        if (
            totals.itemDiscountTotal > 0
        ) {

            itemDiscountRow.style.display =
                'flex';

            document.getElementById(
                'item-discount-value'
            ).textContent =
                '- '
                + formatMoney(
                    totals.itemDiscountTotal
                );

        } else {

            itemDiscountRow.style.display =
                'none';
        }


        /*
        |--------------------------------------------------------------------------
        | Desconto global
        |--------------------------------------------------------------------------
        */

        const globalDiscountRow =
            document.getElementById(
                'global-discount-row'
            );


        if (
            totals.globalDiscount > 0
        ) {

            globalDiscountRow.style.display =
                'flex';

            document.getElementById(
                'global-discount-value'
            ).textContent =
                '- '
                + formatMoney(
                    totals.globalDiscount
                );

        } else {

            globalDiscountRow.style.display =
                'none';
        }


        /*
        |--------------------------------------------------------------------------
        | Desconto total
        |--------------------------------------------------------------------------
        */

        const discountRow =
            document.getElementById(
                'discount-row'
            );


        if (
            totals.discountTotal > 0
        ) {

            discountRow.style.display =
                'flex';

            document.getElementById(
                'discount-total-value'
            ).textContent =
                '- '
                + formatMoney(
                    totals.discountTotal
                );

        } else {

            discountRow.style.display =
                'none';
        }


        /*
         * Actualizar inputs ocultos após
         * alteração de descontos individuais.
         */

        updateCartHiddenInputs();


        calculatePaymentSummary();
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar hidden inputs
    |--------------------------------------------------------------------------
    */

    function updateCartHiddenInputs() {

        const inputContainer =
            document.getElementById(
                'cart-inputs'
            );


        inputContainer.innerHTML =
            '';


        cart.forEach(
            (item, index) => {

                inputContainer.innerHTML += `
                    <input
                        type="hidden"
                        name="items[${index}][product_id]"
                        value="${item.id}"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][quantity]"
                        value="${item.quantity}"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][discount_type]"
                        value="${item.discount_type}"
                    >

                    <input
                        type="hidden"
                        name="items[${index}][discount_value]"
                        value="${item.discount_value || 0}"
                    >
                `;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Condição de pagamento
    |--------------------------------------------------------------------------
    */

    function togglePaymentType() {

        const type =
            document.getElementById(
                'payment-type'
            ).value;


        const amountGroup =
            document.getElementById(
                'amount-received-group'
            );


        const amountInput =
            document.getElementById(
                'amount-received'
            );


        const amountLabel =
            document.getElementById(
                'amount-received-label'
            );


        const amountHint =
            document.getElementById(
                'amount-received-hint'
            );


        const dueDateGroup =
            document.getElementById(
                'due-date-group'
            );


        const dueDateInput =
            document.getElementById(
                'due-date'
            );


        const paymentMethodGroup =
            document.getElementById(
                'payment-method-group'
            );


        const paymentMethod =
            document.getElementById(
                'payment-method'
            );


        const referenceGroup =
            document.getElementById(
                'payment-reference-group'
            );


        const customerHint =
            document.getElementById(
                'customer-credit-hint'
            );


        const conditionLabel =
            document.getElementById(
                'payment-condition-label'
            );


        if (
            type === 'full'
        ) {

            conditionLabel.textContent =
                'Pagamento total';


            dueDateGroup.style.display =
                'none';

            dueDateInput.required =
                false;


            paymentMethodGroup.style.display =
                'block';

            paymentMethod.required =
                true;


            referenceGroup.style.display =
                'block';


            customerHint.style.display =
                'none';


            amountLabel.textContent =
                'Valor recebido';

            amountHint.textContent =
                'Informe o valor entregue pelo cliente.';


            amountGroup.style.display =
                'block';
        }


        if (
            type === 'partial'
        ) {

            conditionLabel.textContent =
                'Pagamento parcial';


            dueDateGroup.style.display =
                'block';

            dueDateInput.required =
                true;


            paymentMethodGroup.style.display =
                'block';

            paymentMethod.required =
                true;


            referenceGroup.style.display =
                'block';


            customerHint.style.display =
                'block';


            amountGroup.style.display =
                'block';


            amountLabel.textContent =
                'Valor do depósito';

            amountHint.textContent =
                'Informe o valor pago agora pelo cliente.';
        }


        if (
            type === 'credit'
        ) {

            conditionLabel.textContent =
                'Venda a crédito';


            dueDateGroup.style.display =
                'block';

            dueDateInput.required =
                true;


            paymentMethodGroup.style.display =
                'none';

            paymentMethod.required =
                false;


            referenceGroup.style.display =
                'none';


            customerHint.style.display =
                'block';


            amountGroup.style.display =
                'none';


            amountInput.value =
                '0';
        }


        togglePaymentFields();

        calculatePaymentSummary();
    }


    /*
    |--------------------------------------------------------------------------
    | Método de pagamento
    |--------------------------------------------------------------------------
    */

    function togglePaymentFields() {

        const type =
            document.getElementById(
                'payment-type'
            ).value;


        const method =
            document.getElementById(
                'payment-method'
            ).value;


        const amountGroup =
            document.getElementById(
                'amount-received-group'
            );


        const amountInput =
            document.getElementById(
                'amount-received'
            );


        if (
            type === 'credit'
        ) {

            amountGroup.style.display =
                'none';

            return;
        }


        if (
            type === 'full'
            && method !== 'cash'
        ) {

            amountGroup.style.display =
                'none';

            amountInput.value =
                '';

        } else {

            amountGroup.style.display =
                'block';
        }


        calculatePaymentSummary();
    }


    /*
    |--------------------------------------------------------------------------
    | Resumo financeiro
    |--------------------------------------------------------------------------
    */

    function calculatePaymentSummary() {

        const totals =
            getTotals();


        const type =
            document.getElementById(
                'payment-type'
            ).value;


        const method =
            document.getElementById(
                'payment-method'
            ).value;


        const input =
            document.getElementById(
                'amount-received'
            );


        const received =
            parseFloat(
                input.value || 0
            );


        let applied = 0;
        let change = 0;
        let debt = 0;


        if (
            type === 'full'
        ) {

            if (
                method === 'cash'
            ) {

                applied =
                    Math.min(
                        received,
                        totals.total
                    );


                change =
                    Math.max(
                        0,
                        received
                        - totals.total
                    );


                debt =
                    Math.max(
                        0,
                        totals.total
                        - applied
                    );

            } else {

                applied =
                    totals.total;

                change =
                    0;

                debt =
                    0;
            }
        }


        if (
            type === 'partial'
        ) {

            applied =
                Math.min(
                    received,
                    totals.total
                );


            change =
                0;


            debt =
                Math.max(
                    0,
                    totals.total
                    - applied
                );
        }


        if (
            type === 'credit'
        ) {

            applied =
                0;

            change =
                0;

            debt =
                totals.total;
        }


        change =
            roundMoney(change);


        debt =
            roundMoney(debt);


        document.getElementById(
            'change-value'
        ).textContent =
            formatMoney(change);


        document.getElementById(
            'remaining-value'
        ).textContent =
            formatMoney(debt);


        document.getElementById(
            'debt-value'
        ).textContent =
            formatMoney(debt);


        const changeBox =
            document.getElementById(
                'change-box'
            );


        const debtBox =
            document.getElementById(
                'debt-box'
            );


        if (
            type === 'full'
            && method === 'cash'
        ) {

            changeBox.style.display =
                'flex';

        } else {

            changeBox.style.display =
                'none';
        }


        if (
            type === 'partial'
            || type === 'credit'
        ) {

            debtBox.style.display =
                'flex';

        } else {

            debtBox.style.display =
                'none';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Inicialização
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            toggleDiscountFields();

            renderCart();

            togglePaymentType();
        }
    );

</script>

@endsection