@extends('layouts.app')

@section('title', 'Encomendas de Stock | DICO')
@section('topbar-title', 'Encomendas de Stock')

@section('content')
<div class="page-heading" style="display:flex;justify-content:space-between;align-items:center;gap:20px;">
    <div><h1>Encomendas — {{ $event->name }}</h1><p>Liquidação, preparação, despacho e recepção de mercadoria dos bares.</p></div>
    <a class="topbar-button" href="{{ route('events.stock-orders.create', $event) }}">Nova encomenda</a>
</div>

<div class="panel"><div class="panel-body"><div style="overflow:auto;">
    <table style="width:100%;border-collapse:collapse;">
        <thead><tr><th align="left">Número</th><th align="left">Bar</th><th align="left">Pedido por</th><th align="right">Total</th><th align="right">Pendente</th><th align="left">Estado</th><th></th></tr></thead>
        <tbody>@forelse($orders as $order)
            <tr style="border-top:1px solid #e5e7eb;">
                <td style="padding:14px 8px;"><strong>{{ $order->number }}</strong>@if($order->initial_allocation)<br><small>Entrega inicial + reserva</small>@endif</td>
                <td>{{ $order->bar->name }}</td><td>{{ $order->requester->name }}</td>
                <td align="right">{{ number_format((float)$order->total, 2, ',', '.') }} MT</td>
                <td align="right">{{ number_format((float)$order->amount_due, 2, ',', '.') }} MT</td>
                <td>{{ str_replace('_', ' ', ucfirst($order->status)) }}</td>
                <td align="right"><a href="{{ route('events.stock-orders.show', [$event, $order]) }}">Abrir</a></td>
            </tr>
        @empty<tr><td colspan="7" style="padding:30px;text-align:center;">Nenhuma encomenda registada.</td></tr>@endforelse</tbody>
    </table>
</div><div style="margin-top:20px;">{{ $orders->links() }}</div></div></div>
@endsection
