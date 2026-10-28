@extends('layouts.app')

@section('title', 'Clientes | DICO')
@section('topbar-title', 'Clientes')

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
            <h1>Clientes</h1>
            <p>Gestão de clientes da empresa.</p>
        </div>

        <a
            href="{{ route('customers.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:#fff;
                border-color:var(--primary);
            "
        >
            + Novo cliente
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-body">

        <form
            method="GET"
            action="{{ route('customers.index') }}"
            style="
                display:flex;
                gap:10px;
                margin-bottom:22px;
                max-width:650px;
            "
        >
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                class="form-control"
                placeholder="Pesquisar cliente..."
            >

            <button
                type="submit"
                class="topbar-button"
            >
                Pesquisar
            </button>

            @if ($search)
                <a
                    href="{{ route('customers.index') }}"
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
                        <th>Cliente</th>
                        <th>NUIT</th>
                        <th>Contacto</th>
                        <th>E-mail</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                <strong>{{ $customer->name }}</strong>

                                @if ($customer->address)
                                    <div class="table-secondary">
                                        {{ $customer->address }}
                                    </div>
                                @endif
                            </td>

                            <td>{{ $customer->nuit ?? '—' }}</td>
                            <td>{{ $customer->phone ?? '—' }}</td>
                            <td>{{ $customer->email ?? '—' }}</td>

                            <td>
                                <span class="status-badge">
                                    {{ $customer->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>

                            <td>
                                <a
                                    href="{{ route('customers.edit', $customer) }}"
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
                                Ainda não existem clientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top:20px;">
            {{ $customers->links() }}
        </div>

    </div>
</div>

@endsection