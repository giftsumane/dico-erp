@extends('layouts.app')

@section('title', 'Novo Fornecedor | DICO')

@section('topbar-title', 'Fornecedores')

@section('content')

<div class="page-heading">

    <h1>Novo fornecedor</h1>

    <p>
        Registe um fornecedor da
        {{ $activeCompany->name }}.
    </p>

</div>

<div
    class="panel"
    style="max-width:800px;"
>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('suppliers.store') }}"
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

            </div>

            <div style="
                display:flex;
                justify-content:flex-end;
                gap:10px;
                margin-top:24px;
            ">

                <a
                    href="{{ route('suppliers.index') }}"
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
                    Guardar fornecedor
                </button>

            </div>

        </form>

    </div>

</div>

@endsection