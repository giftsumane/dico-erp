@extends('layouts.app')

@section('title', $event->name.' | DICO')
@section('topbar-title', 'Configuração do Evento')

@section('content')
@php
    $roleLabels = [
        'event_manager' => 'Gestor do evento', 'warehouse_manager' => 'Gestor do armazém',
        'warehouse_operator' => 'Operador do armazém', 'bar_stock_manager' => 'Gestor de stock do bar',
        'cashier' => 'Caixa', 'attendant' => 'Atendente', 'bar_supervisor' => 'Supervisor do bar',
        'delivery_operator' => 'Entregador',
    ];
@endphp

<div class="page-heading"><h1>{{ $event->name }}</h1><p>{{ $event->code }} · {{ $event->venue ?: 'Local não indicado' }} · Armazém: <strong>{{ $event->warehouseStore->name }}</strong></p></div>

<div style="display:flex;justify-content:flex-end;margin-bottom:18px;"><a class="topbar-button" href="{{ route('events.stock-orders.index', $event) }}">Encomendas de stock</a></div>

<div class="panel"><div class="panel-header"><div><h2 class="panel-title">Adicionar bar</h2><p class="panel-subtitle">Um bar agrupa vários balcões, mas não mistura os respectivos stocks.</p></div></div><div class="panel-body">
    <form method="POST" action="{{ route('events.bars.store', $event) }}" style="display:grid;grid-template-columns:1fr 2fr 120px auto;gap:12px;align-items:end;">
        @csrf
        <div class="form-group"><label class="form-label">Código</label><input class="form-control" name="code" required></div>
        <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
        <div class="form-group"><label class="form-label">Ordem</label><input class="form-control" type="number" min="0" name="position" value="0"></div>
        <button class="topbar-button" type="submit">Adicionar</button>
    </form>
</div></div>

@foreach($event->bars as $bar)
<div class="panel" style="margin-top:20px;">
    <div class="panel-header"><div><h2 class="panel-title">{{ $bar->name }}</h2><p class="panel-subtitle">{{ $bar->code }} · {{ $bar->counters->count() }} balcão(ões)</p></div></div>
    <div class="panel-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-bottom:18px;">
            @foreach($bar->counters as $counter)
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:14px;"><strong>{{ $counter->name }}</strong><br><small>{{ $counter->code }} · Stock: {{ $counter->store->code }}</small></div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('events.counters.store', [$event, $bar]) }}" style="display:grid;grid-template-columns:1fr 2fr 120px auto;gap:12px;align-items:end;">
            @csrf
            <div class="form-group"><label class="form-label">Código do balcão</label><input class="form-control" name="code" required></div>
            <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" required></div>
            <div class="form-group"><label class="form-label">Ordem</label><input class="form-control" type="number" min="0" name="position" value="0"></div>
            <button class="topbar-button" type="submit">Criar balcão</button>
        </form>
    </div>
</div>
@endforeach

<div class="panel" style="margin-top:20px;"><div class="panel-header"><div><h2 class="panel-title">Equipa e responsabilidades</h2><p class="panel-subtitle">Caixas e atendentes exigem balcão; gestores de stock e supervisores exigem bar.</p></div></div><div class="panel-body">
    <form method="POST" action="{{ route('events.assignments.store', $event) }}" style="display:grid;grid-template-columns:2fr 2fr 2fr 2fr auto;gap:12px;align-items:end;">
        @csrf
        <div class="form-group"><label class="form-label">Utilizador</label><select class="form-control" name="user_id" required><option value="">Seleccione</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Função</label><select class="form-control" name="role" required>@foreach($roleLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Bar</label><select class="form-control" name="event_bar_id"><option value="">Evento/armazém</option>@foreach($event->bars as $bar)<option value="{{ $bar->id }}">{{ $bar->name }}</option>@endforeach</select></div>
        <div class="form-group"><label class="form-label">Balcão</label><select class="form-control" name="event_counter_id"><option value="">Nenhum</option>@foreach($event->bars as $bar)@foreach($bar->counters as $counter)<option value="{{ $counter->id }}">{{ $bar->name }} — {{ $counter->name }}</option>@endforeach @endforeach</select></div>
        <button class="topbar-button" type="submit">Atribuir</button>
    </form>

    <div style="margin-top:20px;overflow:auto;"><table style="width:100%;border-collapse:collapse;"><thead><tr><th align="left">Pessoa</th><th align="left">Função</th><th align="left">Local</th></tr></thead><tbody>@forelse($event->assignments as $assignment)<tr style="border-top:1px solid #e5e7eb;"><td style="padding:12px 8px;">{{ $assignment->user->name }}</td><td>{{ $roleLabels[$assignment->role] ?? $assignment->role }}</td><td>{{ $assignment->counter?->name ?? $assignment->bar?->name ?? $event->warehouseStore->name }}</td></tr>@empty<tr><td colspan="3" style="padding:20px;text-align:center;">Nenhuma responsabilidade atribuída.</td></tr>@endforelse</tbody></table></div>
</div></div>
@endsection
