<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercialDocumentItem extends Model
{
    protected $fillable = [
        'commercial_document_id', 'product_id', 'description', 'quantity',
        'unit_price', 'discount', 'tax_rate', 'subtotal', 'tax_total', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3', 'unit_price' => 'decimal:2',
        'discount' => 'decimal:2', 'tax_rate' => 'decimal:2',
        'subtotal' => 'decimal:2', 'tax_total' => 'decimal:2', 'total' => 'decimal:2',
    ];

    public function document() { return $this->belongsTo(CommercialDocument::class, 'commercial_document_id'); }
    public function product() { return $this->belongsTo(Product::class); }
}
