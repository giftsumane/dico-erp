<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBankAccount extends Model
{
    protected $fillable = [
        'company_id', 'position', 'bank_name', 'account_number', 'nib', 'iban', 'swift_code',
    ];

    protected $casts = ['company_id' => 'integer', 'position' => 'integer'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
