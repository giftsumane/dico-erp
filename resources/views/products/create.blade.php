@extends('layouts.app')

@section('title', 'Novo Produto | DICO')
@section('topbar-title', 'Produtos')

@section('content')

@php
    $hasInventory = $activeCompany->hasFeature('inventory');
    $hasVariants = $activeCompany->hasFeature('variants');
    $hasStockBatches =
        $hasInventory
        && $activeCompany->hasFeature('stock_batches');

    $selectedType = old(
        'product_type',
        \App\Models\Product::TYPE_SIMPLE
    );

    $selectedInventoryMode = old(
        'inventory_mode',
        $hasInventory
            ? \App\Models\Product::INVENTORY_SIMPLE
            : \App\Models\Product::INVENTORY_NONE
    );

    $selectedAttributeIds = collect(
        old('attribute_ids', [])
    )
        ->map(fn ($id) => (int) $id)
        ->all();

    $selectedVariantOptionIds = collect(
        old('variant_option_ids', [])
    )
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp


<div class="page-heading">

    <h1>Novo produto</h1>

    <p>
        Registe um novo produto ou serviço na
        {{ $activeCompany->name }}.
    </p>

</div>


<form
    method="POST"
    action="{{ route('products.store') }}"
>
    @csrf


    {{-- ================================================================
         TIPO DE PRODUTO
    ================================================================= --}}

    <div
        class="panel"
        style="margin-bottom:20px;"
    >

        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    Tipo
                </h2>

                <p class="panel-subtitle">
                    Defina o tipo de item que está a registar.
                </p>
            </div>
        </div>


        <div class="panel-body">

            <div class="product-type-grid">

                {{-- Produto simples --}}

                <label
                    class="product-type-option"
                    data-type-card="simple"
                >

                    <input
                        type="radio"
                        name="product_type"
                        value="{{ \App\Models\Product::TYPE_SIMPLE }}"
                        @checked(
                            $selectedType
                            === \App\Models\Product::TYPE_SIMPLE
                        )
                    >

                    <div>
                        <strong>
                            Produto físico
                        </strong>

                        <span>
                            Item vendido individualmente, com ou sem
                            controlo de stock.
                        </span>
                    </div>

                </label>


                {{-- Produto com variantes --}}

                @if ($hasVariants)

                    <label
                        class="product-type-option"
                        data-type-card="variable"
                    >

                        <input
                            type="radio"
                            name="product_type"
                            value="{{ \App\Models\Product::TYPE_VARIABLE }}"
                            @checked(
                                $selectedType
                                === \App\Models\Product::TYPE_VARIABLE
                            )
                        >

                        <div>
                            <strong>
                                Produto com variantes
                            </strong>

                            <span>
                                Produto com opções como cor, tamanho,
                                capacidade ou material.
                            </span>
                        </div>

                    </label>

                @endif


                {{-- Serviço --}}

                <label
                    class="product-type-option"
                    data-type-card="service"
                >

                    <input
                        type="radio"
                        name="product_type"
                        value="{{ \App\Models\Product::TYPE_SERVICE }}"
                        @checked(
                            $selectedType
                            === \App\Models\Product::TYPE_SERVICE
                        )
                    >

                    <div>
                        <strong>
                            Serviço
                        </strong>

                        <span>
                            Serviço vendido sem movimentação de stock.
                        </span>
                    </div>

                </label>

            </div>

            @error('product_type')
                <div
                    class="error-message"
                    style="margin-top:10px;"
                >
                    {{ $message }}
                </div>
            @enderror

        </div>

    </div>


    {{-- ================================================================
         ATRIBUTOS DAS VARIANTES
    ================================================================= --}}

    @if ($hasVariants)

        <div
            id="variant-attributes-panel"
            class="panel"
            style="margin-bottom:20px;"
        >

            <div class="panel-header">

                <div>

                    <h2 class="panel-title">
                        Atributos das variantes
                    </h2>

                    <p class="panel-subtitle">
                        Seleccione as características que irão diferenciar
                        as variantes deste produto.
                    </p>

                </div>

            </div>


            <div class="panel-body">

                @if ($attributes->isEmpty())

                    <div class="empty-state">

                        <strong>
                            Ainda não existem atributos activos.
                        </strong>

                        <div style="margin-top:8px;">
                            Crie atributos como Cor, Tamanho,
                            Capacidade ou Material.
                        </div>

                        <div style="margin-top:16px;">

                            <a
                                href="{{ route('attributes.create') }}"
                                class="topbar-button"
                            >
                                Criar atributo
                            </a>

                        </div>

                    </div>

                @else

                    <div class="attribute-grid">

                        @foreach ($attributes as $attribute)

                            @php
                                $attributeSelected = in_array(
                                    $attribute->id,
                                    $selectedAttributeIds,
                                    true
                                );
                            @endphp

                            <div
                                class="attribute-option"
                                data-attribute-card
                            >

                                <label class="attribute-heading">

                                    <input
                                        type="checkbox"
                                        name="attribute_ids[]"
                                        value="{{ $attribute->id }}"
                                        data-attribute-toggle
                                        @checked($attributeSelected)
                                    >

                                    <div>

                                        <strong>
                                            {{ $attribute->name }}
                                        </strong>

                                        <span>
                                            Seleccione os valores que irão
                                            gerar variantes.
                                        </span>

                                    </div>

                                </label>


                                <div
                                    class="attribute-values"
                                    data-attribute-values
                                >

                                    @forelse ($attribute->values as $value)

                                        <label class="variant-value-option">

                                            <input
                                                type="checkbox"
                                                name="variant_option_ids[]"
                                                value="{{ $value->id }}"
                                                data-variant-option
                                                @checked(
                                                    in_array(
                                                        $value->id,
                                                        $selectedVariantOptionIds,
                                                        true
                                                    )
                                                )
                                            >

                                            <span>
                                                {{ $value->value }}
                                            </span>

                                        </label>

                                    @empty

                                        <div class="attribute-empty-values">
                                            Sem valores activos.
                                        </div>

                                    @endforelse

                                </div>

                            </div>

                        @endforeach

                    </div>

                @endif


                @error('attribute_ids')
                    <div
                        class="error-message"
                        style="margin-top:12px;"
                    >
                        {{ $message }}
                    </div>
                @enderror

                @error('attribute_ids.*')
                    <div
                        class="error-message"
                        style="margin-top:12px;"
                    >
                        {{ $message }}
                    </div>
                @enderror

                @error('variant_option_ids')
                    <div
                        class="error-message"
                        style="margin-top:12px;"
                    >
                        {{ $message }}
                    </div>
                @enderror

                @error('variant_option_ids.*')
                    <div
                        class="error-message"
                        style="margin-top:12px;"
                    >
                        {{ $message }}
                    </div>
                @enderror

            </div>

        </div>

    @endif


    <div class="product-form-grid">


        {{-- ============================================================
             INFORMAÇÃO PRINCIPAL
        ============================================================= --}}

        <div class="panel">

            <div class="panel-header">

                <div>

                    <h2 class="panel-title">
                        Informação
                    </h2>

                    <p class="panel-subtitle">
                        Dados principais para identificação.
                    </p>

                </div>

            </div>


            <div class="panel-body">

                <div class="form-group">

                    <label class="form-label">
                        Nome *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control"
                        placeholder="Ex.: Camisa Classic"
                        required
                        autofocus
                    >

                    @error('name')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label class="form-label">
                            SKU
                        </label>

                        <input
                            type="text"
                            name="sku"
                            value="{{ old('sku') }}"
                            class="form-control"
                            placeholder="Ex.: CAM-001"
                        >

                        @error('sku')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Código de barras
                        </label>

                        <input
                            type="text"
                            name="barcode"
                            value="{{ old('barcode') }}"
                            class="form-control"
                            placeholder="Opcional"
                        >

                        @error('barcode')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">

                        <label class="form-label">
                            Categoria
                        </label>

                        <select
                            name="category_id"
                            class="form-control"
                        >

                            <option value="">
                                Seleccionar categoria
                            </option>

                            @foreach ($categories as $category)

                                <option
                                    value="{{ $category->id }}"
                                    @selected(
                                        old('category_id')
                                        == $category->id
                                    )
                                >
                                    {{ $category->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Marca
                        </label>

                        <select
                            name="brand_id"
                            class="form-control"
                        >

                            <option value="">
                                Seleccionar marca
                            </option>

                            @foreach ($brands as $brand)

                                <option
                                    value="{{ $brand->id }}"
                                    @selected(
                                        old('brand_id')
                                        == $brand->id
                                    )
                                >
                                    {{ $brand->name }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Unidade
                    </label>

                    <select
                        name="unit_id"
                        class="form-control"
                    >

                        <option value="">
                            Seleccionar unidade
                        </option>

                        @foreach ($units as $unit)

                            <option
                                value="{{ $unit->id }}"
                                @selected(
                                    old('unit_id')
                                    == $unit->id
                                )
                            >
                                {{ $unit->name }}
                                ({{ $unit->symbol }})
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Descrição
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        style="
                            min-height:120px;
                            padding-top:14px;
                        "
                        placeholder="Descrição opcional"
                    >{{ old('description') }}</textarea>

                </div>

            </div>

        </div>


        {{-- ============================================================
             PREÇOS E STOCK
        ============================================================= --}}

        <div>

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <h2 class="panel-title">
                            Preços
                        </h2>

                        <p class="panel-subtitle">
                            Valores base em meticais.
                        </p>

                    </div>

                </div>


                <div class="panel-body">

                    <div class="form-group">

                        <label class="form-label">
                            Preço de compra
                        </label>

                        <input
                            type="number"
                            name="purchase_price"
                            value="{{ old('purchase_price', 0) }}"
                            class="form-control"
                            step="0.01"
                            min="0"
                        >

                        @error('purchase_price')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            Preço de venda *
                        </label>

                        <input
                            type="number"
                            name="sale_price"
                            value="{{ old('sale_price', 0) }}"
                            class="form-control"
                            step="0.01"
                            min="0"
                            required
                        >

                        @error('sale_price')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>


                    <div class="form-group">

                        <label class="form-label">
                            IVA (%)
                        </label>

                        <input
                            type="number"
                            name="tax_rate"
                            value="{{ old('tax_rate', 0) }}"
                            class="form-control"
                            step="0.01"
                            min="0"
                            max="100"
                        >

                        @error('tax_rate')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                </div>

            </div>


            @if ($hasInventory)

                <div
                    id="stock-panel"
                    class="panel"
                    style="margin-top:20px;"
                >

                    <div class="panel-header">

                        <div>

                            <h2 class="panel-title">
                                Stock
                            </h2>

                            <p class="panel-subtitle">
                                Definições de controlo.
                            </p>

                        </div>

                    </div>


                    <div class="panel-body">

                        <div class="form-group">

                            <label class="form-label">
                                Modo de inventário
                            </label>

                            <div class="inventory-mode-grid">

                                <label class="inventory-mode-option">
                                    <input
                                        type="radio"
                                        name="inventory_mode"
                                        value="{{ \App\Models\Product::INVENTORY_NONE }}"
                                        @checked(
                                            $selectedInventoryMode
                                            === \App\Models\Product::INVENTORY_NONE
                                        )
                                    >
                                    <div>
                                        <strong>Sem controlo de stock</strong>
                                        <span>
                                            O item pode ser vendido sem movimentar stock.
                                        </span>
                                    </div>
                                </label>

                                <label class="inventory-mode-option">
                                    <input
                                        type="radio"
                                        name="inventory_mode"
                                        value="{{ \App\Models\Product::INVENTORY_SIMPLE }}"
                                        @checked(
                                            $selectedInventoryMode
                                            === \App\Models\Product::INVENTORY_SIMPLE
                                        )
                                    >
                                    <div>
                                        <strong>Stock simples</strong>
                                        <span>
                                            Uma quantidade total por produto e loja.
                                        </span>
                                    </div>
                                </label>

                                @if ($hasStockBatches)

                                    <label class="inventory-mode-option">
                                        <input
                                            type="radio"
                                            name="inventory_mode"
                                            value="{{ \App\Models\Product::INVENTORY_BATCH }}"
                                            @checked(
                                                $selectedInventoryMode
                                                === \App\Models\Product::INVENTORY_BATCH
                                            )
                                        >
                                        <div>
                                            <strong>Stock por lotes</strong>
                                            <span>
                                                Controla quantidades separadas por lote,
                                                remessa ou viagem.
                                            </span>
                                        </div>
                                    </label>

                                @endif

                            </div>

                            @error('inventory_mode')
                                <div class="error-message" style="margin-top:10px;">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>


                        <div
                            id="minimum-stock-group"
                            class="form-group"
                        >

                            <label class="form-label">
                                Stock mínimo
                            </label>

                            <input
                                id="minimum_stock"
                                type="number"
                                name="minimum_stock"
                                value="{{ old('minimum_stock', 0) }}"
                                class="form-control"
                                step="0.001"
                                min="0"
                            >

                        </div>


                        <div
                            id="batch-help"
                            class="inventory-help"
                            style="display:none;"
                        >
                            <strong>Stock por lotes</strong>
                            <span>
                                As entradas deste produto serão associadas a lotes.
                                O stock total continuará agregado por loja, mas cada
                                lote manterá a sua própria quantidade e custo.
                            </span>
                        </div>

                    </div>

                </div>

            @endif

        </div>

    </div>


    <div
        style="
            display:flex;
            justify-content:flex-end;
            gap:10px;
            margin-top:22px;
        "
    >

        <a
            href="{{ route('products.index') }}"
            class="topbar-button"
        >
            Cancelar
        </a>

        <button
            type="submit"
            class="topbar-button"
            style="
                background:var(--primary);
                color:white;
                border-color:var(--primary);
            "
        >
            Guardar
        </button>

    </div>

</form>


<style>

    .product-type-grid {
        display:grid;
        grid-template-columns:
            repeat(auto-fit, minmax(240px, 1fr));
        gap:14px;
    }

    .product-type-option {
        display:flex;
        gap:14px;
        padding:18px;
        border:1px solid var(--border);
        border-radius:12px;
        cursor:pointer;
        transition:.2s ease;
        background:#fff;
    }

    .product-type-option:hover {
        border-color:var(--primary);
    }

    .product-type-option:has(input:checked) {
        border-color:var(--primary);
        background:var(--primary-soft);
    }

    .product-type-option input {
        margin-top:3px;
        accent-color:var(--primary);
    }

    .product-type-option strong {
        display:block;
        margin-bottom:5px;
    }

    .product-type-option span {
        display:block;
        color:var(--muted);
        font-size:13px;
        line-height:1.5;
    }

    .inventory-mode-grid {
        display:grid;
        gap:10px;
    }

    .inventory-mode-option {
        display:flex;
        gap:12px;
        padding:14px;
        border:1px solid var(--border);
        border-radius:12px;
        cursor:pointer;
        background:#fff;
    }

    .inventory-mode-option:has(input:checked) {
        border-color:var(--primary);
        background:var(--primary-soft);
    }

    .inventory-mode-option input {
        margin-top:3px;
        accent-color:var(--primary);
    }

    .inventory-mode-option strong,
    .inventory-mode-option span {
        display:block;
    }

    .inventory-mode-option span,
    .inventory-help span {
        margin-top:4px;
        color:var(--muted);
        font-size:12px;
        line-height:1.5;
    }

    .inventory-help {
        padding:14px;
        border:1px solid var(--border);
        border-radius:12px;
        background:var(--primary-soft);
    }

    .inventory-help strong,
    .inventory-help span {
        display:block;
    }

    .attribute-grid {
        display:grid;
        grid-template-columns:
            repeat(auto-fit, minmax(240px, 1fr));
        gap:12px;
    }

    .attribute-option {
        display:block;
        padding:16px;
        border:1px solid var(--border);
        border-radius:12px;
        background:#fff;
        transition:.2s ease;
    }

    .attribute-option.is-selected {
        border-color:var(--primary);
        background:var(--primary-soft);
    }

    .attribute-heading {
        display:flex;
        align-items:flex-start;
        gap:12px;
        cursor:pointer;
    }

    .attribute-heading input,
    .variant-value-option input {
        margin-top:3px;
        accent-color:var(--primary);
    }

    .attribute-heading strong {
        display:block;
        margin-bottom:5px;
    }

    .attribute-heading span {
        display:block;
        color:var(--muted);
        font-size:12px;
        line-height:1.55;
    }

    .attribute-values {
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-top:14px;
        padding-top:14px;
        border-top:1px solid var(--border);
    }

    .variant-value-option {
        display:flex;
        align-items:center;
        gap:7px;
        padding:8px 10px;
        border:1px solid var(--border);
        border-radius:999px;
        background:#fff;
        cursor:pointer;
        font-size:12px;
    }

    .variant-value-option:has(input:checked) {
        border-color:var(--primary);
        background:#fff;
    }

    .variant-value-option input:disabled + span {
        opacity:.45;
    }

    .attribute-empty-values {
        color:var(--muted);
        font-size:12px;
    }

</style>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const typeInputs = document.querySelectorAll(
        'input[name="product_type"]'
    );

    const stockPanel =
        document.getElementById('stock-panel');

    const attributesPanel =
        document.getElementById(
            'variant-attributes-panel'
        );

    const inventoryModeInputs =
        document.querySelectorAll(
            'input[name="inventory_mode"]'
        );

    const minimumStock =
        document.getElementById('minimum_stock');

    const minimumStockGroup =
        document.getElementById('minimum-stock-group');

    const batchHelp =
        document.getElementById('batch-help');


    function updateProductType() {

        const selected =
            document.querySelector(
                'input[name="product_type"]:checked'
            );

        if (!selected) {
            return;
        }

        const type = selected.value;


        if (stockPanel) {
            stockPanel.style.display =
                type === 'simple'
                    ? 'block'
                    : 'none';
        }


        if (attributesPanel) {
            attributesPanel.style.display =
                type === 'variable'
                    ? 'block'
                    : 'none';
        }


        if (type !== 'simple') {

            const noneMode = document.querySelector(
                'input[name="inventory_mode"][value="none"]'
            );

            if (noneMode) {
                noneMode.checked = true;
            }

            if (minimumStock) {
                minimumStock.value = 0;
            }
        }

        updateInventoryMode();

    }


    function updateInventoryMode() {

        const selectedType =
            document.querySelector(
                'input[name="product_type"]:checked'
            );

        const selectedMode =
            document.querySelector(
                'input[name="inventory_mode"]:checked'
            );

        const isSimple =
            selectedType
            && selectedType.value === 'simple';

        const mode =
            selectedMode
                ? selectedMode.value
                : 'none';

        const controlsStock =
            isSimple
            && mode !== 'none';

        if (minimumStockGroup) {
            minimumStockGroup.style.display =
                controlsStock
                    ? 'block'
                    : 'none';
        }

        if (!controlsStock && minimumStock) {
            minimumStock.value = 0;
        }

        if (batchHelp) {
            batchHelp.style.display =
                isSimple && mode === 'batch'
                    ? 'block'
                    : 'none';
        }
    }


    function updateAttributeCard(card) {

        const toggle = card.querySelector(
            '[data-attribute-toggle]'
        );

        const options = card.querySelectorAll(
            '[data-variant-option]'
        );

        if (!toggle) {
            return;
        }

        card.classList.toggle(
            'is-selected',
            toggle.checked
        );

        options.forEach(function (option) {
            option.disabled = !toggle.checked;

            if (!toggle.checked) {
                option.checked = false;
            }
        });
    }


    document
        .querySelectorAll('[data-attribute-card]')
        .forEach(function (card) {

            const toggle = card.querySelector(
                '[data-attribute-toggle]'
            );

            updateAttributeCard(card);

            if (toggle) {
                toggle.addEventListener(
                    'change',
                    function () {
                        updateAttributeCard(card);
                    }
                );
            }

        });


    typeInputs.forEach(function (input) {

        input.addEventListener(
            'change',
            updateProductType
        );

    });


    inventoryModeInputs.forEach(function (input) {

        input.addEventListener(
            'change',
            updateInventoryMode
        );

    });


    updateProductType();
    updateInventoryMode();

});

</script>

@endsection
