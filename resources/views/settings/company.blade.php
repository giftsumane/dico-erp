@extends('layouts.app')

@section('title', 'Configurações | DICO')
@section('topbar-title', 'Configurações')

@section('content')

<div class="page-heading">
    <h1>Configurações da empresa</h1>

    <p>
        Dados comerciais, fiscais e operacionais da
        <strong>{{ $company->name }}</strong>.
    </p>
</div>

<div
    class="panel"
    style="max-width:950px;"
>
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Dados da empresa
            </h2>

            <p class="panel-subtitle">
                Estas informações serão utilizadas em recibos,
                documentos e configurações do sistema.
            </p>
        </div>
    </div>

    <div class="panel-body">

        <form
            method="POST"
            action="{{ route('settings.company.update') }}"
        >
            @csrf
            @method('PUT')

            <div class="form-row">

                <div class="form-group">
                    <label class="form-label">
                        Nome no DICO *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $company->name) }}"
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
                        Nome comercial
                    </label>

                    <input
                        type="text"
                        name="trade_name"
                        value="{{ old(
                            'trade_name',
                            $company->trade_name
                        ) }}"
                        class="form-control"
                    >
                </div>

            </div>

            <div class="form-group">
                <label class="form-label">
                    Denominação legal
                </label>

                <input
                    type="text"
                    name="legal_name"
                    value="{{ old(
                        'legal_name',
                        $company->legal_name
                    ) }}"
                    class="form-control"
                >
            </div>

            <div class="form-row">

                <div class="form-group">
                    <label class="form-label">
                        NUIT
                    </label>

                    <input
                        type="text"
                        name="nuit"
                        value="{{ old(
                            'nuit',
                            $company->nuit
                        ) }}"
                        class="form-control"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Contacto
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="{{ old(
                            'phone',
                            $company->phone
                        ) }}"
                        class="form-control"
                    >
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label class="form-label">
                        E-mail
                    </label>

                    <input
                        type="email"
                        name="email"
                        value="{{ old(
                            'email',
                            $company->email
                        ) }}"
                        class="form-control"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Endereço
                    </label>

                    <input
                        type="text"
                        name="address"
                        value="{{ old(
                            'address',
                            $company->address
                        ) }}"
                        class="form-control"
                    >
                </div>

            </div>

            <div class="form-row">

                <div class="form-group">
                    <label class="form-label">
                        Moeda
                    </label>

                    <select
                        name="currency"
                        class="form-control"
                        required
                    >
                        @foreach ([
                            'MZN' => 'Metical (MZN)',
                            'USD' => 'Dólar (USD)',
                            'ZAR' => 'Rand (ZAR)',
                            'EUR' => 'Euro (EUR)',
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                @selected(
                                    old(
                                        'currency',
                                        $company->currency
                                    ) === $value
                                )
                            >
                                {{ $label }}
                            </option>

                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Fuso horário
                    </label>

                    <select
                        name="timezone"
                        class="form-control"
                        required
                    >
                        <option
                            value="Africa/Maputo"
                            @selected(
                                old(
                                    'timezone',
                                    $company->timezone
                                ) === 'Africa/Maputo'
                            )
                        >
                            Africa/Maputo
                        </option>

                        <option
                            value="UTC"
                            @selected(
                                old(
                                    'timezone',
                                    $company->timezone
                                ) === 'UTC'
                            )
                        >
                            UTC
                        </option>
                    </select>
                </div>

            </div>

            <div class="form-group">
                <label class="form-label">
                    IVA padrão (%)
                </label>

                <input
                    type="number"
                    name="default_tax_rate"
                    value="{{ old(
                        'default_tax_rate',
                        $company->default_tax_rate
                    ) }}"
                    class="form-control"
                    min="0"
                    max="100"
                    step="0.01"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">
                    Rodapé do recibo
                </label>

                <textarea
                    name="receipt_footer"
                    class="form-control"
                    style="
                        min-height:120px;
                        padding-top:14px;
                    "
                    placeholder="Ex.: Obrigado pela preferência."
                >{{ old(
                    'receipt_footer',
                    $company->receipt_footer
                ) }}</textarea>
            </div>

            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    margin-top:28px;
                "
            >
                <button
                    type="submit"
                    class="topbar-button"
                    style="
                        background:var(--primary);
                        color:#fff;
                        border-color:var(--primary);
                    "
                >
                    Guardar configurações
                </button>
            </div>

        </form>

    </div>
</div>

@endsection