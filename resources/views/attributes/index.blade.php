@extends('layouts.app')

@section('title', 'Atributos | DICO')
@section('topbar-title', 'Atributos')

@section('content')

<div class="page-heading">

    <div
        style="
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:20px;
            flex-wrap:wrap;
        "
    >

        <div>

            <h1>
                Atributos
            </h1>

            <p>
                Configure características utilizadas
                nas variantes dos produtos.
            </p>

        </div>


        <a
            href="{{ route('attributes.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:#fff;
                border-color:var(--primary);
            "
        >
            + Novo atributo
        </a>

    </div>

</div>


@if ($errors->any())

    <div
        class="panel"
        style="
            margin-bottom:20px;
            border-color:#ef4444;
        "
    >

        <div class="panel-body">

            @foreach ($errors->all() as $error)

                <div class="error-message">
                    {{ $error }}
                </div>

            @endforeach

        </div>

    </div>

@endif


@forelse ($attributes as $attribute)

    <div
        class="panel"
        style="margin-bottom:20px;"
    >

        <div class="panel-header">

            <div>

                <div
                    style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    "
                >

                    <h2 class="panel-title">
                        {{ $attribute->name }}
                    </h2>


                    @if ($attribute->active)

                        <span class="status-badge">
                            Activo
                        </span>

                    @else

                        <span
                            class="
                                status-badge
                                status-inactive
                            "
                        >
                            Inactivo
                        </span>

                    @endif

                </div>


                <p class="panel-subtitle">
                    {{ $attribute->values->count() }}
                    valor(es)
                </p>

            </div>


            <a
                href="{{ route(
                    'attributes.edit',
                    $attribute
                ) }}"
                class="topbar-button"
            >
                Editar atributo
            </a>

        </div>


        <div class="panel-body">

            {{-- VALORES EXISTENTES --}}

            @if ($attribute->values->isNotEmpty())

                <div
                    style="
                        display:grid;
                        gap:10px;
                        margin-bottom:22px;
                    "
                >

                    @foreach ($attribute->values as $value)

                        <form
                            method="POST"
                            action="{{ route(
                                'attributes.values.update',
                                [
                                    $attribute,
                                    $value,
                                ]
                            ) }}"
                        >

                            @csrf
                            @method('PUT')


                            <div
                                style="
                                    display:grid;
                                    grid-template-columns:
                                        minmax(180px, 1fr)
                                        100px
                                        auto
                                        auto;
                                    align-items:center;
                                    gap:10px;
                                "
                            >

                                <input
                                    type="text"
                                    name="value"
                                    value="{{ $value->value }}"
                                    class="form-control"
                                    required
                                >


                                <input
                                    type="number"
                                    name="position"
                                    value="{{ $value->position }}"
                                    class="form-control"
                                    min="0"
                                >


                                <label
                                    class="checkbox-control"
                                    style="
                                        padding:10px 14px;
                                        margin:0;
                                    "
                                >

                                    <input
                                        type="checkbox"
                                        name="active"
                                        value="1"
                                        @checked($value->active)
                                    >

                                    <div>
                                        <strong>
                                            Activo
                                        </strong>
                                    </div>

                                </label>


                                <button
                                    type="submit"
                                    class="topbar-button"
                                >
                                    Guardar
                                </button>

                            </div>

                        </form>


                        <form
                            method="POST"
                            action="{{ route(
                                'attributes.values.destroy',
                                [
                                    $attribute,
                                    $value,
                                ]
                            ) }}"
                            onsubmit="
                                return confirm(
                                    'Eliminar este valor?'
                                );
                            "
                            style="
                                display:flex;
                                justify-content:flex-end;
                                margin-top:-4px;
                                margin-bottom:6px;
                            "
                        >

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                style="
                                    border:0;
                                    background:none;
                                    color:#dc2626;
                                    cursor:pointer;
                                    font-size:12px;
                                "
                            >
                                Eliminar valor
                            </button>

                        </form>

                    @endforeach

                </div>

            @else

                <div
                    style="
                        padding:18px;
                        margin-bottom:20px;
                        border:1px dashed var(--border);
                        border-radius:12px;
                        color:var(--muted);
                    "
                >
                    Este atributo ainda não tem valores.
                </div>

            @endif


            {{-- NOVO VALOR --}}

            <div
                style="
                    border-top:1px solid var(--border);
                    padding-top:20px;
                "
            >

                <strong>
                    Adicionar valor
                </strong>


                <form
                    method="POST"
                    action="{{ route(
                        'attributes.values.store',
                        $attribute
                    ) }}"
                    style="
                        display:grid;
                        grid-template-columns:
                            minmax(200px, 1fr)
                            120px
                            auto;
                        gap:10px;
                        margin-top:12px;
                    "
                >

                    @csrf


                    <input
                        type="text"
                        name="value"
                        class="form-control"
                        placeholder="Ex.: Preto"
                        required
                    >


                    <input
                        type="number"
                        name="position"
                        class="form-control"
                        min="0"
                        value="0"
                        placeholder="Ordem"
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
                        Adicionar
                    </button>

                </form>

            </div>

        </div>

    </div>


@empty

    <div class="panel">

        <div
            class="panel-body"
            style="
                text-align:center;
                padding:60px 20px;
            "
        >

            <h3>
                Ainda não existem atributos.
            </h3>

            <p
                style="
                    color:var(--muted);
                    margin-top:8px;
                "
            >
                Crie atributos como Cor, Tamanho,
                Capacidade ou Material.
            </p>


            <a
                href="{{ route('attributes.create') }}"
                class="topbar-button"
                style="
                    display:inline-flex;
                    margin-top:20px;
                    background:var(--primary);
                    color:#fff;
                    border-color:var(--primary);
                "
            >
                Criar primeiro atributo
            </a>

        </div>

    </div>

@endforelse

@endsection