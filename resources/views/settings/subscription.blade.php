@extends('layouts.app')
@section('title','Subscrição | DICO')
@section('topbar-title','Subscrição')
@section('content')
<div class="page-heading"><h1>Subscrição da empresa</h1><p>Plano e módulos contratados por <strong>{{ $company->name }}</strong>.</p></div>
@if(!$subscription)<div class="panel"><div class="panel-body"><h2 class="panel-title">Empresa sem subscrição comercial</h2><p class="panel-subtitle">Esta empresa é anterior ao novo modelo e permanece com acesso às funcionalidades já activas.</p></div></div>
@else
<div class="metric-grid"><div class="metric-card"><div class="metric-label">Plano</div><div class="metric-value" style="font-size:22px">{{ $subscription->plan?->name ?? 'Personalizado' }}</div></div><div class="metric-card"><div class="metric-label">Estado</div><div class="metric-value" style="font-size:22px">{{ ['trial'=>'Experimental','active'=>'Activo','past_due'=>'Pagamento pendente','suspended'=>'Suspenso','cancelled'=>'Cancelado'][$subscription->status] ?? $subscription->status }}</div></div><div class="metric-card"><div class="metric-label">Fim do teste</div><div class="metric-value" style="font-size:22px">{{ $subscription->trial_ends_at?->format('d/m/Y') ?? '—' }}</div></div></div>
<div class="panel" style="margin-top:20px"><div class="panel-header"><div><h2 class="panel-title">Módulos incluídos</h2><p class="panel-subtitle">As funcionalidades disponíveis resultam desta adesão.</p></div></div><div class="panel-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">@foreach($subscription->modules as $module)<div style="border:1px solid var(--border);border-radius:12px;padding:14px"><strong>{{ $module->name }}</strong><p style="margin:6px 0 0;color:var(--muted);font-size:13px">{{ $module->description }}</p></div>@endforeach</div></div>
@endif
@endsection
