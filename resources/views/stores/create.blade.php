@extends('layouts.app')

@section('title', 'Nova Loja | DICO')
@section('topbar-title', 'Lojas')

@section('content')

<div class="page-heading">

    <h1>
        Nova loja
    </h1>

    <p>
        Registe uma nova loja da
        <strong>{{ $activeCompany->name }}</strong>.
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
            action="{{ route('stores.store') }}"
        >

            @csrf


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Nome da loja *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-control"
                        required
                        autofocus
                        placeholder="Ex: DICO Maputo"
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
                        value="{{ old('code') }}"
                        class="form-control"
                        placeholder="Ex: MAP001"
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
                        value="{{ old('phone') }}"
                        class="form-control"
                        placeholder="Ex: 84 000 0000"
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
                        value="{{ old('email') }}"
                        class="form-control"
                        placeholder="loja@empresa.co.mz"
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
                    value="{{ old('address') }}"
                    class="form-control"
                    placeholder="Endereço da loja"
                >

                @error('address')
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
                        @checked(old('is_main'))
                        style="margin-top:3px;"
                    >

                    <div>

                        <strong>
                            Definir como loja principal
                        </strong>

                        <div
                            style="
                                margin-top:4px;
                                color:var(--muted);
                                font-size:12px;
                            "
                        >
                            Esta loja será considerada o
                            estabelecimento principal da empresa.
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
                    Guardar loja
                </button>

            </div>

        </form>

    </div>

</div>

@endsection