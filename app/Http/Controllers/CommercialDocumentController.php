<?php

namespace App\Http\Controllers;

use App\Models\CommercialDocument;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\AuditService;
use App\Services\CommercialDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CommercialDocumentController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');
        abort_unless($store, 403);

        $documents = CommercialDocument::with('customer')
            ->where('company_id', $company->id)->where('store_id', $store->id)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = trim($request->search);
                $q->where(fn ($sub) => $sub->where('number', 'like', "%{$term}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$term}%")));
            })->latest('id')->paginate(25)->withQueryString();

        return view('commercial-documents.index', compact('documents'));
    }

    public function create(Request $request)
    {
        $type = in_array($request->get('type'), $this->types(), true) ? $request->get('type') : 'quote';
        return $this->form(new CommercialDocument(['type' => $type, 'document_date' => now(), 'due_date' => now()->addDays(15)]));
    }

    public function store(Request $request, CommercialDocumentService $service)
    {
        $company = app('activeCompany');
        $store = app('activeStore');
        abort_unless($store, 403);
        $data = $this->validated($request);
        $document = new CommercialDocument(array_merge($data, [
            'company_id' => $company->id, 'store_id' => $store->id, 'user_id' => $request->user()->id,
            'currency' => $company->currency ?: 'MZN',
        ]));
        $document = $service->saveDraft($document, array_merge($data, $document->only(['company_id', 'store_id', 'user_id', 'currency'])));
        app(AuditService::class)->record('commercial_document.created', $document, ['type' => $document->type]);
        return redirect()->route('commercial-documents.show', $document)->with('success', 'Documento guardado como rascunho.');
    }

    public function show(CommercialDocument $commercialDocument)
    {
        $this->authorizeDocument($commercialDocument);
        $commercialDocument->load(['customer', 'store', 'user', 'items.product', 'sourceDocument', 'derivedDocuments', 'receiptAllocations.receipt']);
        return view('commercial-documents.show', compact('commercialDocument'));
    }

    public function edit(CommercialDocument $commercialDocument)
    {
        $this->authorizeDocument($commercialDocument);
        if (! $commercialDocument->isDraft()) {
            $guidance = $commercialDocument->type === CommercialDocument::TYPE_QUOTE
                ? 'A cotação já foi emitida e ficou bloqueada para auditoria. Crie uma nova cotação para efectuar alterações.'
                : 'O documento já foi emitido e não pode ser alterado. Para corrigir uma factura, emita uma nota de crédito ou débito ligada ao documento original.';

            return redirect()
                ->route('commercial-documents.show', $commercialDocument)
                ->with('error', $guidance);
        }
        $commercialDocument->load('items');
        return $this->form($commercialDocument);
    }

    public function update(Request $request, CommercialDocument $commercialDocument, CommercialDocumentService $service)
    {
        $this->authorizeDocument($commercialDocument);
        $document = $service->saveDraft($commercialDocument, $this->validated($request));
        app(AuditService::class)->record('commercial_document.updated', $document);
        return redirect()->route('commercial-documents.show', $document)->with('success', 'Rascunho actualizado.');
    }

    public function issue(CommercialDocument $commercialDocument, CommercialDocumentService $service)
    {
        $this->authorizeDocument($commercialDocument);
        $document = $service->issue($commercialDocument);
        return redirect()->route('commercial-documents.show', $document)->with('success', 'Documento emitido com o número '.$document->number.'.');
    }

    public function convert(CommercialDocument $commercialDocument, CommercialDocumentService $service)
    {
        $this->authorizeDocument($commercialDocument);
        $invoice = $service->convertQuote($commercialDocument->load('items'));
        return redirect()->route('commercial-documents.edit', $invoice)->with('success', 'Cotação convertida. Confirme os dados e emita a factura.');
    }

    public function reviseQuote(CommercialDocument $commercialDocument, CommercialDocumentService $service)
    {
        $this->authorizeDocument($commercialDocument);
        abort_unless(
            $commercialDocument->type === CommercialDocument::TYPE_QUOTE
                && ! $commercialDocument->isDraft()
                && $commercialDocument->status !== 'cancelled',
            422,
            'Apenas cotações emitidas podem gerar revisões.'
        );

        $commercialDocument->load('items');
        $revision = new CommercialDocument([
            'company_id' => $commercialDocument->company_id,
            'store_id' => $commercialDocument->store_id,
            'customer_id' => $commercialDocument->customer_id,
            'user_id' => request()->user()->id,
            'source_document_id' => $commercialDocument->id,
            'type' => CommercialDocument::TYPE_QUOTE,
            'document_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'currency' => $commercialDocument->currency,
            'notes' => $commercialDocument->notes,
            'terms' => $commercialDocument->terms,
        ]);
        $items = $commercialDocument->items->map(fn ($item) => $item->only([
            'product_id', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate',
        ]))->all();
        $revision = $service->saveDraft($revision, array_merge($revision->getAttributes(), ['items' => $items]));
        app(AuditService::class)->record('quote.revised', $commercialDocument, ['revision_id' => $revision->id]);

        return redirect()->route('commercial-documents.edit', $revision)
            ->with('success', 'Foi criada uma nova revisão editável; a cotação original foi preservada.');
    }

    public function fromSale(Sale $sale, CommercialDocumentService $service)
    {
        abort_unless($sale->company_id === app('activeCompany')->id && $sale->store_id === app('activeStore')?->id, 403);
        $document = $service->createFromSale($sale);
        return redirect()->route('commercial-documents.show', $document)
            ->with('success', 'Documento comercial gerado a partir da venda.');
    }

    public function cancel(Request $request, CommercialDocument $commercialDocument)
    {
        $this->authorizeDocument($commercialDocument);
        abort_if(in_array($commercialDocument->status, ['cancelled', 'paid', 'partially_paid'], true), 422, 'Este documento não pode ser anulado.');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $commercialDocument->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => $data['reason']]);
        app(AuditService::class)->record('commercial_document.cancelled', $commercialDocument, ['reason' => $data['reason']]);
        return back()->with('success', 'Documento anulado com justificação registada.');
    }

    public function print(CommercialDocument $commercialDocument)
    {
        $this->authorizeDocument($commercialDocument);
        $commercialDocument->load(['company', 'store', 'customer', 'items']);
        return view('commercial-documents.print', ['document' => $commercialDocument]);
    }

    private function form(CommercialDocument $document)
    {
        $company = app('activeCompany');
        abort_unless(app('activeStore'), 403);
        $customers = Customer::where('company_id', $company->id)->where('active', true)->orderBy('name')->get();
        $products = Product::where('company_id', $company->id)->where('active', true)->orderBy('name')->get();
        $sourceInvoices = CommercialDocument::with('customer')
            ->where('company_id', $company->id)
            ->where('store_id', app('activeStore')->id)
            ->whereIn('type', ['invoice', 'invoice_receipt'])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->latest('id')->limit(200)->get();
        return view('commercial-documents.form', compact('document', 'customers', 'products', 'sourceInvoices'));
    }

    private function validated(Request $request): array
    {
        $company = app('activeCompany');
        return $request->validate([
            'type' => ['required', Rule::in($this->types())],
            'source_document_id' => [
                'nullable', 'required_if:type,credit_note,debit_note',
                Rule::exists('commercial_documents', 'id')->where(fn ($query) => $query
                    ->where('company_id', $company->id)
                    ->where('store_id', app('activeStore')->id)
                    ->whereIn('type', ['invoice', 'invoice_receipt'])
                    ->where('status', '<>', 'cancelled')),
            ],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $company->id)],
            'document_date' => ['required', 'date'], 'valid_until' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:3000'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('company_id', $company->id)],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function authorizeDocument(CommercialDocument $document): void
    {
        abort_unless($document->company_id === app('activeCompany')->id && $document->store_id === app('activeStore')?->id, 403);
    }

    private function types(): array
    {
        return ['quote', 'invoice', 'invoice_receipt', 'credit_note', 'debit_note'];
    }
}
