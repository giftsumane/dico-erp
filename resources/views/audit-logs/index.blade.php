@extends('layouts.app')

@section('title', 'Auditoria | DICO')
@section('topbar-title', 'Auditoria')

@section('content')
<div class="audit-heading">
    <div>
        <h1>Trilho de auditoria</h1>
        <p>Operações críticas registadas com utilizador, data, contexto e referência.</p>
    </div>
    <a class="topbar-button" href="{{ route('audit-logs.export', request()->query()) }}">
        Exportar CSV
    </a>
</div>

<section class="panel">
    <form method="GET" action="{{ route('audit-logs.index') }}" class="audit-filters">
        <div class="form-group">
            <label class="form-label" for="audit-search">Pesquisa</label>
            <input id="audit-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Acção, entidade, ID ou utilizador">
        </div>
        <div class="form-group">
            <label class="form-label" for="audit-action">Acção</label>
            <select id="audit-action" class="form-control" name="action">
                <option value="">Todas</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label class="form-label" for="audit-from">De</label>
            <input id="audit-from" type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="form-group">
            <label class="form-label" for="audit-to">Até</label>
            <input id="audit-to" type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
        </div>
        <button class="btn btn-primary" type="submit">Filtrar</button>
    </form>
</section>

<section class="panel" style="margin-top:18px">
    <div class="audit-table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Acção</th>
                    <th>Utilizador</th>
                    <th>Entidade</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td data-label="Data">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td data-label="Acção"><strong>{{ $log->action }}</strong></td>
                        <td data-label="Utilizador">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td data-label="Entidade">{{ class_basename($log->subject_type) }} #{{ $log->subject_id }}</td>
                        <td data-label="Detalhes"><code>{{ json_encode($log->metadata, JSON_UNESCAPED_UNICODE) }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Nenhum registo encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="margin-top:16px">{{ $logs->links() }}</div>
</section>
@endsection

@section('styles')
<style>
.audit-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px}.audit-heading h1{margin:0 0 5px;font-size:25px}.audit-heading p{margin:0;color:#64748b}.audit-filters{display:grid;grid-template-columns:minmax(220px,2fr) minmax(180px,1fr) 150px 150px auto;gap:12px;align-items:end}.audit-table-wrap{overflow-x:auto}.audit-table-wrap code{white-space:normal;word-break:break-word;font-size:12px;color:#334155}@media(max-width:900px){.audit-filters{grid-template-columns:1fr 1fr}}@media(max-width:650px){.audit-heading{flex-direction:column}.audit-filters{grid-template-columns:1fr}.audit-table-wrap table,.audit-table-wrap thead,.audit-table-wrap tbody,.audit-table-wrap tr,.audit-table-wrap th,.audit-table-wrap td{display:block}.audit-table-wrap thead{display:none}.audit-table-wrap tr{padding:12px 0;border-bottom:1px solid #e2e8f0}.audit-table-wrap td{border:0;padding:5px 0}.audit-table-wrap td:before{content:attr(data-label);display:block;font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b}}
</style>
@endsection
