<?php

namespace App\Services;

use App\Models\CommercialDocument;
use App\Models\DocumentSeries;
use App\Models\Receipt;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommercialDocumentService
{
    private const PREFIXES = [
        'quote' => 'COT',
        'invoice' => 'FT',
        'invoice_receipt' => 'FR',
        'credit_note' => 'NC',
        'debit_note' => 'ND',
        'receipt' => 'RC',
    ];

    public function saveDraft(CommercialDocument $document, array $data): CommercialDocument
    {
        if ($document->exists && ! $document->isDraft()) {
            throw ValidationException::withMessages([
                'document' => 'Um documento emitido já não pode ser alterado.',
            ]);
        }

        return DB::transaction(function () use ($document, $data) {
            $items = $data['items'];
            unset($data['items']);

            if (in_array($data['type'], [CommercialDocument::TYPE_CREDIT_NOTE, CommercialDocument::TYPE_DEBIT_NOTE], true)) {
                $source = CommercialDocument::query()
                    ->where('company_id', $data['company_id'] ?? $document->company_id)
                    ->where('store_id', $data['store_id'] ?? $document->store_id)
                    ->find($data['source_document_id'] ?? null);
                if (! $source || (int) $source->customer_id !== (int) $data['customer_id']) {
                    throw ValidationException::withMessages([
                        'source_document_id' => 'A factura de origem deve pertencer ao mesmo cliente.',
                    ]);
                }
            }

            $totals = $this->calculateTotals($items);
            if (($data['type'] ?? $document->type) === CommercialDocument::TYPE_CREDIT_NOTE
                && isset($source) && $totals['total'] > (float) $source->total) {
                throw ValidationException::withMessages([
                    'items' => 'A nota de crédito não pode exceder o total da factura de origem.',
                ]);
            }
            $document->fill(array_merge($data, $totals, [
                'status' => 'draft',
                'balance_due' => $totals['total'],
            ]));
            $document->save();
            $document->items()->delete();

            foreach ($items as $item) {
                $document->items()->create($this->calculateItem($item));
            }

            return $document->fresh(['items', 'customer']);
        });
    }

    public function issue(CommercialDocument $document): CommercialDocument
    {
        return DB::transaction(function () use ($document) {
            $document = CommercialDocument::query()->lockForUpdate()->findOrFail($document->id);
            if (! $document->isDraft()) {
                throw ValidationException::withMessages(['document' => 'Este documento já foi emitido.']);
            }
            if ($document->items()->doesntExist() || (float) $document->total <= 0) {
                throw ValidationException::withMessages(['document' => 'O documento deve ter pelo menos um item e total positivo.']);
            }

            $document->update([
                'number' => $this->nextNumber($document->company_id, $document->store_id, $document->type, $document->document_date->year),
                'status' => $document->type === CommercialDocument::TYPE_INVOICE_RECEIPT ? 'paid' : 'issued',
                'paid_total' => $document->type === CommercialDocument::TYPE_INVOICE_RECEIPT ? $document->total : 0,
                'balance_due' => $document->type === CommercialDocument::TYPE_INVOICE_RECEIPT ? 0 : $document->total,
                'issued_at' => now(),
            ]);

            app(AuditService::class)->record('commercial_document.issued', $document, [
                'number' => $document->number, 'type' => $document->type, 'total' => $document->total,
            ]);

            return $document->fresh();
        });
    }

    public function convertQuote(CommercialDocument $quote): CommercialDocument
    {
        if ($quote->type !== CommercialDocument::TYPE_QUOTE || ! in_array($quote->status, ['issued', 'accepted'], true)) {
            throw ValidationException::withMessages(['document' => 'Apenas uma cotação emitida pode ser convertida.']);
        }

        return DB::transaction(function () use ($quote) {
            $invoice = new CommercialDocument([
                'company_id' => $quote->company_id,
                'store_id' => $quote->store_id,
                'customer_id' => $quote->customer_id,
                'user_id' => auth()->id(),
                'source_document_id' => $quote->id,
                'type' => CommercialDocument::TYPE_INVOICE,
                'document_date' => now()->toDateString(),
                'due_date' => now()->addDays(15)->toDateString(),
                'currency' => $quote->currency,
                'notes' => $quote->notes,
                'terms' => $quote->terms,
            ]);
            $items = $quote->items->map(fn ($item) => $item->only([
                'product_id', 'description', 'quantity', 'unit_price', 'discount', 'tax_rate',
            ]))->all();
            $invoice = $this->saveDraft($invoice, array_merge($invoice->getAttributes(), ['items' => $items]));
            $quote->update(['status' => 'accepted']);
            app(AuditService::class)->record('quote.converted', $quote, ['invoice_id' => $invoice->id]);
            return $invoice;
        });
    }

    public function createFromSale(Sale $sale): CommercialDocument
    {
        $existing = CommercialDocument::query()
            ->where('company_id', $sale->company_id)->where('sale_id', $sale->id)
            ->whereNot('status', 'cancelled')->first();
        if ($existing) {
            return $existing;
        }
        if (! $sale->customer_id) {
            throw ValidationException::withMessages(['sale' => 'Associe um cliente à venda antes de emitir o documento fiscal.']);
        }

        return DB::transaction(function () use ($sale) {
            $sale->loadMissing(['items.product', 'payments']);
            $isPaid = (float) $sale->amount_due <= 0 && (float) $sale->paid_total >= (float) $sale->total;
            $document = new CommercialDocument([
                'company_id' => $sale->company_id, 'store_id' => $sale->store_id,
                'customer_id' => $sale->customer_id, 'user_id' => auth()->id(), 'sale_id' => $sale->id,
                'type' => $isPaid ? CommercialDocument::TYPE_INVOICE_RECEIPT : CommercialDocument::TYPE_INVOICE,
                'document_date' => optional($sale->completed_at)->toDateString() ?: now()->toDateString(),
                'due_date' => $isPaid ? null : ($sale->due_date?->toDateString() ?: now()->addDays(15)->toDateString()),
                'currency' => $sale->company?->currency ?: 'MZN',
                'notes' => 'Documento gerado a partir da venda '.$sale->number.'.',
            ]);
            $items = $sale->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->product?->name ?: 'Item da venda',
                'quantity' => $item->quantity, 'unit_price' => $item->unit_price,
                'discount' => $item->discount, 'tax_rate' => $item->tax_rate,
            ])->all();
            $document = $this->saveDraft($document, array_merge($document->getAttributes(), ['items' => $items]));
            return $this->issue($document);
        });
    }

    public function createReceipt(CommercialDocument $invoice, array $data): Receipt
    {
        if (! empty($data['idempotency_key'])) {
            $existing = Receipt::query()
                ->where('company_id', $invoice->company_id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->first();
            if ($existing) {
                if (! $existing->allocations()->where('commercial_document_id', $invoice->id)->exists()) {
                    throw ValidationException::withMessages(['idempotency_key' => 'Esta referência já foi utilizada noutro recibo.']);
                }
                return $existing->load(['allocations.document', 'customer']);
            }
        }

        return DB::transaction(function () use ($invoice, $data) {
            $invoice = CommercialDocument::query()->lockForUpdate()->findOrFail($invoice->id);
            if (! $invoice->isInvoice() || ! in_array($invoice->status, ['issued', 'partially_paid'], true)) {
                throw ValidationException::withMessages(['amount' => 'Esta factura não admite recebimentos.']);
            }
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > (float) $invoice->balance_due) {
                throw ValidationException::withMessages(['amount' => 'O valor deve ser positivo e não pode exceder o saldo da factura.']);
            }

            $receipt = Receipt::create([
                'company_id' => $invoice->company_id,
                'store_id' => $invoice->store_id,
                'customer_id' => $invoice->customer_id,
                'user_id' => auth()->id(),
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'number' => $this->nextNumber($invoice->company_id, $invoice->store_id, 'receipt', now()->year),
                'receipt_date' => $data['receipt_date'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'amount' => $amount,
                'status' => 'issued',
                'notes' => $data['notes'] ?? null,
                'issued_at' => now(),
            ]);
            $receipt->allocations()->create(['commercial_document_id' => $invoice->id, 'amount' => $amount]);

            $paid = round((float) $invoice->paid_total + $amount, 2);
            $balance = max(0, round((float) $invoice->total - $paid, 2));
            $invoice->update([
                'paid_total' => $paid,
                'balance_due' => $balance,
                'status' => $balance <= 0 ? 'paid' : 'partially_paid',
            ]);

            app(AuditService::class)->record('receipt.issued', $receipt, [
                'number' => $receipt->number, 'invoice_id' => $invoice->id, 'amount' => $amount,
            ]);
            return $receipt->fresh(['allocations.document', 'customer']);
        });
    }

    private function nextNumber(int $companyId, int $storeId, string $type, int $year): string
    {
        DocumentSeries::query()->firstOrCreate([
            'company_id' => $companyId, 'store_id' => $storeId,
            'document_type' => $type, 'year' => $year,
        ], [
            'prefix' => self::PREFIXES[$type], 'next_number' => 1,
        ]);

        $series = DocumentSeries::query()->where([
            'company_id' => $companyId, 'store_id' => $storeId,
            'document_type' => $type, 'year' => $year,
        ])->lockForUpdate()->first();

        $number = sprintf('%s-%d-%06d', $series->prefix, $year, $series->next_number);
        $series->increment('next_number');
        return $number;
    }

    private function calculateTotals(array $items): array
    {
        $calculated = collect($items)->map(fn ($item) => $this->calculateItem($item));
        return [
            'subtotal' => round($calculated->sum('subtotal'), 2),
            'discount_total' => round($calculated->sum('discount'), 2),
            'tax_total' => round($calculated->sum('tax_total'), 2),
            'total' => round($calculated->sum('total'), 2),
        ];
    }

    private function calculateItem(array $item): array
    {
        $quantity = round((float) $item['quantity'], 3);
        $unitPrice = round((float) $item['unit_price'], 2);
        $gross = round($quantity * $unitPrice, 2);
        $discount = min(round((float) ($item['discount'] ?? 0), 2), $gross);
        $subtotal = round($gross - $discount, 2);
        $tax = round($subtotal * ((float) ($item['tax_rate'] ?? 0) / 100), 2);
        return array_merge($item, [
            'quantity' => $quantity, 'unit_price' => $unitPrice, 'discount' => $discount,
            'tax_rate' => round((float) ($item['tax_rate'] ?? 0), 2),
            'subtotal' => $subtotal, 'tax_total' => $tax, 'total' => round($subtotal + $tax, 2),
        ]);
    }
}
