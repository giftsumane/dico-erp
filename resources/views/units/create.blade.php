@extends('layouts.app')

@section('title', 'Nova Unidade | DICO')

@section('topbar-title', 'Unidades')

@section('content')

<div class="page-heading">
    <h1>Nova unidade</h1>

    <p>
        Registe uma unidade de medida.
    </p>
</div>

<div class="panel" style="max-width:700px;">
    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('units.store') }}"
        >
            @csrf

            <div class="form-group">
                <label class="form-label">
                    Nome
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control"
                    placeholder="Ex.: Unidade"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">
                    Símbolo
                </label>

                <input
                    type="text"
                    name="symbol"
                    value="{{ old('symbol') }}"
                    class="form-control"
                    placeholder="Ex.: un"
                    required
                >
            </div>

            <div style="
                display:flex;
                justify-content:flex-end;
                gap:10px;
                margin-top:25px;
            ">
                <a
                    href="{{ route('units.index') }}"
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
                    Guardar unidade
                </button>
            </div>

        </form>

    </div>
</div>

@endsection