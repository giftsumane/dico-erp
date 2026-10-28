@extends('layouts.app')

@section('title', 'Novo Atributo | DICO')
@section('topbar-title', 'Atributos')

@section('content')

<div class="page-heading">

    <h1>
        Novo atributo
    </h1>

    <p>
        Crie uma característica para variantes de produtos.
    </p>

</div>


<div
    class="panel"
    style="max-width:700px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Informação do atributo
            </h2>

            <p class="panel-subtitle">
                Ex.: Cor, Tamanho, Capacidade ou Material.
            </p>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('attributes.store') }}"
        >

            @csrf


            <div class="form-group">

                <label class="form-label">
                    Nome *
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control"
                    placeholder="Ex.: Cor"
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
                    Ordem
                </label>

                <input
                    type="number"
                    name="position"
                    value="{{ old('position', 0) }}"
                    class="form-control"
                    min="0"
                >

                <small
                    style="color:var(--muted);"
                >
                    Define a ordem de apresentação.
                </small>

            </div>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:10px;
                    margin-top:24px;
                "
            >

                <a
                    href="{{ route('attributes.index') }}"
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
                    Criar atributo
                </button>

            </div>

        </form>

    </div>

</div>

@endsection