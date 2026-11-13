<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    protected $fillable = [
        'company_id', 'store_id', 'customer_id', 'user_id', 'idempotency_key',
        'number', 'receipt_date', 'payment_method', 'reference', 'amount',
        'status', 'notes', 'issued_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected $casts = [
        'company_id' => 'integer', 'store_id' => 'integer', 'customer_id' => 'integer',
        'user_id' => 'integer',
        'receipt_date' => 'date', 'amount' => 'decimal:2',
        'issued_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function store() { return $this->belongsTo(Store::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function allocations() { return $this->hasMany(ReceiptAllocation::class); }
}
