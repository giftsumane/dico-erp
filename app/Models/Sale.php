<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'company_id',
        'store_id',
        'customer_id',
        'user_id',
        'number',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'paid_total',
        'change_total',
        'completed_at',
        'cash_register_id',
        'payment_status',
        'amount_due',
        'due_date',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'change_total' => 'decimal:2',
        'completed_at' => 'datetime',
        'due_date' => 'date',
        'amount_due' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function returns()
    {
        return $this->hasMany(
            SaleReturn::class
        );
    }
}