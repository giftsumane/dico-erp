@extends('layouts.app')

@section('title', 'Novo Cliente | DICO')
@section('topbar-title', 'Clientes')

@section('content')

<div class="page-heading">
    <h1>Novo cliente</h1>

    <p>
        Registe um novo cliente da
        <strong>{{ $activeCompany->name }}</strong>.
    </p>
</div>

<div
    class="panel"
    style="max-width:800px;"
>
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Dados do cliente
            </h2>

            <p class="panel-subtitle">
                Preencha as informações principais.
            </p>
        </div>
    </div>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('customers.store') }}"
        >
            @csrf

            <div class="form-group">
                <label class="form-label">
                    Nome *
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control"
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
                        NUIT
                    </label>

                    <input
                        type="text"
                        name="nuit"
                        value="{{ old('nuit') }}"
                        class="form-control"
                    >

                    @error('nuit')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Contacto
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old('phone') }}"
                        class="form-control"
                    >

                    @error('phone')
                        <div class="error-message">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

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
                >

                @error('email')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror
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
                >

                @error('address')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">
                    Observações
                </label>

                <textarea
                    name="notes"
                    class="form-control"
                    style="
                        min-height:120px;
                        padding-top:14px;
                    "
                >{{ old('notes') }}</textarea>

                @error('notes')
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
                    margin-top:25px;
                "
            >
                <a
                    href="{{ route('customers.index') }}"
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
                    Guardar cliente
                </button>
            </div>

        </form>

    </div>
</div>

@endsection