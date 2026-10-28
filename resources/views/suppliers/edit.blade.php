@extends('layouts.app')

@section('title', 'Editar Fornecedor | DICO')

@section('topbar-title', 'Fornecedores')

@section('content')

<div class="page-heading">

    <h1>Editar fornecedor</h1>

    <p>
        Actualize os dados de
        <strong>{{ $supplier->name }}</strong>.
    </p>

</div>

<div
    class="panel"
    style="max-width:800px;"
>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route(
                'suppliers.update',
                $supplier
            ) }}"
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
                    value="{{ old(
                        'name',
                        $supplier->name
                    ) }}"
                    class="form-control"
                    required
                >

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        NUIT
                    </label>

                    <input
                        type="text"
                        name="nuit"
                        value="{{ old(
                            'nuit',
                            $supplier->nuit
                        ) }}"
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
                        value="{{ old(
                            'phone',
                            $supplier->phone
                        ) }}"
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
                    value="{{ old(
                        'email',
                        $supplier->email
                    ) }}"
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
                    value="{{ old(
                        'address',
                        $supplier->address
                    ) }}"
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
                >{{ old(
                    'notes',
                    $supplier->notes
                ) }}</textarea>

            </div>

            <div class="form-group">

                <label class="checkbox-control">

                    <input
                        type="checkbox"
                        name="active"
                        value="1"
                        @checked(
                            old(
                                'active',
                                $supplier->active
                            )
                        )
                    >

                    <div>

                        <strong>
                            Fornecedor activo
                        </strong>

                        <span>
                            Fornecedores inactivos deixam de aparecer
                            nas novas compras.
                        </span>

                    </div>

                </label>

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
                    Guardar alterações
                </button>

            </div>

        </form>

    </div>

</div>

@endsection