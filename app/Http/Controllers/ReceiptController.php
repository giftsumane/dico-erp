<?php

namespace App\Http\Controllers;

use App\Models\CommercialDocument;
use App\Models\Receipt;
use App\Services\CommercialDocumentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::with('customer')->where('company_id', app('activeCompany')->id)
            ->where('store_id', app('activeStore')?->id)->latest('id')->paginate(25);
        return view('receipts.index', compact('receipts'));
    }

    public function create(CommercialDocument $commercialDocument)
    {
        $this->authorizeInvoice($commercialDocument);
        abort_unless(in_array($commercialDocument->status, ['issued', 'partially_paid'], true), 422);
        return view('receipts.create', ['invoice' => $commercialDocument->load('customer')]);
    }

    public function store(Request $request, CommercialDocument $commercialDocument, CommercialDocumentService $service)
    {
        $this->authorizeInvoice($commercialDocument);
        $data = $request->validate([
            'receipt_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(['cash', 'mpesa', 'emola', 'card', 'bank_transfer', 'other'])],
            'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:1000'],
            'idempotency_key' => ['nullable', 'uuid'],
        ]);
        $receipt = $service->createReceipt($commercialDocument, $data);
        return redirect()->route('receipts.show', $receipt)->with('success', 'Recibo emitido com sucesso.');
    }

    public function show(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        $receipt->load(['company', 'store', 'customer', 'user', 'allocations.document']);
        return view('receipts.show', compact('receipt'));
    }

    public function print(Receipt $receipt)
    {
        $this->authorizeReceipt($receipt);
        $receipt->load(['company', 'store', 'customer', 'allocations.document']);
        return view('receipts.print', compact('receipt'));
    }

    private function authorizeInvoice(CommercialDocument $document): void
    {
        abort_unless($document->company_id === app('activeCompany')->id && $document->store_id === app('activeStore')?->id && $document->isInvoice(), 403);
    }

    private function authorizeReceipt(Receipt $receipt): void
    {
        abort_unless($receipt->company_id === app('activeCompany')->id && $receipt->store_id === app('activeStore')?->id, 403);
    }
}
