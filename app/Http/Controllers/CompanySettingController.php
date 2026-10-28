<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $company = app('activeCompany');

        return view(
            'settings.company',
            compact('company')
        );
    }

    public function update(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'legal_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'trade_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'nuit' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'address' => [
                'nullable',
                'string',
                'max:255',
            ],

            'currency' => [
                'required',
                'in:MZN,USD,ZAR,EUR',
            ],

            'timezone' => [
                'required',
                'string',
                'max:100',
            ],

            'default_tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'receipt_footer' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $company->update($data);

        return redirect()
            ->route('settings.company.edit')
            ->with(
                'success',
                'Configurações actualizadas com sucesso.'
            );
    }
}