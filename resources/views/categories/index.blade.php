@extends('layouts.app')

@section('title', 'Categorias | DICO')

@section('topbar-title', 'Categorias')

@section('content')

<div class="page-heading">
    <div style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:20px;
    ">
        <div>
            <h1>Categorias</h1>

            <p>
                Organize os produtos da empresa por categoria.
            </p>
        </div>

        <a
            href="{{ route('categories.create') }}"
            class="topbar-button"
            style="
                background: var(--primary);
                color: white;
                border-color: var(--primary);
            "
        >
            Nova categoria
        </a>
    </div>
</div>

<div class="panel">
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Lista de categorias
            </h2>

            <p class="panel-subtitle">
                Empresa: {{ $activeCompany->name }}
            </p>
        </div>
    </div>

    <div class="panel-body">

        @forelse($categories as $category)

            <div class="company-item">

                <div>
                    <div class="company-name">
                        {{ $category->name }}
                    </div>

                    <div class="company-meta">
                        {{ $category->description ?? 'Sem descrição' }}
                    </div>
                </div>

                <span class="status-badge">
                    {{ $category->active ? 'Activa' : 'Inactiva' }}
                </span>

            </div>

        @empty

            <div class="empty-state">
                Ainda não existem categorias.
            </div>

        @endforelse

        <div style="margin-top:20px;">
            {{ $categories->links() }}
        </div>

    </div>
</div>

@endsection