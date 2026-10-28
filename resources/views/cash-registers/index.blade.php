@extends('layouts.app')

@section('title', 'Caixa | DICO')
@section('topbar-title', 'Caixa')

@section('content')

<div class="page-heading">
    <h1>Caixa</h1>

    <p>
        Gestão de abertura e fecho do caixa da loja
        <strong>{{ $activeStore?->name }}</strong>.
    </p>
</div>

@if (! $currentRegister)

    <div
        class="panel"
        style="max-width:650px;"
    >
        <div class="panel-header">
            <div>
                <h2 class="panel-title">
                    Abrir caixa
                </h2>

                <p class="panel-subtitle">
                    Informe o valor inicial disponível.
                </p>
            </div>
        </div>

        <div class="panel-body">

            <form
                method="POST"
                action="{{ route('cash-registers.open') }}"
            >
                @csrf

                <div class="form-group">
                    <label class="form-label">
                        Fundo inicial
                    </label>

                    <input
                        type="number"
                        name="opening_amount"
                        value="{{ old('opening_amount', 0) }}"
                        class="form-control"
                        min="0"
                        step="0.01"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Observação
                    </label>

                    <textarea
                        name="opening_notes"
                        class="form-control"
                        style="min-height:100px;padding-top:14px;"
                    >{{ old('opening_notes') }}</textarea>
                </div>

                <button
                    type="submit"
                    class="topbar-button"
                    style="
                        background:var(--primary);
                        color:white;
                        border-color:var(--primary);
                    "
                >
                    Abrir caixa
                </button>
            </form>

        </div>
    </div>

@else

    <div class="metric-grid">

            <div class="metric-card">

                <div class="metric-card-header">

                    <div class="metric-label">
                        Fundo inicial
                    </div>

                    <div class="metric-icon">
                        MT
                    </div>

                </div>

                <div class="metric-value">
                    {{ number_format(
                        $currentSummary['opening_amount'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="metric-description">
                    Valor existente na abertura do caixa
                </div>

            </div>


            <div class="metric-card">

                <div class="metric-card-header">

                    <div class="metric-label">
                        Vendas em dinheiro
                    </div>

                    <div class="metric-icon">
                        +
                    </div>

                </div>

                <div class="metric-value">
                    {{ number_format(
                        $currentSummary['cash_sales'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="metric-description">
                    {{ $currentSummary['sales_count'] }}
                    {{ $currentSummary['sales_count'] == 1
                        ? 'venda realizada'
                        : 'vendas realizadas'
                    }}
                </div>

            </div>


            <div class="metric-card">

                <div class="metric-card-header">

                    <div class="metric-label">
                        Entradas manuais
                    </div>

                    <div class="metric-icon">
                        +
                    </div>

                </div>

                <div class="metric-value">
                    {{ number_format(
                        $currentSummary['manual_in'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="metric-description">
                    Dinheiro adicionado manualmente
                </div>

            </div>


            <div class="metric-card">

                <div class="metric-card-header">

                    <div class="metric-label">
                        Saídas manuais
                    </div>

                    <div class="metric-icon">
                        −
                    </div>

                </div>

                <div class="metric-value">
                    {{ number_format(
                        $currentSummary['manual_out'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="metric-description">
                    Dinheiro retirado do caixa
                </div>

            </div>

    </div>

    <div class="panel" style="margin-top:22px; border:1px solid var(--primary);">

        <div
            class="panel-body"
            style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:25px;
                flex-wrap:wrap;
            "
        >

            <div>

                <div
                    style="
                        color:var(--muted);
                        font-size:13px;
                        font-weight:700;
                    "
                >
                    Saldo esperado no caixa
                </div>

                <div
                    style="
                        margin-top:7px;
                        color:var(--muted);
                        font-size:12px;
                    "
                >
                    Fundo inicial + vendas em dinheiro
                    + entradas − saídas
                </div>

            </div>


            <div
                style="
                    font-size:32px;
                    font-weight:850;
                    letter-spacing:-0.04em;
                    color:var(--primary-dark);
                "
            >

                {{ number_format(
                    $currentSummary['expected_balance'],
                    2,
                    ',',
                    '.'
                ) }}

                <span
                    style="
                        font-size:15px;
                        font-weight:700;
                    "
                >
                    MT
                </span>

            </div>

        </div>

    </div>

    <div
        class="dashboard-grid"
        style="margin-top:22px;"
    >

        <div class="panel">

            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        Movimento manual
                    </h2>

                    <p class="panel-subtitle">
                        Registar entrada ou saída de dinheiro.
                    </p>
                </div>
            </div>

            <div class="panel-body">

                <form
                    method="POST"
                    action="{{ route(
                        'cash-registers.movements.store',
                        $currentRegister
                    ) }}"
                >
                    @csrf

                    <div class="form-group">
                        <label class="form-label">
                            Tipo
                        </label>

                        <select
                            name="type"
                            class="form-control"
                            required
                        >
                            <option value="in">
                                Entrada
                            </option>

                            <option value="out">
                                Saída
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Valor
                        </label>

                        <input
                            type="number"
                            name="amount"
                            class="form-control"
                            min="0.01"
                            step="0.01"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Motivo
                        </label>

                        <input
                            type="text"
                            name="reason"
                            class="form-control"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        class="topbar-button"
                    >
                        Registar
                    </button>

                </form>

            </div>
        </div>

        <div class="panel">

            <div class="panel-header">
                <div>
                    <h2 class="panel-title">
                        Fechar caixa
                    </h2>

                    <p class="panel-subtitle">
                        Informe o dinheiro contado fisicamente.
                    </p>
                </div>
            </div>

            <div class="panel-body">

                <form
                    method="POST"
                    action="{{ route(
                        'cash-registers.close',
                        $currentRegister
                    ) }}"
                    onsubmit="
                        return confirm(
                            'Tens a certeza que pretendes fechar o caixa?'
                        );
                    "
                >
                    @csrf

                    <div class="form-group">
                        <div
                            style="
                                margin-bottom:20px;
                                padding:15px;
                                border-radius:12px;
                                background:var(--primary-soft);
                            "
                        >

                            <div
                                style="
                                    color:var(--muted);
                                    font-size:12px;
                                "
                            >
                                O sistema espera encontrar
                            </div>

                            <div
                                style="
                                    margin-top:5px;
                                    font-size:23px;
                                    font-weight:850;
                                    color:var(--primary-dark);
                                "
                            >
                                {{ number_format(
                                    $currentSummary['expected_balance'],
                                    2,
                                    ',',
                                    '.'
                                ) }}
                                MT
                            </div>

                        </div>
                        <label class="form-label">
                            Valor contado
                        </label>

                        <input
                            type="number"
                            name="closing_amount"
                            id="closing-amount"
                            class="form-control"
                            min="0"
                            step="0.01"
                            required
                            oninput="calculateCashDifference()"
                        >
                    </div>

                    <div id="cash-difference-box" style="display:none;margin-bottom:20px;padding:14px;border-radius:12px;background:#f4f7f6;">

                        <div style="display:flex;justify-content:space-between;gap:20px;">

                            <span>
                                Diferença
                            </span>

                            <strong id="cash-difference-value">
                                0,00 MT
                            </strong>

                        </div>

                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Observações
                        </label>

                        <textarea
                            name="closing_notes"
                            class="form-control"
                            style="min-height:100px;padding-top:14px;"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="topbar-button"
                        style="
                            background:#b42318;
                            color:#fff;
                            border-color:#b42318;
                        "
                    >
                        Fechar caixa
                    </button>

                </form>

            </div>
        </div>

    </div>

@endif

<div
    class="panel"
    style="margin-top:22px;"
>
    <div class="panel-header">
        <div>
            <h2 class="panel-title">
                Histórico de caixas
            </h2>
        </div>
    </div>

    <div class="panel-body">

        <div style="overflow-x:auto;">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Abertura</th>
                        <th>Operador</th>
                        <th>Inicial</th>
                        <th>Esperado</th>
                        <th>Contado</th>
                        <th>Diferença</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse ($registers as $register)

                        <tr>

                            <td>
                                {{ $register->opened_at
                                    ?->format('d/m/Y H:i') }}
                            </td>

                            <td>
                                {{ $register->user->name }}
                            </td>

                            <td>
                                {{ number_format(
                                    $register->opening_amount,
                                    2,
                                    ',',
                                    '.'
                                ) }}
                                MT
                            </td>

                            <td>
                                {{ $register->expected_amount !== null
                                    ? number_format(
                                        $register->expected_amount,
                                        2,
                                        ',',
                                        '.'
                                    ) . ' MT'
                                    : '—'
                                }}
                            </td>

                            <td>
                                {{ $register->closing_amount !== null
                                    ? number_format(
                                        $register->closing_amount,
                                        2,
                                        ',',
                                        '.'
                                    ) . ' MT'
                                    : '—'
                                }}
                            </td>

                            <td>
                                {{ $register->difference !== null
                                    ? number_format(
                                        $register->difference,
                                        2,
                                        ',',
                                        '.'
                                    ) . ' MT'
                                    : '—'
                                }}
                            </td>

                            <td>
                                <span class="status-badge">
                                    {{ $register->status === 'open'
                                        ? 'Aberto'
                                        : 'Fechado'
                                    }}
                                </span>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td
                                colspan="7"
                                style="
                                    padding:45px;
                                    text-align:center;
                                    color:var(--muted);
                                "
                            >
                                Ainda não existem caixas.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div style="margin-top:20px;">
            {{ $registers->links() }}
        </div>

    </div>
</div>
@endsection
@section('scripts')

@if ($currentRegister && $currentSummary)

<script>
    function calculateCashDifference() {
        const expected =
            {{ (float) ($currentSummary['expected_balance'] ?? 0) }};

        const input =
            document.getElementById('closing-amount');

        const box =
            document.getElementById(
                'cash-difference-box'
            );

        const output =
            document.getElementById(
                'cash-difference-value'
            );

        if (!input || !box || !output) {
            return;
        }

        if (!input.value) {
            box.style.display = 'none';
            return;
        }

        const counted =
            parseFloat(input.value || 0);

        const difference =
            counted - expected;

        output.textContent =
            new Intl.NumberFormat('pt-PT', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(difference)
            + ' MT';

        box.style.display = 'block';

        if (difference < 0) {
            output.style.color = '#b42318';
        } else if (difference > 0) {
            output.style.color = '#8a5c00';
        } else {
            output.style.color = 'var(--primary-dark)';
        }
    }
</script>

@endif

@endsection