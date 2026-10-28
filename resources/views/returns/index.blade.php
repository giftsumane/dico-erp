@extends('layouts.app')
@section('title', 'Devoluções | DICO')
@section('topbar-title', 'Devoluções')
@section('content')
<div class="page-heading"><h1>Devoluções</h1><p>Consulte as devoluções registadas e os respectivos reembolsos.</p></div>
<div class="panel">
    <div class="panel-header"><h2 class="panel-title">Pesquisar devoluções</h2></div>
    <div class="panel-body">
        <form method="GET" action="{{ route('returns.index') }}">
            <div class="form-row">
                <div class="form-group"><label class="form-label" for="q">Devolução, venda ou cliente</label><input class="form-control" id="q" name="q" value="{{ request('q') }}" placeholder="DEV-, venda ou cliente"></div>
                <div class="form-group"><label class="form-label" for="status">Estado</label><select class="form-control" id="status" name="status"><option value="">Todos</option><option value="completed" @selected(request('status') === 'completed')>Concluída</option><option value="cancelled" @selected(request('status') === 'cancelled')>Cancelada</option></select></div>
                <div class="form-group"><label class="form-label" for="from">De</label><input class="form-control" type="date" id="from" name="from" value="{{ request('from') }}"></div>
                <div class="form-group"><label class="form-label" for="to">Até</label><input class="form-control" type="date" id="to" name="to" value="{{ request('to') }}"></div>
            </div>
            <button type="submit" class="topbar-button">Filtrar</button> <a href="{{ route('returns.index') }}" class="topbar-button">Limpar</a>
        </form>
    </div>
</div>
<div class="panel" style="margin-top:22px">
    <div class="panel-header"><h2 class="panel-title">Registos ({{ $returns->total() }})</h2></div>
    <div class="panel-body">
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead><tr><th>Devolução</th><th>Venda / Cliente</th><th>Data</th><th>Loja / Operador</th><th>Tipo / Estado</th><th>Valor</th><th>Reembolsado</th><th></th></tr></thead>
                <tbody>
                    @forelse($returns as $return)
                        <tr>
                            <td><strong>{{ $return->number }}</strong></td>
                            <td><a href="{{ route('sales.show', $return->sale) }}"><strong>{{ $return->sale?->number ?? '—' }}</strong></a><div class="table-secondary">{{ $return->sale?->customer?->name ?? 'Consumidor final' }}</div></td>
                            <td>{{ $return->returned_at?->format('d/m/Y H:i') ?? $return->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $return->store?->name ?? '—' }}<div class="table-secondary">{{ $return->user?->name ?? '—' }}</div></td>
                            <td>{{ $return->return_type === 'full' ? 'Total' : 'Parcial' }}<div class="table-secondary">{{ $return->status === 'completed' ? 'Concluída' : 'Cancelada' }}</div></td>
                            <td>{{ number_format((float)$return->total, 2, ',', '.') }} MT</td>
                            <td>{{ number_format((float)$return->refund_amount, 2, ',', '.') }} MT</td>
                            <td><a class="topbar-button" href="{{ route('returns.show', $return) }}">Ver detalhes</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="empty-state">Não foram encontradas devoluções.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px">{{ $returns->links() }}</div>
    </div>
</div>
@endsection
