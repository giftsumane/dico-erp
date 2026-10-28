@extends('layouts.app')

@section('title', 'Novo Utilizador | DICO')
@section('topbar-title', 'Utilizadores')

@section('content')

<div class="page-heading">

    <h1>
        Novo utilizador
    </h1>

    <p>
        Crie um acesso para um membro da equipa.
    </p>

</div>


<div
    class="panel"
    style="max-width:900px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Dados do utilizador
            </h2>

            <p class="panel-subtitle">
                Defina também o perfil e as lojas disponíveis.
            </p>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('users.store') }}"
        >

            @csrf


            <div class="form-row">

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
                    E-mail *
                </label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control"
                    required
                >

                @error('email')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label class="form-label">
                        Palavra-passe *
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Confirmar palavra-passe *
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label class="form-label">
                    Perfil *
                </label>

                <select
                    name="role"
                    class="form-control"
                    required
                >

                    <option value="">
                        Seleccionar perfil
                    </option>

                    <option
                        value="admin"
                        @selected(
                            old('role') === 'admin'
                        )
                    >
                        Administrador
                    </option>

                    <option
                        value="manager"
                        @selected(
                            old('role') === 'manager'
                        )
                    >
                        Gerente
                    </option>

                    <option
                        value="cashier"
                        @selected(
                            old('role') === 'cashier'
                        )
                    >
                        Caixa
                    </option>

                </select>

                @error('role')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror

            </div>


            <div class="form-group">

                <label class="form-label">
                    Acesso às lojas
                </label>


                <div class="store-access-grid">

                    @forelse ($stores as $store)

                        <label class="store-access-item">

                            <input
                                type="checkbox"
                                name="stores[]"
                                value="{{ $store->id }}"
                                @checked(
                                    in_array(
                                        $store->id,
                                        old('stores', [])
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

                    @empty

                        <div class="empty-state">
                            Não existem lojas disponíveis.
                        </div>

                    @endforelse

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
                    Criar utilizador
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