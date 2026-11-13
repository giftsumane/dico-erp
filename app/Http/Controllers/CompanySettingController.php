<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $company = app('activeCompany');
        $company->load('bankAccounts');

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

            'bank_accounts' => ['nullable', 'array', 'max:2'],
            'bank_accounts.*.bank_name' => ['nullable', 'string', 'max:150'],
            'bank_accounts.*.account_number' => ['nullable', 'string', 'max:100'],
            'bank_accounts.*.nib' => ['nullable', 'string', 'max:100'],
            'bank_accounts.*.iban' => ['nullable', 'string', 'max:100'],
            'bank_accounts.*.swift_code' => ['nullable', 'string', 'max:50'],
        ]);

        $accounts = collect($data['bank_accounts'] ?? [])->take(2)
            ->filter(fn ($account) => collect($account)->filter(fn ($value) => filled($value))->isNotEmpty())
            ->values();
        unset($data['bank_accounts']);

        foreach ($accounts as $index => $account) {
            if (blank($account['bank_name'] ?? null)) {
                return back()->withErrors([
                    "bank_accounts.{$index}.bank_name" => 'Indique o nome do banco para esta conta.',
                ])->withInput();
            }
        }

        DB::transaction(function () use ($company, $data, $accounts) {
            $company->update($data);
            $company->bankAccounts()->delete();
            foreach ($accounts as $index => $account) {
                $company->bankAccounts()->create(array_merge($account, ['position' => $index + 1]));
            }
        });

        return redirect()
            ->route('settings.company.edit')
            ->with(
                'success',
                'Configurações actualizadas com sucesso.'
            );
    }
}
