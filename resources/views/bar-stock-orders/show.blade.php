@extends('layouts.app')

@section('title', $stockOrder->number.' | DICO')
@section('topbar-title', 'Encomenda de Stock')

@section('content')
<div class="event-operation-page">
<div class="page-heading"><h1>{{ $stockOrder->number }}</h1><p>{{ $stockOrder->bar->name }} · {{ str_replace('_', ' ', ucfirst($stockOrder->status)) }} @if($stockOrder->initial_allocation)· Entrega inicial com reserva @endif</p></div>

<div class="panel"><div class="panel-header"><div><h2 class="panel-title">Mercadoria por balcão</h2><p class="panel-subtitle">O despacho retira do armazém; a recepção adiciona directamente ao stock de cada balcão.</p></div></div><div class="panel-body"><div style="overflow:auto;">
<table style="width:100%;border-collapse:collapse;"><thead><tr><th align="left">Balcão</th><th align="left">Produto</th><th>Pedido</th><th>Aprovado</th><th>Despachado</th><th>Recebido</th><th>Reserva</th><th align="right">Valor</th></tr></thead><tbody>
@foreach($stockOrder->items as $item)<tr style="border-top:1px solid #e5e7eb;"><td style="padding:12px 6px;">{{ $item->counter->name }}</td><td>{{ $item->product->name }}</td><td align="center">{{ $item->quantity_requested }}</td><td align="center">{{ $item->quantity_approved }}</td><td align="center">{{ $item->quantity_dispatched }}</td><td align="center">{{ $item->quantity_received }}</td><td align="center">{{ $item->reserve_quantity }}</td><td align="right">{{ number_format((float)$item->total, 2, ',', '.') }} MT</td></tr>@endforeach
</tbody></table></div>

<div class="operation-actions" style="margin-top:18px;">
@if($stockOrder->status === 'requested')<form method="POST" action="{{ route('events.stock-orders.approve', [$event, $stockOrder]) }}">@csrf<button class="topbar-button">Aprovar</button></form>@endif
@if($stockOrder->status === 'approved')<form method="POST" action="{{ route('events.stock-orders.prepare', [$event, $stockOrder]) }}">@csrf<button class="topbar-button">Iniciar preparação</button></form>@endif
@if($stockOrder->status === 'preparing')<form method="POST" action="{{ route('events.stock-orders.dispatch', [$event, $stockOrder]) }}">@csrf<button class="topbar-button">Despachar</button></form>@endif
</div></div></div>

@if(in_array($stockOrder->status, ['dispatched', 'partially_received']))
<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Confirmar recepção</h2><p class="panel-subtitle">Introduza apenas a quantidade recebida agora. É possível receber parcialmente.</p></div></div><div class="panel-body"><form method="POST" action="{{ route('events.stock-orders.receive', [$event, $stockOrder]) }}">@csrf
@foreach($stockOrder->items as $item)@php($remaining = max(0, (float)$item->quantity_dispatched - (float)$item->quantity_received))<div class="form-group" style="margin-bottom:12px;"><label class="form-label">{{ $item->counter->name }} — {{ $item->product->name }} (restante {{ number_format($remaining, 3, ',', '.') }})</label><input class="form-control" type="number" min="0" max="{{ $remaining }}" step="0.001" name="received[{{ $item->id }}]" value="{{ $remaining }}"></div>@endforeach
<div class="operation-actions"><button class="topbar-button">Confirmar recepção</button></div></form></div></div>
@endif

<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Liquidação com o armazém</h2><p class="panel-subtitle">Total {{ number_format((float)$stockOrder->total, 2, ',', '.') }} MT · Pago {{ number_format((float)$stockOrder->paid_total, 2, ',', '.') }} MT · Pendente {{ number_format((float)$stockOrder->amount_due, 2, ',', '.') }} MT</p></div></div><div class="panel-body">
@if((float)$stockOrder->amount_due > 0 && in_array($stockOrder->status, ['dispatched', 'partially_received', 'received']))<form class="operation-form" method="POST" action="{{ route('events.stock-orders.settlements.store', [$event, $stockOrder]) }}" style="grid-template-columns:1fr 1fr 2fr auto;">@csrf
<div class="form-group"><label class="form-label">Valor</label><input class="form-control" type="number" min="0.01" max="{{ $stockOrder->amount_due }}" step="0.01" name="amount" required></div>
<div class="form-group"><label class="form-label">Método</label><select class="form-control" name="method"><option value="cash">Numerário</option><option value="mpesa">M-Pesa</option><option value="emola">e-Mola</option><option value="bank_transfer">Transferência</option><option value="other">Outro</option></select></div>
<div class="form-group"><label class="form-label">Referência</label><input class="form-control" name="reference"></div><button class="topbar-button">Registar pagamento</button></form>@endif

<div style="margin-top:18px;overflow:auto;"><table style="width:100%;border-collapse:collapse;"><thead><tr><th align="left">Data</th><th align="left">Registado por</th><th align="left">Método/referência</th><th align="right">Valor</th><th align="left">Estado</th><th></th></tr></thead><tbody>@forelse($stockOrder->settlements as $settlement)<tr style="border-top:1px solid #e5e7eb;"><td style="padding:12px 6px;">{{ $settlement->paid_at->format('d/m/Y H:i') }}</td><td>{{ $settlement->payer->name }}</td><td>{{ $settlement->method }} {{ $settlement->reference }}</td><td align="right">{{ number_format((float)$settlement->amount, 2, ',', '.') }} MT</td><td>{{ $settlement->status }}</td><td align="right">@if($settlement->status === 'pending')<form method="POST" action="{{ route('events.stock-orders.settlements.confirm', [$event, $stockOrder, $settlement]) }}">@csrf<button>Confirmar no armazém</button></form>@endif</td></tr>@empty<tr><td colspan="6" style="padding:18px;text-align:center;">Nenhum pagamento registado.</td></tr>@endforelse</tbody></table></div>
</div></div>
</div>
@endsection
