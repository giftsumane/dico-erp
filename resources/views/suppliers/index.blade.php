@extends('layouts.app')

@section('title', 'Fornecedores | DICO')

@section('topbar-title', 'Fornecedores')

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
            <h1>Fornecedores</h1>

            <p>
                Gerir fornecedores da empresa.
            </p>
        </div>

        <a
            href="{{ route('suppliers.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:white;
                border-color:var(--primary);
            "
        >
            + Novo fornecedor
        </a>

    </div>

</div>

<div class="panel">

    <div class="panel-header">

        <div>
            <h2 class="panel-title">
                Lista de fornecedores
            </h2>

            <p class="panel-subtitle">
                Empresa: {{ $activeCompany->name }}
            </p>
        </div>

    </div>

    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('suppliers.index') }}"
            style="
                display:flex;
                gap:10px;
                margin-bottom:22px;
                max-width:600px;
            "
        >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar fornecedor..."
            >

            <button
                type="submit"
                class="topbar-button"
            >
                Pesquisar
            </button>

            @if ($search)

                <a
                    href="{{ route('suppliers.index') }}"
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
                        <th>Fornecedor</th>
                        <th>NUIT</th>
                        <th>Contacto</th>
                        <th>E-mail</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($suppliers as $supplier)

                        <tr>

                            <td>
                                <strong>
                                    {{ $supplier->name }}
                                </strong>

                                @if ($supplier->address)
                                    <div class="table-secondary">
                                        {{ $supplier->address }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                {{ $supplier->nuit ?? '—' }}
                            </td>

                            <td>
                                {{ $supplier->phone ?? '—' }}
                            </td>

                            <td>
                                {{ $supplier->email ?? '—' }}
                            </td>

                            <td>

                                @if ($supplier->active)

                                    <span class="status-badge">
                                        Activo
                                    </span>

                                @else

                                    <span class="status-badge status-inactive">
                                        Inactivo
                                    </span>

                                @endif

                            </td>

                            <td>
                                <a
                                    href="{{ route(
                                        'suppliers.edit',
                                        $supplier
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
                                Ainda não existem fornecedores.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">
            {{ $suppliers->links() }}
        </div>

    </div>

</div>

@endsection