@extends('layouts.app')

@section('title', 'Editar Utilizador | DICO')
@section('topbar-title', 'Utilizadores')

@section('content')

<div class="page-heading">

    <h1>
        Editar utilizador
    </h1>

    <p>
        Actualize o acesso de
        <strong>{{ $user->name }}</strong>.
    </p>

</div>


<div
    class="panel"
    style="max-width:900px;"
>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route(
                'users.update',
                $user
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Nome *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old(
                            'name',
                            $user->name
                        ) }}"
                        class="form-control"
                        required
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
                            $user->phone
                        ) }}"
                        class="form-control"
                    >

                </div>

            </div>


            <div class="form-group">

                <label class="form-label">
                    E-mail *
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old(
                        'email',
                        $user->email
                    ) }}"
                    class="form-control"
                    required
                >

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Nova palavra-passe
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                    >

                    <div class="table-secondary">
                        Deixe vazio para manter a actual.
                    </div>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Confirmar palavra-passe
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Perfil
                    </label>

                    <select
                        name="role"
                        class="form-control"
                        @disabled($pivot->is_owner)
                    >

                        @if ($pivot->is_owner)

                            <option value="owner">
                                Proprietário
                            </option>

                        @else

                            <option
                                value="admin"
                                @selected(
                                    old(
                                        'role',
                                        $pivot->role
                                    ) === 'admin'
                                )
                            >
                                Administrador
                            </option>

                            <option
                                value="manager"
                                @selected(
                                    old(
                                        'role',
                                        $pivot->role
                                    ) === 'manager'
                                )
                            >
                                Gerente
                            </option>

                            <option
                                value="cashier"
                                @selected(
                                    old(
                                        'role',
                                        $pivot->role
                                    ) === 'cashier'
                                )
                            >
                                Caixa
                            </option>

                        @endif

                    </select>

                    @if ($pivot->is_owner)

                        <input
                            type="hidden"
                            name="role"
                            value="owner"
                        >

                    @endif

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Estado
                    </label>

                    <select
                        name="status"
                        class="form-control"
                        @disabled($pivot->is_owner)
                    >

                        <option
                            value="active"
                            @selected(
                                old(
                                    'status',
                                    $pivot->status
                                ) === 'active'
                            )
                        >
                            Activo
                        </option>

                        <option
                            value="inactive"
                            @selected(
                                old(
                                    'status',
                                    $pivot->status
                                ) === 'inactive'
                            )
                        >
                            Inactivo
                        </option>

                    </select>

                    @if ($pivot->is_owner)

                        <input
                            type="hidden"
                            name="status"
                            value="active"
                        >

                    @endif

                </div>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Acesso às lojas
                </label>


                <div class="store-access-grid">

                    @foreach ($stores as $store)

                        <label class="store-access-item">

                            <input
                                type="checkbox"
                                name="stores[]"
                                value="{{ $store->id }}"
                                @checked(
                                    in_array(
                                        $store->id,
                                        old(
                                            'stores',
                                            $selectedStores
                                        )
                                    )
                                )
                            >

                            <div>

                                <strong>
                                    {{ $store->name }}
                                </strong>

                                <span>
                                    {{ $store->is_main
                                        ? 'Loja principal'
                                        : (
                                            $store->code
                                                ?? 'Filial'
                                        )
                                    }}
                                </span>

                            </div>

                        </label>

                    @endforeach

                </div>

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
                    href="{{ route('users.index') }}"
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


@section('styles')

.store-access-grid {
    display:grid;
    grid-template-columns:
        repeat(2,minmax(0,1fr));
    gap:12px;
}

.store-access-item {
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:15px;
    border:1px solid var(--border);
    border-radius:12px;
    cursor:pointer;
}

.store-access-item:hover {
    border-color:var(--primary);
}

.store-access-item input {
    margin-top:3px;
    accent-color:var(--primary);
}

.store-access-item strong {
    display:block;
    font-size:13px;
}

.store-access-item span {
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-size:11px;
}

@media (max-width:650px) {

    .store-access-grid {
        grid-template-columns:1fr;
    }

}

@endsection