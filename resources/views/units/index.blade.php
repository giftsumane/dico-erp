@extends('layouts.app')

@section('title', 'Unidades | DICO')

@section('topbar-title', 'Unidades')

@section('content')

<div class="page-heading">
    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
    ">
        <div>
            <h1>Unidades</h1>

            <p>
                Defina as unidades utilizadas pelos produtos.
            </p>
        </div>

        <a
            href="{{ route('units.create') }}"
            class="topbar-button"
            style="
                background:var(--primary);
                color:white;
                border-color:var(--primary);
            "
        >
            Nova unidade
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Lista de unidades
            </h2>

            <p class="panel-subtitle">
                Empresa: {{ $activeCompany->name }}
            </p>
        </div>
    </div>

    <div class="panel-body">

        <div class="company-list">
            @forelse($units as $unit)

                <div class="company-item">

                    <div>
                        <div class="company-name">
                            {{ $unit->name }}
                        </div>

                        <div class="company-meta">
                            Símbolo: {{ $unit->symbol }}
                        </div>
                    </div>

                    <span class="status-badge">
                        {{ $unit->active ? 'Activa' : 'Inactiva' }}
                    </span>

                </div>

            @empty

                <div class="empty-state">
                    Ainda não existem unidades.
                </div>

            @endforelse
        </div>

        <div style="margin-top:20px;">
            {{ $units->links() }}
        </div>

    </div>
</div>

@endsection