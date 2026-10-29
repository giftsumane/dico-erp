@extends('layouts.app')

@section('title', 'Operação dos Eventos | DICO')
@section('topbar-title', 'Operação dos Eventos')

@section('content')
<div class="page-heading"><h1>Operação de stock dos eventos</h1><p>Escolha um evento para gerir encomendas, pagamentos, despachos e recepções.</p></div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px;">
@forelse($events as $event)
    <div class="panel"><div class="panel-body">
        <h2 style="margin:0 0 8px;">{{ $event->name }}</h2>
        <p>{{ $event->venue ?: 'Local não indicado' }}<br>Armazém: {{ $event->warehouseStore->name }}<br>{{ $event->bars->count() }} bar(es)</p>
        <div style="display:flex;gap:8px;flex-wrap:wrap;"><a class="topbar-button" href="{{ route('events.warehouse.index', $event) }}">Armazém</a><a class="topbar-button" href="{{ route('events.stock-orders.index', $event) }}">Encomendas</a></div>
    </div></div>
@empty
    <div class="panel"><div class="panel-body">Não existem eventos operacionais associados à sua conta.</div></div>
@endforelse
</div>
@endsection
