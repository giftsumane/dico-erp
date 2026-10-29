@extends('layouts.app')

@section('title', 'Nova Encomenda | DICO')
@section('topbar-title', 'Nova Encomenda')

@section('content')
<div class="page-heading"><h1>Encomendar stock ao armazém</h1><p>Uma encomenda pode distribuir produtos por vários balcões do mesmo bar.</p></div>

<div class="panel"><div class="panel-body">
<form method="POST" action="{{ route('events.stock-orders.store', $event) }}" id="order-form">
    @csrf
    <input type="hidden" name="idempotency_key" value="{{ $idempotencyKey }}">
    <div class="form-group"><label class="form-label">Bar</label><select class="form-control" name="event_bar_id" id="bar-select" required><option value="">Seleccione</option>@foreach($event->bars as $bar)<option value="{{ $bar->id }}">{{ $bar->name }}</option>@endforeach</select></div>
    <div style="margin-top:20px;overflow:auto;"><table style="width:100%;border-collapse:collapse;" id="items-table"><thead><tr><th align="left">Balcão</th><th align="left">Produto</th><th>Quantidade</th><th>Reserva inicial</th><th></th></tr></thead><tbody></tbody></table></div>
    <button type="button" class="topbar-button" style="margin-top:14px;" onclick="addRow()">Adicionar produto</button>
    <div class="form-group" style="margin-top:18px;"><label class="form-label">Notas</label><textarea class="form-control" name="notes" rows="3"></textarea></div>
    <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:20px;"><a href="{{ route('events.stock-orders.index', $event) }}">Cancelar</a><button class="topbar-button" type="submit">Enviar pedido</button></div>
</form>
</div></div>

<script>
const bars = @json($event->bars->mapWithKeys(fn($bar) => [$bar->id => $bar->counters->map(fn($counter) => ['id' => $counter->id, 'name' => $counter->name]) ]));
const products = @json($products->map(fn($product) => ['id' => $product->id, 'name' => $product->name]));
let rowIndex = 0;

function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
}

function addRow() {
    const barId = document.getElementById('bar-select').value;
    if (!barId) { alert('Seleccione primeiro o bar.'); return; }
    const counters = bars[barId] || [];
    const counterOptions = counters.map(item => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');
    const productOptions = products.map(item => `<option value="${item.id}">${escapeHtml(item.name)}</option>`).join('');
    const row = document.createElement('tr');
    row.style.borderTop = '1px solid #e5e7eb';
    row.innerHTML = `<td style="padding:10px 5px;"><select class="form-control" name="items[${rowIndex}][event_counter_id]" required>${counterOptions}</select></td><td><select class="form-control" name="items[${rowIndex}][product_id]" required>${productOptions}</select></td><td><input class="form-control" type="number" min="0.001" step="0.001" name="items[${rowIndex}][quantity]" required></td><td><input class="form-control" type="number" min="0" step="0.001" name="items[${rowIndex}][reserve_quantity]" value="0"></td><td><button type="button" onclick="this.closest('tr').remove()">Remover</button></td>`;
    document.querySelector('#items-table tbody').appendChild(row);
    rowIndex++;
}

document.getElementById('bar-select').addEventListener('change', () => {
    document.querySelector('#items-table tbody').innerHTML = '';
    rowIndex = 0;
});
</script>
@endsection
