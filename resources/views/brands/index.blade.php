@extends('layouts.app')

@section('title', 'Marcas | DICO')

@section('topbar-title', 'Marcas')

@section('content')

<div class="page-heading">
    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
    ">
        <div>
            <h1>Marcas</h1>

            <p>
                Gerir marcas utilizadas nos produtos.
            </p>
        </div>

        <a
            href="{{ route('brands.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:white;
                border-color:var(--primary);
            "
        >
            Nova marca
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Lista de marcas
            </h2>

            <p class="panel-subtitle">
                Empresa: {{ $activeCompany->name }}
            </p>
        </div>
    </div>

    <div class="panel-body">

        <div class="company-list">
            @forelse($brands as $brand)

                <div class="company-item">

                    <div>
                        <div class="company-name">
                            {{ $brand->name }}
                        </div>

                        <div class="company-meta">
                            {{ $brand->description ?? 'Sem descrição' }}
                        </div>
                    </div>

                    <span class="status-badge">
                        {{ $brand->active ? 'Activa' : 'Inactiva' }}
                    </span>

                </div>

            @empty

                <div class="empty-state">
                    Ainda não existem marcas.
                </div>

            @endforelse
        </div>

        <div style="margin-top:20px;">
            {{ $brands->links() }}
        </div>

    </div>
</div>

@endsection