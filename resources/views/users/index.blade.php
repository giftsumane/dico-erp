@extends('layouts.app')

@section('title', 'Utilizadores | DICO')
@section('topbar-title', 'Utilizadores')

@section('content')

@php
    $roleLabels = [
        'owner' => 'Proprietário',
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'cashier' => 'Caixa',
    ];
@endphp

<div class="page-heading">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
        flex-wrap:wrap;
    ">

        <div>

            <h1>
                Utilizadores
            </h1>

            <p>
                Gestão da equipa da
                <strong>
                    {{ $activeCompany->name }}
                </strong>.
            </p>

        </div>


        <a
            href="{{ route('users.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:#fff;
                border-color:var(--primary);
            "
        >
            + Novo utilizador
        </a>

    </div>

</div>


<div class="panel">

    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('users.index') }}"
            style="
                display:flex;
                gap:10px;
                max-width:650px;
                margin-bottom:22px;
            "
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar utilizador..."
            >

            <button
                type="submit"
                class="topbar-button"
            >
                Pesquisar
            </button>


            @if ($search)

                <a
                    href="{{ route('users.index') }}"
                    class="topbar-button"
                >
                    Limpar
                </a>

            @endif

        </form>


        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>

                    <tr>
                        <th>Utilizador</th>
                        <th>Contacto</th>
                        <th>Perfil</th>
                        <th>Lojas</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>

                </thead>


                <tbody>

                    @forelse ($users as $user)

                        <tr>

                            <td>

                                <strong>
                                    {{ $user->name }}
                                </strong>

                                <div class="table-secondary">
                                    {{ $user->email }}
                                </div>

                            </td>


                            <td>
                                {{ $user->phone ?? '—' }}
                            </td>


                            <td>

                                <span class="status-badge">

                                    {{ $roleLabels[
                                        $user->pivot->role
                                    ] ?? $user->pivot->role }}

                                </span>

                            </td>


                            <td>

                                @php
                                    $companyStores = $user
                                        ->stores
                                        ->where(
                                            'company_id',
                                            $activeCompany->id
                                        );
                                @endphp

                                @forelse (
                                    $companyStores
                                    as $userStore
                                )

                                    <div class="table-secondary">
                                        {{ $userStore->name }}
                                    </div>

                                @empty

                                    —

                                @endforelse

                            </td>


                            <td>

                                @if (
                                    $user->pivot->status
                                    === 'active'
                                )

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

                            </td>


                            <td>

                                <a
                                    href="{{ route(
                                        'users.edit',
                                        $user
                                    ) }}"
                                    class="topbar-button"
                                >
                                    Editar
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem utilizadores.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div style="margin-top:20px;">
            {{ $users->links() }}
        </div>

    </div>

</div>

@endsection