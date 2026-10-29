@extends('layouts.app')

@section('title', 'Eventos e Bares | DICO')
@section('topbar-title', 'Eventos e Bares')

@section('content')
<div class="event-operation-page">
<div class="page-heading operation-heading">
    <div>
        <h1>Operações de eventos</h1>
        <p>Organize o armazém, os bares, os balcões e as responsabilidades.</p>
    </div>
    <a class="topbar-button" href="{{ route('events.create') }}">Novo evento</a>
</div>

<div class="panel">
    <div class="panel-body">
        <div style="overflow:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead><tr><th align="left">Evento</th><th align="left">Armazém</th><th>Bares</th><th align="left">Início</th><th align="left">Estado</th><th></th></tr></thead>
                <tbody>
                @forelse($events as $event)
                    <tr style="border-top:1px solid #e5e7eb;">
                        <td style="padding:14px 8px;"><strong>{{ $event->name }}</strong><br><small>{{ $event->code }}</small></td>
                        <td>{{ $event->warehouseStore->name }}</td>
                        <td align="center">{{ $event->bars_count }}</td>
                        <td>{{ $event->starts_at->format('d/m/Y H:i') }}</td>
                        <td>{{ ucfirst($event->status) }}</td>
                        <td align="right"><a href="{{ route('events.show', $event) }}">Configurar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:30px;text-align:center;">Ainda não existem eventos configurados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:20px;">{{ $events->links() }}</div>
    </div>
</div>
</div>
@endsection
