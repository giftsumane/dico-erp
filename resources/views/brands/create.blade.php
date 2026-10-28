@extends('layouts.app')

@section('title', 'Nova Marca | DICO')

@section('topbar-title', 'Marcas')

@section('content')

<div class="page-heading">
    <h1>Nova marca</h1>

    <p>
        Registe uma marca para associar aos produtos.
    </p>
</div>

<div class="panel" style="max-width:700px;">
    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('brands.store') }}"
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
                    required
                >

                @error('name')
                    <div class="error-message">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">
                    Descrição
                </label>

                <textarea
                    name="description"
                    class="form-control"
                    style="min-height:120px;padding-top:14px;"
                >{{ old('description') }}</textarea>
            </div>

            <div style="
                display:flex;
                justify-content:flex-end;
                gap:10px;
                margin-top:25px;
            ">
                <a
                    href="{{ route('brands.index') }}"
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
                    Guardar marca
                </button>
            </div>
        </form>

    </div>
</div>

@endsection