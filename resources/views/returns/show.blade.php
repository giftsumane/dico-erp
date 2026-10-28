@extends('layouts.app')
@section('title', ($saleReturn->number ?? 'Devolução') . ' | DICO')
@section('topbar-title', 'Detalhes da devolução')
@section('content')
<div class="page-heading" style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:center">
    <div><h1>{{ $saleReturn->number }}</h1><p>Registada em {{ $saleReturn->returned_at?->format('d/m/Y H:i') ?? $saleReturn->created_at?->format('d/m/Y H:i') }}</p></div>
    <div><a class="topbar-button" href="{{ route('returns.index') }}">Voltar</a> <a class="topbar-button" href="{{ route('sales.show', $saleReturn->sale) }}">Ver venda original</a></div>
</div>
<div class="metric-grid">
    <div class="metric-card"><div class="metric-label">Valor da devolução</div><div class="metric-value">{{ number_format((float)$saleReturn->total,2,',','.') }}</div><div class="metric-description">MT</div></div>
    <div class="metric-card"><div class="metric-label">Valor reembolsado</div><div class="metric-value">{{ number_format((float)$saleReturn->refund_amount,2,',','.') }}</div><div class="metric-description">MT</div></div>
    <div class="metric-card"><div class="metric-label">Tipo</div><div class="metric-value" style="font-size:20px">{{ $saleReturn->return_type === 'full' ? 'Total' : 'Parcial' }}</div></div>
    <div class="metric-card"><div class="metric-label">Estado</div><div class="metric-value" style="font-size:20px">{{ $saleReturn->status === 'completed' ? 'Concluída' : 'Cancelada' }}</div></div>
</div>
<div class="panel" style="margin-top:22px"><div class="panel-header"><h2 class="panel-title">Informações</h2></div><div class="panel-body">
    <div class="form-row">
        <div><div class="table-secondary">Venda original</div><strong>{{ $saleReturn->sale?->number ?? '—' }}</strong></div>
        <div><div class="table-secondary">Cliente</div><strong>{{ $saleReturn->sale?->customer?->name ?? 'Consumidor final' }}</strong></div>
        <div><div class="table-secondary">Loja</div><strong>{{ $saleReturn->store?->name ?? '—' }}</strong></div>
        <div><div class="table-secondary">Operador</div><strong>{{ $saleReturn->user?->name ?? '—' }}</strong></div>
        <div><div class="table-secondary">Método de reembolso</div><strong>{{ ['none'=>'Sem reembolso','cash'=>'Dinheiro','other'=>'Outro'][$saleReturn->refund_method] ?? $saleReturn->refund_method }}</strong></div>
        <div><div class="table-secondary">Referência</div><strong>{{ $saleReturn->refund_reference ?: '—' }}</strong></div>
        <div><div class="table-secondary">Caixa do reembolso</div><strong>{{ $saleReturn->cash_register_id ? '#'.$saleReturn->cash_register_id : '—' }}</strong></div>
    </div>
    @if($saleReturn->reason)<p><strong>Motivo:</strong> {{ $saleReturn->reason }}</p>@endif
    @if($saleReturn->notes)<p><strong>Observações:</strong> {{ $saleReturn->notes }}</p>@endif
</div></div>
<div class="panel" style="margin-top:22px"><div class="panel-header"><h2 class="panel-title">Produtos devolvidos e lotes repostos</h2></div><div class="panel-body"><div style="overflow-x:auto"><table class="data-table"><thead><tr><th>Produto</th><th>Quantidade</th><th>Lote(s) de origem</th><th>Preço unitário</th><th>Total</th></tr></thead><tbody>
    @forelse($saleReturn->items as $item)
        <tr><td><strong>{{ $item->product?->name ?? 'Produto removido' }}</strong></td>
        <td>{{ number_format((float)$item->quantity,3,',','.') }} {{ $item->product?->unit?->symbol }}</td>
        <td>@forelse($item->stockBatches as $batch)<div>{{ $batch->code }} — {{ number_format((float)$batch->pivot->quantity,3,',','.') }} {{ $item->product?->unit?->symbol }}</div>@empty<span class="table-secondary">Sem lote associado</span>@endforelse</td>
        <td>{{ number_format((float)$item->unit_price,2,',','.') }} MT</td><td><strong>{{ number_format((float)$item->total,2,',','.') }} MT</strong></td></tr>
    @empty<tr><td colspan="5">Sem produtos associados.</td></tr>@endforelse
</tbody></table></div></div></div>
@endsection
