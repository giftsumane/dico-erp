<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSeries extends Model
{
    protected $table = 'document_series';

    protected $fillable = [
        'company_id', 'store_id', 'document_type', 'year', 'prefix', 'next_number',
    ];
}
