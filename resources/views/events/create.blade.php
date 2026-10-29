@extends('layouts.app')

@section('title', 'Novo Evento | DICO')
@section('topbar-title', 'Novo Evento')

@section('content')
<div class="page-heading"><h1>Criar evento</h1><p>Seleccione a loja que funcionará como armazém central. Os balcões serão criados depois.</p></div>

<div class="panel"><div class="panel-body">
    <form method="POST" action="{{ route('events.store') }}">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;">
            <div class="form-group"><label class="form-label">Código</label><input class="form-control" name="code" value="{{ old('code') }}" required></div>
            <div class="form-group"><label class="form-label">Nome</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
            <div class="form-group"><label class="form-label">Local</label><input class="form-control" name="venue" value="{{ old('venue') }}"></div>
            <div class="form-group"><label class="form-label">Armazém central</label><select class="form-control" name="warehouse_store_id" required><option value="">Seleccione</option>@foreach($stores as $store)<option value="{{ $store->id }}" @selected(old('warehouse_store_id') == $store->id)>{{ $store->name }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label">Início</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required></div>
            <div class="form-group"><label class="form-label">Fim previsto</label><input class="form-control" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></div>
            <div class="form-group"><label class="form-label">Fuso horário</label><input class="form-control" name="timezone" value="{{ old('timezone', 'Africa/Maputo') }}" required></div>
        </div>
        <div class="form-group" style="margin-top:18px;"><label class="form-label">Notas</label><textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea></div>
        <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;"><a href="{{ route('events.index') }}">Cancelar</a><button class="topbar-button" type="submit">Criar evento</button></div>
    </form>
</div></div>
@endsection
