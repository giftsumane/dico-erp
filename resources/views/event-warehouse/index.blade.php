@extends('layouts.app')

@section('title', 'Armazém do Evento | DICO')
@section('topbar-title', 'Gestão do Armazém')

@section('content')
<div class="page-heading" style="display:flex;justify-content:space-between;align-items:center;gap:20px;">
    <div><h1>{{ $warehouse->name }}</h1><p>{{ $event->name }} · stock central, encomendas, despachos e valores por receber.</p></div>
    <a class="topbar-button" href="{{ route('events.stock-orders.index', $event) }}">Todas as encomendas</a>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:14px;margin-bottom:20px;">
    @foreach([
        ['Produtos', $metrics['products']], ['Unidades', number_format($metrics['units'], 3, ',', '.')],
        ['Stock baixo', $metrics['low_stock']], ['Encomendas na fila', $metrics['orders_waiting']],
        ['Por receber', number_format($metrics['amount_due'], 2, ',', '.').' MT'],
        ['Pagamentos a confirmar', $metrics['settlements_pending']],
    ] as [$label, $value])
        <div class="panel"><div class="panel-body"><span style="color:var(--muted);font-size:13px;">{{ $label }}</span><strong style="display:block;font-size:24px;margin-top:6px;">{{ $value }}</strong></div></div>
    @endforeach
</div>

<div class="panel"><div class="panel-header"><div><h2 class="panel-title">Entrada rápida no armazém</h2><p class="panel-subtitle">Regista mercadoria recebida fisicamente. A nota é obrigatória para auditoria.</p></div></div><div class="panel-body">
    <form method="POST" action="{{ route('events.warehouse.stock.receive', $event) }}" style="display:grid;grid-template-columns:2fr 1fr 1.5fr 2fr auto;gap:12px;align-items:end;">@csrf
        <div class="form-group"><label class="form-label">Produto</label><select class="form-control" name="product_id" required><option value="">Seleccione</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Quantidade</label><input class="form-control" type="number" min="0.001" step="0.001" name="quantity" required></div>
        <div class="form-group"><label class="form-label">Referência</label><input class="form-control" name="reference" placeholder="Factura/guia"></div>
        <div class="form-group"><label class="form-label">Motivo/notas</label><input class="form-control" name="notes" required></div>
        <button class="topbar-button">Registar entrada</button>
    </form>
</div></div>

<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Encomendas operacionais</h2><p class="panel-subtitle">Fila actual do armazém.</p></div></div><div class="panel-body"><div style="overflow:auto;"><table style="width:100%;border-collapse:collapse;"><thead><tr><th align="left">Número</th><th align="left">Bar</th><th align="left">Estado</th><th align="right">Pendente</th><th></th></tr></thead><tbody>
@forelse($orders as $order)<tr style="border-top:1px solid #e5e7eb;"><td style="padding:12px 6px;">{{ $order->number }}</td><td>{{ $order->bar->name }}</td><td>{{ str_replace('_', ' ', ucfirst($order->status)) }}</td><td align="right">{{ number_format((float)$order->amount_due, 2, ',', '.') }} MT</td><td align="right"><a href="{{ route('events.stock-orders.show', [$event, $order]) }}">Processar</a></td></tr>@empty<tr><td colspan="5" style="padding:18px;text-align:center;">Sem encomendas pendentes.</td></tr>@endforelse
</tbody></table></div></div></div>

<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Stock disponível</h2><p class="panel-subtitle">Saldo actual do armazém central.</p></div></div><div class="panel-body"><div style="overflow:auto;"><table style="width:100%;border-collapse:collapse;"><thead><tr><th align="left">Produto</th><th align="right">Quantidade</th><th align="right">Mínimo</th><th align="left">Estado</th></tr></thead><tbody>
@forelse($stocks as $stock)@php($low = (float)$stock->quantity <= (float)$stock->product->minimum_stock)<tr style="border-top:1px solid #e5e7eb;"><td style="padding:12px 6px;">{{ $stock->product->name }}</td><td align="right">{{ number_format((float)$stock->quantity, 3, ',', '.') }} {{ $stock->product->unit?->symbol }}</td><td align="right">{{ number_format((float)$stock->product->minimum_stock, 3, ',', '.') }}</td><td>{{ $low ? 'Repor' : 'Disponível' }}</td></tr>@empty<tr><td colspan="4" style="padding:18px;text-align:center;">O armazém ainda não possui stock.</td></tr>@endforelse
</tbody></table></div></div></div>

<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Pagamentos pendentes de confirmação</h2></div></div><div class="panel-body">
@forelse($pendingSettlements as $settlement)<div style="display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-top:1px solid #e5e7eb;"><span>{{ $settlement->order->bar->name }} · {{ $settlement->payer->name }} · {{ $settlement->method }}</span><strong>{{ number_format((float)$settlement->amount, 2, ',', '.') }} MT — <a href="{{ route('events.stock-orders.show', [$event, $settlement->order]) }}">confirmar</a></strong></div>@empty<p>Sem pagamentos pendentes.</p>@endforelse
</div></div>

<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Últimos movimentos</h2></div></div><div class="panel-body"><div style="overflow:auto;"><table style="width:100%;border-collapse:collapse;"><thead><tr><th align="left">Data</th><th align="left">Produto</th><th align="left">Tipo</th><th align="right">Quantidade</th><th align="left">Responsável</th></tr></thead><tbody>
@forelse($movements as $movement)<tr style="border-top:1px solid #e5e7eb;"><td style="padding:12px 6px;">{{ $movement->occurred_at->format('d/m H:i') }}</td><td>{{ $movement->product->name }}</td><td>{{ $movement->type }}</td><td align="right">{{ number_format((float)$movement->quantity, 3, ',', '.') }}</td><td>{{ $movement->user?->name ?: 'Sistema' }}</td></tr>@empty<tr><td colspan="5" style="padding:18px;text-align:center;">Sem movimentos.</td></tr>@endforelse
</tbody></table></div></div></div>
@endsection
