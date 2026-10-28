@extends('layouts.app')

@section('title', 'Lojas | DICO')
@section('topbar-title', 'Lojas')

@section('content')

<div class="page-heading">

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
        flex-wrap:wrap;
    ">

        <div>
            <h1>Lojas</h1>

            <p>
                Gestão das lojas e estabelecimentos da
                <strong>{{ $activeCompany->name }}</strong>.
            </p>
        </div>

        <a
            href="{{ route('stores.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:#fff;
                border-color:var(--primary);
            "
        >
            + Nova loja
        </a>

    </div>

</div>


<div class="panel">

    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('stores.index') }}"
            style="
                display:flex;
                gap:10px;
                margin-bottom:22px;
                max-width:700px;
            "
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar loja..."
            >

            <button
                type="submit"
                class="topbar-button"
            >
                Pesquisar
            </button>

            @if ($search)

                <a
                    href="{{ route('stores.index') }}"
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
                        <th>Loja</th>
                        <th>Código</th>
                        <th>Contacto</th>
                        <th>Localização</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>

                </thead>


                <tbody>

                    @forelse ($stores as $store)

                        <tr>

                            <td>

                                <strong>
                                    {{ $store->name }}
                                </strong>

                                @if ($store->email)

                                    <div class="table-secondary">
                                        {{ $store->email }}
                                    </div>

                                @endif

                            </td>


                            <td>
                                {{ $store->code ?? '—' }}
                            </td>


                            <td>
                                {{ $store->phone ?? '—' }}
                            </td>


                            <td>
                                {{ $store->address ?? '—' }}
                            </td>


                            <td>

                                @if ($store->is_main)

                                    <span class="status-badge">
                                        Principal
                                    </span>

                                @else

                                    <span class="table-secondary">
                                        Filial
                                    </span>

                                @endif

                            </td>


                            <td>

                                @if ($store->status === 'active')

                                    <span class="status-badge">
                                        Activa
                                    </span>

                                @else

                                    <span class="status-badge status-inactive">
                                        Inactiva
                                    </span>

                                @endif

                            </td>


                            <td>

                                <a
                                    href="{{ route(
                                        'stores.edit',
                                        $store
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
                                colspan="7"
                                style="
                                    padding:50px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem lojas.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        <div style="margin-top:20px;">
            {{ $stores->links() }}
        </div>

    </div>

</div>

@endsection