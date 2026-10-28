@extends('layouts.app')

@section('title', 'Nova Compra | DICO')
@section('topbar-title', 'Compras')

@section('content')

<div class="page-heading">
    <h1>Nova compra</h1>

    <p>
        Registe os dados gerais da compra.
        Os produtos serão adicionados no passo seguinte.
    </p>
</div>

<div class="panel" style="max-width:800px;">
    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('purchases.store') }}"
        >
            @csrf

            <div class="form-group">
                <label class="form-label">
                    Fornecedor
                </label>

                <select
                    name="supplier_id"
                    class="form-control"
                >
                    <option value="">
                        Sem fornecedor
                    </option>

                    @foreach ($suppliers as $supplier)
                        <option
                            value="{{ $supplier->id }}"
                            @selected(
                                old('supplier_id') == $supplier->id
                            )
                        >
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">
                        Data da compra *
                    </label>

                    <input
                        type="date"
                        name="purchase_date"
                        value="{{ old(
                            'purchase_date',
                            now()->format('Y-m-d')
                        ) }}"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Referência do fornecedor
                    </label>

                    <input
                        type="text"
                        name="supplier_reference"
                        value="{{ old('supplier_reference') }}"
                        class="form-control"
                        placeholder="Ex.: FT 00125"
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    Observações
                </label>

                <textarea
                    name="notes"
                    class="form-control"
                    style="
                        min-height:110px;
                        padding-top:14px;
                    "
                >{{ old('notes') }}</textarea>
            </div>

            <div style="
                display:flex;
                justify-content:flex-end;
                gap:10px;
                margin-top:24px;
            ">
                <a
                    href="{{ route('purchases.index') }}"
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
                    Continuar
                </button>
            </div>

        </form>
    </div>
</div>

@endsection