@extends('layouts.app')
@section('title', 'Documentos Comerciais | DICO')
@section('topbar-title', 'Faturação')
@section('content')
@php
    $types = ['quote'=>'Cotação','invoice'=>'Factura','invoice_receipt'=>'Factura-recibo','credit_note'=>'Nota de crédito','debit_note'=>'Nota de débito'];
    $statuses = ['draft'=>'Rascunho','issued'=>'Emitido','accepted'=>'Aceite','partially_paid'=>'Pagamento parcial','paid'=>'Pago','cancelled'=>'Anulado'];
@endphp
<div class="page-heading">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
        <div><h1>Documentos comerciais</h1><p>Cotações, facturas e notas da loja <strong>{{ $activeStore?->name }}</strong>.</p></div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="topbar-button" href="{{ route('receipts.index') }}">Recibos</a>
            <a class="topbar-button" style="background:var(--primary);color:#fff" href="{{ route('commercial-documents.create') }}">+ Novo documento</a>
        </div>
    </div>
</div>
<div class="panel"><div class="panel-body">
    <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
        <div class="form-group" style="margin:0;flex:2 1 220px;min-width:0"><label class="form-label">Pesquisar</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Número ou cliente"></div>
        <div class="form-group" style="margin:0;flex:1 1 170px;min-width:0"><label class="form-label">Tipo</label><select class="form-control" name="type"><option value="">Todos</option>@foreach($types as $key=>$label)<option value="{{ $key }}" @selected(request('type')===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="form-group" style="margin:0;flex:1 1 170px;min-width:0"><label class="form-label">Estado</label><select class="form-control" name="status"><option value="">Todos</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></div>
        <button class="topbar-button" type="submit">Filtrar</button>
    </form>
</div></div>
<div class="panel" style="margin-top:20px"><div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;min-width:760px">
    <thead><tr><th style="padding:14px;text-align:left">Documento</th><th style="padding:14px;text-align:left">Cliente</th><th style="padding:14px;text-align:left">Data</th><th style="padding:14px;text-align:right">Total</th><th style="padding:14px;text-align:right">Saldo</th><th style="padding:14px;text-align:left">Estado</th></tr></thead>
    <tbody>@forelse($documents as $document)<tr style="border-top:1px solid var(--border)">
        <td style="padding:14px"><a href="{{ route('commercial-documents.show',$document) }}"><strong>{{ $document->number ?: 'Rascunho #'.$document->id }}</strong></a><br><small>{{ $types[$document->type] ?? $document->type }}</small></td>
        <td style="padding:14px">{{ $document->customer->name }}</td><td style="padding:14px">{{ $document->document_date->format('d/m/Y') }}</td>
        <td style="padding:14px;text-align:right">{{ number_format($document->total,2,',','.') }} MT</td><td style="padding:14px;text-align:right">{{ number_format($document->balance_due,2,',','.') }} MT</td><td style="padding:14px">{{ $statuses[$document->status] ?? $document->status }}</td>
    </tr>@empty<tr><td colspan="6" style="padding:30px;text-align:center">Ainda não existem documentos comerciais.</td></tr>@endforelse</tbody>
</table></div><div style="padding:16px">{{ $documents->links() }}</div></div>
@endsection
