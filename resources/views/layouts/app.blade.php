<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', 'DICO')
    </title>

    <style>
        :root {
            --primary: #168a5b;
            --primary-dark: #0f6945;
            --primary-soft: #e8f6ef;
            --sidebar: #10241b;
            --sidebar-hover: #1b382a;
            --background: #f4f7f6;
            --surface: #ffffff;
            --text: #17201c;
            --muted: #748079;
            --border: #e2e9e5;
            --danger: #c83d4d;
            --warning: #c58b19;
            --shadow: 0 12px 32px rgba(26, 58, 43, 0.08);
            --sidebar-width: 260px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .app-shell {
            min-height: 100vh;
        }

        /*
        |--------------------------------------------------------------------------
        | Sidebar
        |--------------------------------------------------------------------------
        */

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            padding: 24px 18px;
            background: var(--sidebar);
            color: #fff;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 48px;
            margin-bottom: 34px;
            padding: 0 10px;
        }

        .sidebar-brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 13px;
            background: #fff;
            color: var(--primary-dark);
            font-weight: 900;
        }

        .sidebar-brand-name {
            display: block;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 0.04em;
        }

        .sidebar-brand-product {
            display: block;
            margin-top: 2px;
            color: rgba(255, 255, 255, 0.58);
            font-size: 11px;
            letter-spacing: 0.12em;
        }

        .sidebar-section-label {
            margin: 18px 12px 10px;
            color: rgba(255, 255, 255, 0.42);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .sidebar-menu {
            display: grid;
            gap: 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 44px;
            padding: 0 13px;
            border-radius: 11px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 14px;
            font-weight: 650;
            transition:
                color 0.2s,
                background 0.2s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: var(--sidebar-hover);
            color: #fff;
        }

        .sidebar-icon {
            width: 21px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 10px 4px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .sidebar-user-name {
            overflow: hidden;
            color: #fff;
            font-size: 13px;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sidebar-user-role {
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 11px;
            font-weight: 750;
        }

        .sidebar-user-email {
            margin-top: 3px;
            overflow: hidden;
            color: rgba(255, 255, 255, 0.5);
            font-size: 11px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /*
        |--------------------------------------------------------------------------
        | Main
        |--------------------------------------------------------------------------
        */

        .main-area {
            min-height: 100vh;
            margin-left: var(--sidebar-width);
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 80;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 32px;
            border-bottom: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
        }

        .topbar-title {
            font-size: 15px;
            font-weight: 800;
        }

        .topbar-subtitle {
            margin-top: 3px;
            color: var(--muted);
            font-size: 12px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-button {
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            background: #fff;
            color: var(--text);
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
        }

        .topbar-button:hover {
            background: #f8faf9;
        }

        .content {
            padding: 32px;
        }

        /*
        |--------------------------------------------------------------------------
        | Page
        |--------------------------------------------------------------------------
        */

        .page-heading {
            margin-bottom: 28px;
        }

        .page-heading h1 {
            margin: 0;
            font-size: 30px;
            letter-spacing: -0.035em;
        }

        .page-heading p {
            margin: 8px 0 0;
            color: var(--muted);
            line-height: 1.6;
        }

        /*
        |--------------------------------------------------------------------------
        | Metrics
        |--------------------------------------------------------------------------
        */

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .metric-card {
            padding: 22px;
            border: 1px solid var(--border);
            border-radius: 17px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .metric-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
        }

        .metric-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .metric-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .metric-value {
            margin-top: 20px;
            font-size: 31px;
            font-weight: 850;
            letter-spacing: -0.04em;
        }

        .metric-description {
            margin-top: 7px;
            color: var(--muted);
            font-size: 12px;
        }

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        .dashboard-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.6fr)
                minmax(280px, 0.7fr);
            gap: 20px;
            margin-top: 22px;
        }

        /*
        |--------------------------------------------------------------------------
        | Panels
        |--------------------------------------------------------------------------
        */

        .panel {
            border: 1px solid var(--border);
            border-radius: 17px;
            background: var(--surface);
            box-shadow: var(--shadow);
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 22px;
            border-bottom: 1px solid var(--border);
        }

        .panel-title {
            margin: 0;
            font-size: 16px;
        }

        .panel-subtitle {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 12px;
        }

        .panel-body {
            padding: 22px;
        }

        .empty-state {
            padding: 48px 24px;
            color: var(--muted);
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Company
        |--------------------------------------------------------------------------
        */

        .company-list {
            display: grid;
            gap: 12px;
        }

        .company-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 15px;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 800;
        }

        .company-meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 27px;
            border-radius: 999px;
            padding: 0 10px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 11px;
            font-weight: 800;
        }

        .status-inactive {
            background: #f1f3f2;
            color: var(--muted);
        }

        /*
        |--------------------------------------------------------------------------
        | Quick actions
        |--------------------------------------------------------------------------
        */

        .quick-actions {
            display: grid;
            gap: 10px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 14px;
            border: 1px solid var(--border);
            border-radius: 12px;
            transition:
                border-color 0.2s,
                transform 0.2s;
        }

        .quick-action:hover {
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .quick-action-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .quick-action-title {
            font-size: 13px;
            font-weight: 800;
        }

        .quick-action-description {
            margin-top: 3px;
            color: var(--muted);
            font-size: 11px;
        }

        /*
        |--------------------------------------------------------------------------
        | Business context
        |--------------------------------------------------------------------------
        */

        .business-context-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .context-select {
            height: 40px;
            min-width: 150px;
            max-width: 220px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 34px 0 12px;
            background: #fff;
            color: var(--text);
            font-size: 13px;
            font-weight: 650;
            outline: none;
        }

        .context-select:focus {
            border-color: var(--primary);
            box-shadow:
                0 0 0 3px
                rgba(22, 138, 91, 0.10);
        }

        .context-select:disabled {
            background: #f1f4f2;
            color: var(--muted);
            cursor: not-allowed;
        }

        /*
        |--------------------------------------------------------------------------
        | Forms
        |--------------------------------------------------------------------------
        */

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 750;
        }

        .form-control {
            width: 100%;
            min-height: 46px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 0 14px;
            background: #fff;
            color: var(--text);
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow:
                0 0 0 3px
                rgba(22, 138, 91, 0.10);
        }

        .error-message {
            margin-top: 6px;
            color: var(--danger);
            font-size: 12px;
        }

        /*
        |--------------------------------------------------------------------------
        | Validation UX
        |--------------------------------------------------------------------------
        |
        | O layout marca automaticamente a área que contém um erro Laravel e,
        | após o redirect de validação, desloca a página para o primeiro erro.
        | `scroll-margin-top` evita que a topbar sticky tape o campo.
        |--------------------------------------------------------------------------
        */

        .validation-error-target {
            scroll-margin-top: 104px;
        }

        .form-group.validation-error-target .form-control,
        [data-validation-section].validation-error-target {
            border-color: var(--danger);
        }

        [data-validation-section].validation-error-target {
            border-radius: 12px;
            box-shadow:
                0 0 0 3px
                rgba(200, 61, 77, 0.08);
        }

        .form-group.validation-error-target .form-control:focus {
            border-color: var(--danger);
            box-shadow:
                0 0 0 3px
                rgba(200, 61, 77, 0.12);
        }

        .form-row {
            display: grid;
            grid-template-columns:
                repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .checkbox-control {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
        }

        .checkbox-control input {
            margin-top: 4px;
            accent-color: var(--primary);
        }

        .checkbox-control strong {
            display: block;
            font-size: 13px;
        }

        .checkbox-control span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.5;
        }

        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            padding: 13px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .data-table td {
            padding: 15px 14px;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
            vertical-align: middle;
        }

        .data-table tbody tr:hover {
            background: #fafcfb;
        }

        .table-secondary {
            margin-top: 4px;
            color: var(--muted);
            font-size: 11px;
        }

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        .product-form-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.5fr)
                minmax(300px, 0.7fr);
            gap: 22px;
        }

        /*
        |--------------------------------------------------------------------------
        | Mobile
        |--------------------------------------------------------------------------
        */

        .mobile-menu-button {
            display: none;
        }

        @media (max-width: 1100px) {

            .metric-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 950px) {

            .product-form-grid {
                grid-template-columns: 1fr;
            }

        }

        @media (max-width: 900px) {

            .business-context-form {
                display: none;
            }

        }

        @media (max-width: 760px) {

            .sidebar {
                display: none;
            }

            .main-area {
                margin-left: 0;
            }

            .topbar {
                height: auto;
                min-height: 68px;
                padding: 14px 18px;
            }

            .content {
                padding: 22px 16px;
            }

            .metric-grid {
                grid-template-columns: 1fr;
            }

            .topbar-subtitle {
                display: none;
            }

            .mobile-menu-button {
                display: inline-flex;
            }

        }

        @media (max-width: 650px) {

            .form-row {
                grid-template-columns: 1fr;
            }

        }

        @yield('styles')
    </style>
</head>


<body>

@php
    /*
    |--------------------------------------------------------------------------
    | Perfil activo
    |--------------------------------------------------------------------------
    */

    $roleLabels = [
        'owner' => 'Proprietário',
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'cashier' => 'Caixa',
    ];

    /*
    |--------------------------------------------------------------------------
    | Permissões por role
    |--------------------------------------------------------------------------
    */

    $canOperate = in_array(
        $activeRole ?? null,
        [
            'owner',
            'admin',
            'manager',
            'cashier',
        ],
        true
    );

    $canManageBusiness = in_array(
        $activeRole ?? null,
        [
            'owner',
            'admin',
            'manager',
        ],
        true
    );

    $canAdminister = in_array(
        $activeRole ?? null,
        [
            'owner',
            'admin',
        ],
        true
    );

    /*
    |--------------------------------------------------------------------------
    | Funcionalidades activas da empresa
    |--------------------------------------------------------------------------
    |
    | Produtos, Categorias e Unidades NÃO são features.
    | Fazem parte do Core do DICO.
    |--------------------------------------------------------------------------
    */

    $canPos =
        $activeCompany
        ? $activeCompany->hasFeature('pos')
        : false;

    $canSales =
        $activeCompany
        ? $activeCompany->hasFeature('sales')
        : false;

    $canInventory =
        $activeCompany
        ? $activeCompany->hasFeature('inventory')
        : false;

    $canPurchases =
        $activeCompany
        ? $activeCompany->hasFeature('purchases')
        : false;

    $canReceivables =
        $activeCompany
        ? $activeCompany->hasFeature('receivables')
        : false;

    $canBrands =
        $activeCompany
        ? $activeCompany->hasFeature('brands')
        : false;

    /*
    |--------------------------------------------------------------------------
    | Features avançadas
    |--------------------------------------------------------------------------
    |
    | Já podem estar configuradas na empresa,
    | mas ainda não mostramos links enquanto os módulos
    | correspondentes não estiverem implementados.
    |--------------------------------------------------------------------------
    */

    $canVariants =
        $activeCompany
        ? $activeCompany->hasFeature('variants')
        : false;

    $canStockBatches =
        $activeCompany
        ? $activeCompany->hasFeature('stock_batches')
        : false;

    $canImports =
        $activeCompany
        ? $activeCompany->hasFeature('imports')
        : false;

    $canExpiryDates =
        $activeCompany
        ? $activeCompany->hasFeature('expiry_dates')
        : false;

    $canSerialNumbers =
        $activeCompany
        ? $activeCompany->hasFeature('serial_numbers')
        : false;

    $canRecipes =
        $activeCompany
        ? $activeCompany->hasFeature('recipes')
        : false;

    $canIngredients =
        $activeCompany
        ? $activeCompany->hasFeature('ingredients')
        : false;

    $canTables =
        $activeCompany
        ? $activeCompany->hasFeature('tables')
        : false;

    $canKitchen =
        $activeCompany
        ? $activeCompany->hasFeature('kitchen')
        : false;
@endphp


<div class="app-shell">

    {{-- =====================================================================
         SIDEBAR
    ====================================================================== --}}

    <aside class="sidebar">

        <a
            href="{{ route('dashboard') }}"
            class="sidebar-brand"
        >

            <div class="sidebar-brand-mark">
                D
            </div>

            <div>

                <span class="sidebar-brand-name">
                    DICO
                </span>

                <span class="sidebar-brand-product">
                    BUSINESS MANAGEMENT
                </span>

            </div>

        </a>


        {{-- ================================================================
             GERAL
        ================================================================= --}}

        <div class="sidebar-section-label">
            Geral
        </div>


        <nav class="sidebar-menu">

            {{-- Dashboard - CORE --}}

            <a
                href="{{ route('dashboard') }}"
                class="
                    sidebar-link
                    {{ request()->routeIs('dashboard')
                        ? 'active'
                        : ''
                    }}
                "
            >

                <span class="sidebar-icon">
                    ⌂
                </span>

                Dashboard

            </a>


            {{-- POS - FEATURE: pos --}}

            @if (
                $canOperate
                && $canPos
            )

                <a
                    href="{{ route('pos.index') }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs('pos.*')
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ▣
                    </span>

                    Ponto de venda

                </a>

            @endif


            {{-- Caixa - FEATURE: pos --}}

            @if (
                $canOperate
                && $canPos
            )

                <a
                    href="{{ route(
                        'cash-registers.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'cash-registers.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ▦
                    </span>

                    Caixa

                </a>

            @endif


            {{-- Vendas - FEATURE: sales --}}

            @if (
                $canOperate
                && $canSales
            )

                <a
                    href="{{ route('sales.index') }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'sales.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ▤
                    </span>

                    Vendas

                </a>

            @endif


            {{-- Devoluções - FEATURE: sales --}}

            @if ($canOperate && $canSales)
                <a
                    href="{{ route('returns.index') }}"
                    class="sidebar-link {{ request()->routeIs('returns.*') ? 'active' : '' }}"
                >
                    <span class="sidebar-icon">↩</span>
                    Devoluções
                </a>
            @endif


            {{-- Contas a Receber - FEATURE: receivables --}}

            @if (
                $canOperate
                && $canReceivables
            )

                <a
                    href="{{ route(
                        'receivables.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'receivables.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ◫
                    </span>

                    Contas a Receber

                </a>

            @endif


            {{-- Clientes - CORE --}}

            @if ($canOperate)

                <a
                    href="{{ route(
                        'customers.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'customers.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ♙
                    </span>

                    Clientes

                </a>

            @endif

        </nav>


        {{-- ================================================================
             GESTÃO
        ================================================================= --}}

        @if ($canManageBusiness)

            <div class="sidebar-section-label">
                Gestão
            </div>


            <nav class="sidebar-menu">

                {{-- ========================================================
                     PRODUTOS - CORE
                ========================================================= --}}

                <a
                    href="{{ route(
                        'products.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'products.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ◇
                    </span>

                    Produtos

                </a>


                {{-- ========================================================
                     CATEGORIAS - CORE
                ========================================================= --}}

                <a
                    href="{{ route(
                        'categories.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'categories.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ▦
                    </span>

                    Categorias

                </a>


                {{-- ========================================================
                     MARCAS - FEATURE: brands
                ========================================================= --}}

                @if ($canBrands)

                    <a
                        href="{{ route(
                            'brands.index'
                        ) }}"
                        class="
                            sidebar-link
                            {{ request()->routeIs(
                                'brands.*'
                            )
                                ? 'active'
                                : ''
                            }}
                        "
                    >

                        <span class="sidebar-icon">
                            ◈
                        </span>

                        Marcas

                    </a>

                @endif


                {{-- ========================================================
                     UNIDADES - CORE
                ========================================================= --}}

                <a
                    href="{{ route(
                        'units.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'units.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ↔
                    </span>

                    Unidades

                </a>


                {{-- ========================================================
                     ATRIBUTOS - FEATURE: variants
                ========================================================= --}}

                @if ($canVariants)

                    <a
                        href="{{ route(
                            'attributes.index'
                        ) }}"
                        class="
                            sidebar-link
                            {{ request()->routeIs(
                                'attributes.*'
                            )
                                ? 'active'
                                : ''
                            }}
                        "
                    >

                        <span class="sidebar-icon">
                            ◌
                        </span>

                        Atributos

                    </a>

                @endif


                {{-- ========================================================
                     STOCK - FEATURE: inventory
                ========================================================= --}}

                @if ($canInventory)

                    <a
                        href="{{ route(
                            'stocks.index'
                        ) }}"
                        class="
                            sidebar-link
                            {{ request()->routeIs(
                                'stocks.*'
                            )
                                ? 'active'
                                : ''
                            }}
                        "
                    >

                        <span class="sidebar-icon">
                            ▤
                        </span>

                        Stock

                    </a>

                @endif


                {{-- ========================================================
                     COMPRAS - FEATURE: purchases
                ========================================================= --}}

                @if ($canPurchases)

                    <a
                        href="{{ route(
                            'purchases.index'
                        ) }}"
                        class="
                            sidebar-link
                            {{ request()->routeIs(
                                'purchases.*'
                            )
                                ? 'active'
                                : ''
                            }}
                        "
                    >

                        <span class="sidebar-icon">
                            ▨
                        </span>

                        Compras

                    </a>


                    {{-- Fornecedores acompanham Compras --}}

                    <a
                        href="{{ route(
                            'suppliers.index'
                        ) }}"
                        class="
                            sidebar-link
                            {{ request()->routeIs(
                                'suppliers.*'
                            )
                                ? 'active'
                                : ''
                            }}
                        "
                    >

                        <span class="sidebar-icon">
                            ▧
                        </span>

                        Fornecedores

                    </a>

                @endif


                {{-- ========================================================
                     RELATÓRIOS - FEATURE: sales
                ========================================================= --}}

                @if ($canSales)

                    <a
                        href="{{ route(
                            'reports.index'
                        ) }}"
                        class="
                            sidebar-link
                            {{ request()->routeIs(
                                'reports.*'
                            )
                                ? 'active'
                                : ''
                            }}
                        "
                    >

                        <span class="sidebar-icon">
                            ▥
                        </span>

                        Relatórios

                    </a>

                @endif

            </nav>

        @endif


        {{-- ================================================================
             ADMINISTRAÇÃO
        ================================================================= --}}

        @if ($canAdminister)

            <div class="sidebar-section-label">
                Administração
            </div>


            <nav class="sidebar-menu">

                {{-- Lojas - CORE --}}

                <a
                    href="{{ route(
                        'stores.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'stores.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ◎
                    </span>

                    Lojas

                </a>


                {{-- Utilizadores - CORE --}}

                <a
                    href="{{ route(
                        'users.index'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'users.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ♙
                    </span>

                    Utilizadores

                </a>


                {{-- Configurações da empresa --}}

                <a
                    href="{{ route(
                        'settings.company.edit'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'settings.company.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ⚙
                    </span>

                    Configurações

                </a>


                {{-- Perfil e funcionalidades --}}

                <a
                    href="{{ route(
                        'settings.features.edit'
                    ) }}"
                    class="
                        sidebar-link
                        {{ request()->routeIs(
                            'settings.features.*'
                        )
                            ? 'active'
                            : ''
                        }}
                    "
                >

                    <span class="sidebar-icon">
                        ◉
                    </span>

                    Funcionalidades

                </a>

            </nav>

        @endif


        {{-- ================================================================
             UTILIZADOR
        ================================================================= --}}

        <div class="sidebar-footer">

            <div class="sidebar-user-name">

                {{ auth()->user()->name }}

            </div>


            <div class="sidebar-user-role">

                {{ $roleLabels[
                    $activeRole ?? ''
                ] ?? 'Utilizador' }}

            </div>


            <div class="sidebar-user-email">

                {{ auth()->user()->email }}

            </div>

        </div>

    </aside>


    {{-- =====================================================================
         ÁREA PRINCIPAL
    ====================================================================== --}}

    <div class="main-area">

        {{-- ================================================================
             TOPBAR
        ================================================================= --}}

        <header class="topbar">

            <div>

                <div class="topbar-title">

                    @yield(
                        'topbar-title',
                        'DICO'
                    )

                </div>


                <div class="topbar-subtitle">

                    Gestão simples para negócios melhores.

                </div>

            </div>


            <div class="topbar-actions">

                {{-- ========================================================
                     CONTEXTO EMPRESA / LOJA
                ========================================================= --}}

                <form
                    method="POST"
                    action="{{ route(
                        'business.context.update'
                    ) }}"
                    class="business-context-form"
                >

                    @csrf


                    <select
                        name="company_id"
                        class="context-select"
                    >

                        @foreach (
                            $availableCompanies
                            as $company
                        )

                            <option
                                value="{{ $company->id }}"
                                @selected(
                                    $activeCompany?->id
                                    === $company->id
                                )
                            >

                                {{ $company->name }}

                            </option>

                        @endforeach

                    </select>


                    <select
                        name="store_id"
                        class="context-select"
                        @disabled(
                            $availableStores->isEmpty()
                        )
                    >

                        @if (
                            $availableStores->isEmpty()
                        )

                            <option value="">
                                Sem lojas
                            </option>

                        @else

                            @foreach (
                                $availableStores
                                as $store
                            )

                                <option
                                    value="{{ $store->id }}"
                                    @selected(
                                        $activeStore?->id
                                        === $store->id
                                    )
                                >

                                    {{ $store->name }}

                                </option>

                            @endforeach

                        @endif

                    </select>


                    <button
                        type="submit"
                        class="topbar-button"
                    >
                        Alterar
                    </button>

                </form>


                {{-- ========================================================
                     LOGOUT
                ========================================================= --}}

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="topbar-button"
                    >
                        Sair
                    </button>

                </form>

            </div>

        </header>


        {{-- ================================================================
             CONTENT
        ================================================================= --}}

        <main class="content">

            @if (session('success'))

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:var(--primary-soft);
                        color:var(--primary-dark);
                        font-size:14px;
                    "
                >

                    {{ session('success') }}

                </div>

            @endif


            @if (session('error'))

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fff0f0;
                        color:var(--danger);
                        font-size:14px;
                    "
                >

                    {{ session('error') }}

                </div>

            @endif


            @yield('content')

        </main>

    </div>

</div>


@yield('scripts')


<script>
document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | UX global para erros de validação
    |--------------------------------------------------------------------------
    |
    | Sempre que uma view renderizar `.error-message`, procuramos a área
    | lógica mais próxima, marcamo-la e levamos o utilizador ao primeiro erro.
    |
    | Para grupos especiais (radios, checkboxes, cards, etc.), a view pode
    | simplesmente adicionar `data-validation-section` ao contentor.
    |--------------------------------------------------------------------------
    */

    const errorMessages = Array.from(
        document.querySelectorAll('.error-message')
    );

    if (errorMessages.length === 0) {
        return;
    }

    function getErrorTarget(errorMessage) {
        return errorMessage.closest(
            '[data-validation-section], .form-group'
        ) || errorMessage;
    }

    function getFocusableControl(target) {
        if (!target) {
            return null;
        }

        return target.querySelector(
            [
                'input:not([type="hidden"]):not([disabled])',
                'select:not([disabled])',
                'textarea:not([disabled])',
                'button:not([disabled])'
            ].join(',')
        );
    }

    errorMessages.forEach(function (errorMessage) {
        const target = getErrorTarget(errorMessage);

        if (target) {
            target.classList.add('validation-error-target');
        }
    });

    const firstError = errorMessages[0];
    const firstTarget = getErrorTarget(firstError);

    /*
     * requestAnimationFrame + pequeno timeout:
     * deixa os scripts específicos da página terminarem primeiro
     * (mostrar/esconder painéis, variantes, inventário, etc.).
     */
    window.requestAnimationFrame(function () {
        window.setTimeout(function () {

            firstTarget.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            const control = getFocusableControl(firstTarget);

            if (control) {
                window.setTimeout(function () {
                    try {
                        control.focus({
                            preventScroll: true
                        });
                    } catch (error) {
                        control.focus();
                    }
                }, 350);
            }

        }, 80);
    });

});
</script>

</body>

</html>