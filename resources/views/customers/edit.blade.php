@extends('layouts.app')

@section('title', 'Editar Cliente | DICO')
@section('topbar-title', 'Clientes')

@section('content')

<div class="page-heading">
    <h1>Editar cliente</h1>

    <p>
        Actualize os dados de
        <strong>{{ $customer->name }}</strong>.
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
        </div>
    </div>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('customers.update', $customer) }}"
        >
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">
                    Nome *
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name', $customer->name) }}"
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
                        value="{{ old('nuit', $customer->nuit) }}"
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
                        value="{{ old('phone', $customer->phone) }}"
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
                    value="{{ old('email', $customer->email) }}"
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
                    value="{{ old('address', $customer->address) }}"
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
                >{{ old('notes', $customer->notes) }}</textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-control">

                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        @checked(old('active', $customer->active))
                    >

                    <div>
                        <strong>
                            Cliente activo
                        </strong>

                        <span>
                            Clientes inactivos deixam de aparecer
                            na selecção do POS.
                        </span>
                    </div>

                </label>
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
                    Guardar alterações
                </button>
            </div>

        </form>

    </div>
</div>

@endsection