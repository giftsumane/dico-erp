<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptAllocation extends Model
{
    protected $fillable = ['receipt_id', 'commercial_document_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];
    public function receipt() { return $this->belongsTo(Receipt::class); }
    public function document() { return $this->belongsTo(CommercialDocument::class, 'commercial_document_id'); }
}
