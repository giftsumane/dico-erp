@extends('layouts.app')

@section('title', 'Editar Atributo | DICO')
@section('topbar-title', 'Atributos')

@section('content')

<div class="page-heading">

    <h1>
        Editar atributo
    </h1>

    <p>
        Actualize
        <strong>{{ $attribute->name }}</strong>.
    </p>

</div>


<div
    class="panel"
    style="max-width:700px;"
>

    <div class="panel-header">

        <div>

            <h2 class="panel-title">
                Informação
            </h2>

        </div>

    </div>


    <div class="panel-body">

        <form
            method="POST"
            action="{{ route(
                'attributes.update',
                $attribute
            ) }}"
        >

            @csrf
            @method('PUT')


            <div class="form-group">

                <label class="form-label">
                    Nome *
                </label>

                <input
                    type="text"
                    name="name"
                    value="{{ old(
                        'name',
                        $attribute->name
                    ) }}"
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
                    Ordem
                </label>

                <input
                    type="number"
                    name="position"
                    value="{{ old(
                        'position',
                        $attribute->position
                    ) }}"
                    class="form-control"
                    min="0"
                >

            </div>


            <label class="checkbox-control">

                <input
                    type="checkbox"
                    name="active"
                    value="1"
                    @checked(
                        old(
                            'active',
                            $attribute->active
                        )
                    )
                >

                <div>

                    <strong>
                        Atributo activo
                    </strong>

                    <span>
                        Apenas atributos activos estarão disponíveis
                        para novos produtos com variantes.
                    </span>

                </div>

            </label>


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
                        color:#fff;
                        border-color:var(--primary);
                    "
                >
                    Guardar alterações
                </button>

            </div>

        </form>

    </div>

</div>

@endsection