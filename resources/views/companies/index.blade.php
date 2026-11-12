@extends('layouts.app')

@section('title', 'Empresas | DICO')
@section('topbar-title', 'Empresas')

@section('content')
<div class="company-heading">
    <div>
        <h1>Empresas autorizadas</h1>
        <p>Cada empresa possui lojas, utilizadores, stock e operações independentes.</p>
    </div>
    <a href="{{ route('companies.create') }}" class="topbar-button company-primary">+ Nova empresa</a>
</div>

<div class="company-grid">
    @foreach ($companies as $company)
        <article class="panel company-card">
            <div class="panel-body">
                <div class="company-card-top">
                    <div>
                        <h2>{{ $company->name }}</h2>
                        <p>{{ $company->legal_name ?: 'Sem denominação legal informada' }}</p>
                    </div>
                    <span class="company-status">{{ $company->status }}</span>
                </div>
                <dl>
                    <div><dt>Lojas</dt><dd>{{ $company->stores_count }}</dd></div>
                    <div><dt>Perfil</dt><dd>{{ $company->business_type ?: 'standard' }}</dd></div>
                    <div><dt>NUIT</dt><dd>{{ $company->nuit ?: '—' }}</dd></div>
                </dl>
                @if ($activeCompany->id === $company->id)
                    <strong class="company-active">Empresa activa</strong>
                @endif
            </div>
        </article>
    @endforeach
</div>
@endsection

@section('styles')
<style>
.company-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:20px}.company-heading h1{margin:0 0 6px;font-size:25px}.company-heading p,.company-card p{margin:0;color:var(--muted)}.company-primary{background:var(--primary);border-color:var(--primary);color:#fff}.company-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.company-card h2{margin:0 0 5px;font-size:19px}.company-card-top{display:flex;justify-content:space-between;gap:12px}.company-status,.company-active{display:inline-flex;padding:5px 9px;border-radius:999px;background:#eaf7ef;color:#176b3a;font-size:12px}.company-card dl{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:20px 0}.company-card dt{font-size:11px;color:var(--muted)}.company-card dd{margin:4px 0 0;font-weight:750;word-break:break-word}@media(max-width:600px){.company-heading{flex-direction:column}.company-heading .topbar-button{width:100%;justify-content:center}.company-card dl{grid-template-columns:1fr 1fr}}
</style>
@endsection
