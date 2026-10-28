@extends('layouts.app')

@section('title', 'Editar Loja | DICO')
@section('topbar-title', 'Lojas')

@section('content')

<div class="page-heading">

    <h1>
        Editar loja
    </h1>

    <p>
        Actualize os dados de
        <strong>{{ $store->name }}</strong>.
    </p>

</div>


<div
    class="panel"
    style="max-width:850px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Dados da loja
            </h2>

            <p class="panel-subtitle">
                Informações do estabelecimento.
            </p>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="POST"
            action="{{ route(
                'stores.update',
                $store
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Nome da loja *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old(
                            'name',
                            $store->name
                        ) }}"
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
                        Código
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="{{ old(
                            'code',
                            $store->code
                        ) }}"
                        class="form-control"
                    >

                    @error('code')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Contacto
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old(
                            'phone',
                            $store->phone
                        ) }}"
                        class="form-control"
                    >

                    @error('phone')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>


                <div class="form-group">

                    <label class="form-label">
                        E-mail
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old(
                            'email',
                            $store->email
                        ) }}"
                        class="form-control"
                    >

                    @error('email')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Endereço
                </label>

                <input
                    type="text"
                    name="address"
                    value="{{ old(
                        'address',
                        $store->address
                    ) }}"
                    class="form-control"
                >

                @error('address')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div class="form-group">

                <label class="form-label">
                    Estado
                </label>

                <select
                    name="status"
                    class="form-control"
                    required
                >

                    <option
                        value="active"
                        @selected(
                            old(
                                'status',
                                $store->status
                            ) === 'active'
                        )
                    >
                        Activa
                    </option>

                    <option
                        value="inactive"
                        @selected(
                            old(
                                'status',
                                $store->status
                            ) === 'inactive'
                        )
                    >
                        Inactiva
                    </option>

                </select>

                @error('status')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div
                style="
                    margin-top:25px;
                    padding:18px;
                    border:1px solid var(--border);
                    border-radius:14px;
                "
            >

                <label
                    style="
                        display:flex;
                        align-items:flex-start;
                        gap:12px;
                        cursor:pointer;
                    "
                >

                    <input
                        type="checkbox"
                        name="is_main"
                        value="1"
                        @checked(
                            old(
                                'is_main',
                                $store->is_main
                            )
                        )
                        style="margin-top:3px;"
                    >

                    <div>

                        <strong>
                            Loja principal
                        </strong>

                        <div
                            style="
                                margin-top:4px;
                                color:var(--muted);
                                font-size:12px;
                            "
                        >
                            Ao seleccionar esta opção,
                            as restantes lojas deixam
                            automaticamente de ser a principal.
                        </div>

                    </div>

                </label>

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
                    href="{{ route('stores.index') }}"
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

        </form>

    </div>

</div>

@endsection