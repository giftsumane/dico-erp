<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialDocument extends Model
{
    public const TYPE_QUOTE = 'quote';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_INVOICE_RECEIPT = 'invoice_receipt';
    public const TYPE_CREDIT_NOTE = 'credit_note';
    public const TYPE_DEBIT_NOTE = 'debit_note';

    protected $fillable = [
        'company_id', 'store_id', 'customer_id', 'user_id', 'sale_id',
        'source_document_id', 'idempotency_key', 'type', 'number', 'status',
        'document_date', 'valid_until', 'due_date', 'currency', 'subtotal',
        'discount_total', 'tax_total', 'total', 'paid_total', 'balance_due',
        'notes', 'terms', 'issued_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'document_date' => 'date', 'valid_until' => 'date', 'due_date' => 'date',
        'issued_at' => 'datetime', 'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2', 'total' => 'decimal:2',
        'paid_total' => 'decimal:2', 'balance_due' => 'decimal:2',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function sale() { return $this->belongsTo(Sale::class); }
    public function sourceDocument() { return $this->belongsTo(self::class, 'source_document_id'); }
    public function derivedDocuments() { return $this->hasMany(self::class, 'source_document_id'); }
    public function items() { return $this->hasMany(CommercialDocumentItem::class); }
    public function receiptAllocations() { return $this->hasMany(ReceiptAllocation::class); }

    public function isDraft(): bool { return $this->status === 'draft'; }
    public function isInvoice(): bool
    {
        return in_array($this->type, [self::TYPE_INVOICE, self::TYPE_INVOICE_RECEIPT, self::TYPE_DEBIT_NOTE], true);
    }
}
