@extends('layouts.app')

@section('title', 'Nova empresa | DICO')
@section('topbar-title', 'Nova empresa')

@section('content')
<div class="panel company-form-panel">
    <div class="panel-header">
        <div>
            <h1 class="panel-title">Criar empresa</h1>
            <p class="panel-subtitle">A empresa e a primeira loja serão criadas com dados totalmente separados.</p>
        </div>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('companies.store') }}">
            @csrf
            <div class="company-form-grid">
                <div class="form-group"><label class="form-label">Nome no DICO *</label><input class="form-control" name="name" value="{{ old('name') }}" required></div>
                <div class="form-group"><label class="form-label">Perfil de actividade *</label><select class="form-control" name="business_type" required>@foreach($profiles as $value => $label)<option value="{{ $value }}" @selected(old('business_type') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group"><label class="form-label">Denominação legal</label><input class="form-control" name="legal_name" value="{{ old('legal_name') }}"></div>
                <div class="form-group"><label class="form-label">Nome comercial</label><input class="form-control" name="trade_name" value="{{ old('trade_name') }}"></div>
                <div class="form-group"><label class="form-label">NUIT</label><input class="form-control" name="nuit" value="{{ old('nuit') }}"></div>
                <div class="form-group"><label class="form-label">Contacto</label><input class="form-control" name="phone" value="{{ old('phone') }}"></div>
                <div class="form-group"><label class="form-label">E-mail</label><input type="email" class="form-control" name="email" value="{{ old('email') }}"></div>
                <div class="form-group"><label class="form-label">Endereço</label><input class="form-control" name="address" value="{{ old('address') }}"></div>
            </div>
            <h2 class="company-subtitle">Primeira loja</h2>
            <div class="company-form-grid">
                <div class="form-group"><label class="form-label">Nome da loja *</label><input class="form-control" name="store_name" value="{{ old('store_name', 'Loja Principal') }}" required></div>
                <div class="form-group"><label class="form-label">Código</label><input class="form-control" name="store_code" value="{{ old('store_code') }}" placeholder="Ex.: LP"></div>
            </div>
            <div class="company-actions"><a href="{{ route('companies.index') }}" class="topbar-button">Cancelar</a><button class="topbar-button company-primary" type="submit">Criar e seleccionar empresa</button></div>
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
.company-form-panel{max-width:900px;margin:0 auto}.company-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px}.company-subtitle{font-size:17px;margin:24px 0 14px}.company-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}.company-primary{background:var(--primary);border-color:var(--primary);color:#fff}@media(max-width:650px){.company-form-grid{grid-template-columns:1fr}.company-actions{flex-direction:column-reverse}.company-actions .topbar-button{width:100%;justify-content:center}}
</style>
@endsection
