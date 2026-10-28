@extends('layouts.app')

@section('title', 'Funcionalidades | DICO')
@section('topbar-title', 'Configurações')

@section('content')

@php
    $groups = [
        'core' => [
            'title' => 'Gestão Principal',
            'description' =>
                'Funcionalidades centrais para a operação do negócio.',
        ],

        'products' => [
            'title' => 'Produtos',
            'description' =>
                'Ferramentas adicionais para organizar e controlar produtos.',
        ],

        'inventory' => [
            'title' => 'Stock Avançado',
            'description' =>
                'Funcionalidades para negócios com necessidades especiais de inventário.',
        ],

        'food' => [
            'title' => 'Restauração',
            'description' =>
                'Funcionalidades destinadas a restaurantes e pastelarias.',
        ],
    ];
@endphp


<div class="page-heading">

    <h1>
        Perfil e Funcionalidades
    </h1>

    <p>
        Configure o DICO de acordo com a forma como
        <strong>{{ $company->name }}</strong>
        trabalha.
    </p>

</div>


{{-- =========================================================================
     PERFIL
============================================================================ --}}

<div class="panel">

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Perfil do negócio
            </h2>

            <p class="panel-subtitle">
                O perfil serve como configuração inicial.
                Depois pode activar ou desactivar funcionalidades individualmente.
            </p>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="POST"
            action="{{ route(
                'settings.features.apply-profile'
            ) }}"
        >

            @csrf


            <div class="business-profile-grid">

                @foreach (
                    $profiles
                    as $value => $label
                )

                    <label
                        class="
                            business-profile-card
                            {{ $company->business_type === $value
                                ? 'selected'
                                : ''
                            }}
                        "
                    >

                        <input
                            type="radio"
                            name="business_type"
                            value="{{ $value }}"
                            @checked(
                                old(
                                    'business_type',
                                    $company->business_type
                                ) === $value
                            )
                        >

                        <div>

                            <strong>
                                {{ $label }}
                            </strong>

                            <span>

                                @switch($value)

                                    @case('retail')
                                        Loja de produtos ou comércio geral.
                                        @break

                                    @case('fashion')
                                        Moda, roupa, calçado e acessórios.
                                        @break

                                    @case('supermarket')
                                        Mercearias, minimercados e supermercados.
                                        @break

                                    @case('food')
                                        Restaurante, pastelaria ou alimentação.
                                        @break

                                    @case('import')
                                        Importadores, distribuidores e revendedores.
                                        @break

                                    @case('services')
                                        Empresas focadas principalmente em serviços.
                                        @break

                                    @default
                                        Outro modelo de negócio.

                                @endswitch

                            </span>

                        </div>

                    </label>

                @endforeach

            </div>


            <div class="profile-warning">

                Aplicar um perfil substitui a selecção actual
                pelas funcionalidades recomendadas para esse negócio.

            </div>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    margin-top:18px;
                "
            >

                <button
                    type="submit"
                    class="topbar-button"
                >
                    Aplicar perfil recomendado
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================================
     FUNCIONALIDADES
============================================================================ --}}

<form
    method="POST"
    action="{{ route(
        'settings.features.update'
    ) }}"
>

    @csrf
    @method('PUT')


    <input
        type="hidden"
        name="business_type"
        value="{{ $company->business_type ?? 'other' }}"
    >


    @foreach (
        $groups
        as $groupKey => $group
    )

        <div
            class="panel"
            style="margin-top:22px;"
        >

            <div class="panel-header">

                <div>

                    <h2 class="panel-title">
                        {{ $group['title'] }}
                    </h2>

                    <p class="panel-subtitle">
                        {{ $group['description'] }}
                    </p>

                </div>

            </div>


            <div class="panel-body">

                <div class="feature-grid">

                    @foreach (
                        $features
                        as $featureKey => $feature
                    )

                        @continue(
                            $feature['group']
                            !== $groupKey
                        )


                        <label class="feature-card">

                            <div class="feature-toggle">

                                <input
                                    type="checkbox"
                                    name="features[]"
                                    value="{{ $featureKey }}"
                                    @checked(
                                        in_array(
                                            $featureKey,
                                            old(
                                                'features',
                                                $enabledFeatures
                                            ),
                                            true
                                        )
                                    )
                                >

                                <span></span>

                            </div>


                            <div class="feature-content">

                                <strong>
                                    {{ $feature['label'] }}
                                </strong>

                                <p>
                                    {{ $feature['description'] }}
                                </p>


                                @if (
                                    in_array(
                                        $featureKey,
                                        [
                                            'variants',
                                            'stock_batches',
                                            'imports',
                                            'expiry_dates',
                                            'serial_numbers',
                                            'recipes',
                                            'ingredients',
                                            'tables',
                                            'kitchen',
                                        ],
                                        true
                                    )
                                )

                                    <span class="feature-advanced">
                                        Funcionalidade avançada
                                    </span>

                                @endif

                            </div>

                        </label>

                    @endforeach

                </div>

            </div>

        </div>

    @endforeach


    <div class="settings-save-bar">

        <div>

            <strong>
                Personalização do DICO
            </strong>

            <span>
                Apenas as funcionalidades activas serão mostradas no sistema.
            </span>

        </div>


        <button
            type="submit"
            class="
                topbar-button
                settings-save-button
            "
        >
            Guardar configuração
        </button>

    </div>

</form>

@endsection


@section('styles')

.business-profile-grid {
    display:grid;
    grid-template-columns:
        repeat(3,minmax(0,1fr));
    gap:12px;
}

.business-profile-card {
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-height:105px;
    padding:16px;
    border:1px solid var(--border);
    border-radius:13px;
    background:#fff;
    cursor:pointer;
}

.business-profile-card:hover {
    border-color:var(--primary);
}

.business-profile-card.selected {
    border-color:var(--primary);
    background:var(--primary-soft);
}

.business-profile-card input {
    margin-top:3px;
}

.business-profile-card > div {
    display:flex;
    flex-direction:column;
    gap:5px;
}

.business-profile-card strong {
    font-size:13px;
}

.business-profile-card span {
    color:var(--muted);
    font-size:11px;
    line-height:1.5;
}

.profile-warning {
    margin-top:16px;
    padding:11px 13px;
    border-radius:9px;
    background:#fff7df;
    color:#8a5c00;
    font-size:11px;
}


/* ==========================================================================
   FEATURES
============================================================================ */

.feature-grid {
    display:grid;
    grid-template-columns:
        repeat(2,minmax(0,1fr));
    gap:12px;
}

.feature-card {
    display:flex;
    align-items:flex-start;
    gap:14px;
    min-height:105px;
    padding:16px;
    border:1px solid var(--border);
    border-radius:13px;
    background:#fff;
    cursor:pointer;
}

.feature-card:hover {
    border-color:var(--primary);
}


/* ==========================================================================
   TOGGLE
============================================================================ */

.feature-toggle {
    flex:0 0 auto;
    position:relative;
}

.feature-toggle input {
    position:absolute;
    opacity:0;
    pointer-events:none;
}

.feature-toggle span {
    width:42px;
    height:23px;
    display:block;
    position:relative;
    border-radius:999px;
    background:#d9dfdb;
    transition:.2s;
}

.feature-toggle span::after {
    content:'';
    width:17px;
    height:17px;
    position:absolute;
    top:3px;
    left:3px;
    border-radius:50%;
    background:#fff;
    box-shadow:0 1px 3px rgba(0,0,0,.18);
    transition:.2s;
}

.feature-toggle
input:checked
+ span {
    background:var(--primary);
}

.feature-toggle
input:checked
+ span::after {
    transform:translateX(19px);
}


/* ==========================================================================
   CONTENT
============================================================================ */

.feature-content {
    min-width:0;
}

.feature-content strong {
    display:block;
    font-size:13px;
}

.feature-content p {
    margin:6px 0 0;
    color:var(--muted);
    font-size:11px;
    line-height:1.55;
}

.feature-advanced {
    display:inline-flex;
    margin-top:9px;
    padding:4px 7px;
    border-radius:999px;
    background:#f3f5f4;
    color:var(--muted);
    font-size:9px;
    font-weight:750;
}


/* ==========================================================================
   SAVE BAR
============================================================================ */

.settings-save-bar {
    position:sticky;
    bottom:18px;
    z-index:20;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-top:22px;
    padding:16px 18px;
    border:1px solid var(--border);
    border-radius:14px;
    background:#fff;
    box-shadow:0 8px 35px rgba(0,0,0,.1);
}

.settings-save-bar > div {
    display:flex;
    flex-direction:column;
    gap:3px;
}

.settings-save-bar strong {
    font-size:12px;
}

.settings-save-bar span {
    color:var(--muted);
    font-size:10px;
}

.settings-save-button {
    background:var(--primary);
    color:#fff;
    border-color:var(--primary);
}


@media (max-width:900px) {

    .business-profile-grid {
        grid-template-columns:
            repeat(2,minmax(0,1fr));
    }

}

@media (max-width:700px) {

    .feature-grid {
        grid-template-columns:1fr;
    }

}

@media (max-width:560px) {

    .business-profile-grid {
        grid-template-columns:1fr;
    }

    .settings-save-bar {
        align-items:flex-start;
        flex-direction:column;
    }

    .settings-save-button {
        width:100%;
    }

}

@endsection