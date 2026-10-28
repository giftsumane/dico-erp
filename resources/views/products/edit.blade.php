@extends('layouts.app')

@section('title', 'Editar Produto | DICO')
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
        $product->product_type
            ?: \App\Models\Product::TYPE_SIMPLE
    );

    $selectedInventoryMode = old(
        'inventory_mode',
        $product->inventory_mode
            ?: \App\Models\Product::INVENTORY_NONE
    );

    $selectedAttributeIds = collect(
        old(
            'attribute_ids',
            $product->attributes->pluck('id')->all()
        )
    )
        ->map(fn ($id) => (int) $id)
        ->all();

    $selectedVariantOptionIds = collect(
        old(
            'variant_option_ids',
            $product->variantOptions->pluck('id')->all()
        )
    )
        ->map(fn ($id) => (int) $id)
        ->all();
@endphp


<div class="page-heading">

    <h1>
        Editar produto
    </h1>

    <p>
        Actualize os dados de
        <strong>{{ $product->name }}</strong>.
    </p>

</div>


<form
    method="POST"
    action="{{ route('products.update', $product) }}"
>

    @csrf
    @method('PUT')


    {{-- ================================================================
         TIPO
    ================================================================= --}}

    <div
        class="panel"
        data-validation-section
        style="
            max-width:950px;
            margin-bottom:20px;
        "
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Tipo
                </h2>

                <p class="panel-subtitle">
                    Classificação principal do item.
                </p>

            </div>

        </div>


        <div class="panel-body">

            <div class="product-type-grid">

                {{-- Produto simples --}}

                <label class="product-type-option">

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
                            Produto vendável com ou sem
                            controlo de stock.
                        </span>

                    </div>

                </label>


                {{-- Produto com variantes --}}

                @if ($hasVariants)

                    <label class="product-type-option">

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
                                Produto principal com opções como
                                cor, tamanho ou capacidade.
                            </span>

                        </div>

                    </label>

                @endif


                {{-- Serviço --}}

                <label class="product-type-option">

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
                            Serviço vendido sem controlo
                            de stock.
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
            data-validation-section
            style=
                max-width:950px;
                margin-bottom:20px;
            "
        >

            <div class="panel-header">

                <div>

                    <h2 class="panel-title">
                        Atributos das variantes
                    </h2>

                    <p class="panel-subtitle">
                        Defina as características utilizadas
                        por este produto.
                    </p>

                </div>

            </div>


            <div class="panel-body">

                @if ($attributes->isEmpty())

                    <div class="empty-state">

                        Ainda não existem atributos activos.

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


    <div
        class="panel"
        style="max-width:950px;"
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Dados do produto
                </h2>

                <p class="panel-subtitle">
                    Preços, classificação, IVA e configurações.
                </p>

            </div>

        </div>


        <div class="panel-body">

            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Nome *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $product->name) }}"
                        class="form-control"
                        required
                    >

                    @error('name')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        SKU
                    </label>

                    <input
                        type="text"
                        name="sku"
                        value="{{ old('sku', $product->sku) }}"
                        class="form-control"
                    >

                    @error('sku')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Código de barras
                    </label>

                    <input
                        type="text"
                        name="barcode"
                        value="{{ old(
                            'barcode',
                            $product->barcode
                        ) }}"
                        class="form-control"
                    >

                    @error('barcode')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Categoria
                    </label>

                    <select
                        name="category_id"
                        class="form-control"
                    >

                        <option value="">
                            Sem categoria
                        </option>

                        @foreach ($categories as $category)

                            <option
                                value="{{ $category->id }}"
                                @selected(
                                    old(
                                        'category_id',
                                        $product->category_id
                                    )
                                    == $category->id
                                )
                            >
                                {{ $category->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('category_id')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Marca
                    </label>

                    <select
                        name="brand_id"
                        class="form-control"
                    >

                        <option value="">
                            Sem marca
                        </option>

                        @foreach ($brands as $brand)

                            <option
                                value="{{ $brand->id }}"
                                @selected(
                                    old(
                                        'brand_id',
                                        $product->brand_id
                                    )
                                    == $brand->id
                                )
                            >
                                {{ $brand->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('brand_id')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

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
                            Sem unidade
                        </option>

                        @foreach ($units as $unit)

                            <option
                                value="{{ $unit->id }}"
                                @selected(
                                    old(
                                        'unit_id',
                                        $product->unit_id
                                    )
                                    == $unit->id
                                )
                            >
                                {{ $unit->name }}
                                ({{ $unit->symbol }})
                            </option>

                        @endforeach

                    </select>

                    @error('unit_id')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Descrição
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    style="
                        min-height:110px;
                        padding-top:14px;
                    "
                >{{ old(
                    'description',
                    $product->description
                ) }}</textarea>

                @error('description')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Preço de compra
                    </label>

                    <input
                        type="number"
                        name="purchase_price"
                        value="{{ old(
                            'purchase_price',
                            $product->purchase_price
                        ) }}"
                        class="form-control"
                        min="0"
                        step="0.01"
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
                        value="{{ old(
                            'sale_price',
                            $product->sale_price
                        ) }}"
                        class="form-control"
                        min="0"
                        step="0.01"
                        required
                    >

                    @error('sale_price')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        IVA (%)
                    </label>

                    <input
                        type="number"
                        name="tax_rate"
                        value="{{ old(
                            'tax_rate',
                            $product->tax_rate
                        ) }}"
                        class="form-control"
                        min="0"
                        max="100"
                        step="0.01"
                    >

                    @error('tax_rate')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                @if ($hasInventory)

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
                            value="{{ old(
                                'minimum_stock',
                                $product->minimum_stock
                            ) }}"
                            class="form-control"
                            min="0"
                            step="0.001"
                        >

                        @error('minimum_stock')
                            <div class="error-message">
                                {{ $message }}
                            </div>
                        @enderror

                    </div>

                @endif

            </div>


            <div
                style="
                    display:grid;
                    gap:14px;
                    margin-top:8px;
                    padding:18px;
                    border:1px solid var(--border);
                    border-radius:12px;
                "
            >

                @if ($hasInventory)

                    <div
                        id="inventory-mode-control"
                        data-validation-section
                    >

                        <div class="form-label" style="margin-bottom:10px;">
                            Modo de inventário
                        </div>

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
                                    <span>Não movimenta stock.</span>
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
                                    <span>Quantidade total por produto e loja.</span>
                                </div>
                            </label>

                            @if (
                                $hasStockBatches
                                || $product->inventory_mode
                                    === \App\Models\Product::INVENTORY_BATCH
                            )

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
                                            Quantidades separadas por lote,
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

                        <div
                            id="batch-help"
                            class="inventory-help"
                            style="display:none; margin-top:10px;"
                        >
                            <strong>Stock por lotes</strong>
                            <span>
                                A quantidade total continuará em stock, enquanto
                                cada lote manterá o seu saldo individual.
                            </span>
                        </div>

                    </div>

                @endif


                <label class="checkbox-control">

                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        @checked(
                            old(
                                'active',
                                $product->active
                            )
                        )
                    >

                    <div>

                        <strong>
                            Item activo
                        </strong>

                        <span>
                            Itens inactivos deixam de aparecer
                            no ponto de venda.
                        </span>

                    </div>

                </label>

                @error('active')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:28px;
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
                        color:#fff;
                        border-color:var(--primary);
                    "
                >
                    Guardar alterações
                </button>

            </div>

        </div>

    </div>



</form>


{{-- ================================================================
         GERAÇÃO DE VARIANTES
    ================================================================= --}}

    @if (
        $hasVariants
        && $product->isVariable()
    )

        <div
            class="panel"
            style="
                max-width:950px;
                margin-top:20px;
            "
        >

            <div class="panel-header">

                <div>

                    <h2 class="panel-title">
                        Variantes
                    </h2>

                    <p class="panel-subtitle">
                        Gere automaticamente as combinações dos valores
                        seleccionados nos atributos deste produto.
                    </p>

                </div>

            </div>


            <div class="panel-body">

                @error('variants')
                    <div
                        class="error-message"
                        style="margin-bottom:16px;"
                    >
                        {{ $message }}
                    </div>
                @enderror


                <div class="variant-generation-box">

                    <div>

                        <strong>
                            Gerar combinações
                        </strong>

                        <div class="variant-generation-text">
                            O DICO irá criar as variantes-filhas deste
                            produto com base nos valores seleccionados
                            acima.
                        </div>

                        @if ($product->variants->isNotEmpty())

                            <div class="variant-generation-current">
                                Variantes actualmente criadas:
                                <strong>
                                    {{ $product->variants->count() }}
                                </strong>
                            </div>

                        @endif

                    </div>


                    <form
                        method="POST"
                        action="{{ route(
                            'products.generate-variants',
                            $product
                        ) }}"
                        onsubmit="
                            return confirm(
                                'Gerar as variantes com base na configuração actual?'
                            );
                        "
                        style="margin:0;"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="topbar-button"
                            style="
                                background:var(--primary);
                                color:#fff;
                                border-color:var(--primary);
                                white-space:nowrap;
                            "
                        >
                            Gerar variantes
                        </button>

                    </form>

                </div>

            </div>

        </div>

    @endif




@if (
    $hasVariants
    && $product->isVariable()
    && $product->variants->isNotEmpty()
)

    <div
        class="panel"
        style="
            max-width:950px;
            margin-top:20px;
        "
    >

        <div class="panel-header">

            <div>

                <h2 class="panel-title">
                    Gerir variantes
                </h2>

                <p class="panel-subtitle">
                    Defina SKU, código de barras, preços, stock mínimo
                    e estado de cada variante.
                </p>

            </div>

        </div>


        <div class="panel-body">

            <div class="variants-list">

                @foreach ($product->variants->sortBy('name') as $variant)

                    <form
                        method="POST"
                        action="{{ route(
                            'products.variants.update',
                            [
                                'product' => $product,
                                'variant' => $variant,
                            ]
                        ) }}"
                        class="variant-card"
                    >

                        @csrf
                        @method('PUT')


                        <div class="variant-card-header">

                            <div>

                                <strong class="variant-card-title">
                                    {{ $variant->name }}
                                </strong>

                                @if ($variant->attributeValues->isNotEmpty())

                                    <div class="variant-tags">

                                        @foreach (
                                            $variant->attributeValues
                                                ->sortBy(
                                                    fn ($value) =>
                                                        $value->attribute?->position ?? 0
                                                )
                                            as $value
                                        )

                                            <span class="variant-tag">
                                                @if ($value->attribute)
                                                    {{ $value->attribute->name }}:
                                                @endif

                                                {{ $value->value }}
                                            </span>

                                        @endforeach

                                    </div>

                                @endif

                            </div>


                            <span
                                class="
                                    variant-status
                                    {{ $variant->active
                                        ? 'is-active'
                                        : 'is-inactive' }}
                                "
                            >
                                {{ $variant->active
                                    ? 'Activa'
                                    : 'Inactiva' }}
                            </span>

                        </div>


                        <div class="variant-form-grid">

                            <div class="form-group">

                                <label class="form-label">
                                    SKU
                                </label>

                                <input
                                    type="text"
                                    name="sku"
                                    value="{{ old(
                                        'sku',
                                        $variant->sku
                                    ) }}"
                                    class="form-control"
                                >

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Código de barras
                                </label>

                                <input
                                    type="text"
                                    name="barcode"
                                    value="{{ old(
                                        'barcode',
                                        $variant->barcode
                                    ) }}"
                                    class="form-control"
                                >

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Preço de compra
                                </label>

                                <input
                                    type="number"
                                    name="purchase_price"
                                    value="{{ old(
                                        'purchase_price',
                                        $variant->purchase_price
                                    ) }}"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                >

                            </div>


                            <div class="form-group">

                                <label class="form-label">
                                    Preço de venda *
                                </label>

                                <input
                                    type="number"
                                    name="sale_price"
                                    value="{{ old(
                                        'sale_price',
                                        $variant->sale_price
                                    ) }}"
                                    class="form-control"
                                    min="0"
                                    step="0.01"
                                    required
                                >

                            </div>


                            @if ($hasInventory)

                                <div class="form-group">

                                    <label class="form-label">
                                        Stock mínimo
                                    </label>

                                    <input
                                        type="number"
                                        name="minimum_stock"
                                        value="{{ old(
                                            'minimum_stock',
                                            $variant->minimum_stock
                                        ) }}"
                                        class="form-control"
                                        min="0"
                                        step="0.001"
                                    >

                                </div>

                            @endif

                        </div>


                        <div class="variant-card-footer">

                            <div class="variant-controls">

                                @if ($hasInventory)

                                    <label class="variant-inventory-control">

                                        <span>
                                            Inventário
                                        </span>

                                        <select
                                            name="inventory_mode"
                                            class="form-control"
                                            style="min-width:180px;"
                                        >
                                            <option
                                                value="{{ \App\Models\Product::INVENTORY_NONE }}"
                                                @selected(
                                                    $variant->inventory_mode
                                                    === \App\Models\Product::INVENTORY_NONE
                                                )
                                            >
                                                Sem stock
                                            </option>

                                            <option
                                                value="{{ \App\Models\Product::INVENTORY_SIMPLE }}"
                                                @selected(
                                                    $variant->inventory_mode
                                                    === \App\Models\Product::INVENTORY_SIMPLE
                                                )
                                            >
                                                Stock simples
                                            </option>

                                            @if (
                                                $hasStockBatches
                                                || $variant->inventory_mode
                                                    === \App\Models\Product::INVENTORY_BATCH
                                            )
                                                <option
                                                    value="{{ \App\Models\Product::INVENTORY_BATCH }}"
                                                    @selected(
                                                        $variant->inventory_mode
                                                        === \App\Models\Product::INVENTORY_BATCH
                                                    )
                                                >
                                                    Stock por lotes
                                                </option>
                                            @endif
                                        </select>

                                    </label>

                                @endif


                                <label class="variant-checkbox">

                                    <input
                                        type="checkbox"
                                        name="active"
                                        value="1"
                                        @checked(
                                            $variant->active
                                        )
                                    >

                                    <span>
                                        Variante activa
                                    </span>

                                </label>

                            </div>


                            <button
                                type="submit"
                                class="topbar-button"
                                style="
                                    background:var(--primary);
                                    color:#fff;
                                    border-color:var(--primary);
                                "
                            >
                                Guardar variante
                            </button>

                        </div>

                    </form>

                @endforeach

            </div>

        </div>

    </div>

@endif


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
        grid-template-columns:
            repeat(auto-fit, minmax(190px, 1fr));
        gap:10px;
    }

    .inventory-mode-option {
        display:flex;
        gap:10px;
        padding:13px;
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
    .inventory-mode-option span,
    .inventory-help strong,
    .inventory-help span {
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
        padding:13px;
        border:1px solid var(--border);
        border-radius:12px;
        background:var(--primary-soft);
    }

    .variant-inventory-control {
        display:flex;
        align-items:center;
        gap:8px;
        font-size:13px;
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

    .variant-generation-box {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:20px;
        flex-wrap:wrap;
        padding:18px;
        border:1px solid var(--border);
        border-radius:12px;
        background:#fff;
    }

    .variant-generation-text {
        margin-top:5px;
        color:var(--muted);
        font-size:13px;
        line-height:1.55;
    }

    .variant-generation-current {
        margin-top:10px;
        font-size:13px;
    }


    .variants-list {
        display:grid;
        gap:16px;
    }

    .variant-card {
        display:block;
        padding:18px;
        border:1px solid var(--border);
        border-radius:14px;
        background:#fff;
    }

    .variant-card-header {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
        margin-bottom:18px;
    }

    .variant-card-title {
        display:block;
        font-size:15px;
    }

    .variant-tags {
        display:flex;
        flex-wrap:wrap;
        gap:7px;
        margin-top:9px;
    }

    .variant-tag {
        display:inline-flex;
        padding:6px 9px;
        border-radius:999px;
        background:var(--primary-soft);
        font-size:11px;
        line-height:1;
    }

    .variant-status {
        flex:0 0 auto;
        padding:6px 9px;
        border-radius:999px;
        font-size:11px;
        font-weight:600;
    }

    .variant-status.is-active {
        background:#ecfdf3;
        color:#027a48;
    }

    .variant-status.is-inactive {
        background:#f2f4f7;
        color:#667085;
    }

    .variant-form-grid {
        display:grid;
        grid-template-columns:
            repeat(auto-fit, minmax(180px, 1fr));
        gap:14px;
    }

    .variant-card-footer {
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
        margin-top:18px;
        padding-top:16px;
        border-top:1px solid var(--border);
    }

    .variant-controls {
        display:flex;
        flex-wrap:wrap;
        gap:14px;
    }

    .variant-checkbox {
        display:flex;
        align-items:center;
        gap:8px;
        cursor:pointer;
        font-size:13px;
    }

    .variant-checkbox input {
        accent-color:var(--primary);
    }

</style>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const typeInputs =
        document.querySelectorAll(
            'input[name="product_type"]'
        );

    const attributesPanel =
        document.getElementById(
            'variant-attributes-panel'
        );

    const inventoryControl =
        document.getElementById(
            'inventory-mode-control'
        );

    const minimumGroup =
        document.getElementById(
            'minimum-stock-group'
        );

    const inventoryModeInputs =
        document.querySelectorAll(
            'input[name="inventory_mode"]'
        );

    const minimumStock =
        document.getElementById(
            'minimum_stock'
        );

    const batchHelp =
        document.getElementById(
            'batch-help'
        );


    function updateProductType() {

        const selected =
            document.querySelector(
                'input[name="product_type"]:checked'
            );

        if (!selected) {
            return;
        }

        const type = selected.value;
        const isSimple = type === 'simple';
        const isVariable = type === 'variable';


        if (attributesPanel) {
            attributesPanel.style.display =
                isVariable
                    ? 'block'
                    : 'none';
        }


        if (inventoryControl) {
            inventoryControl.style.display =
                isSimple
                    ? 'block'
                    : 'none';
        }


        if (!isSimple) {

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

        if (minimumGroup) {
            minimumGroup.style.display =
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

    /*
     * Se o servidor devolveu um erro dentro de uma secção composta,
     * garantimos que a secção fica visível antes do mecanismo global
     * de scroll/focus do layouts/app.blade.php actuar.
     */
    const localValidationError =
        document.querySelector('.error-message');

    if (localValidationError) {
        const validationSection =
            localValidationError.closest(
                '[data-validation-section]'
            );

        if (validationSection) {
            validationSection.style.removeProperty('display');
        }
    }

});

</script>

@endsection
