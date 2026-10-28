<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class BusinessContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
        ], [
            'company_id.required' => 'Seleccione uma empresa.',
            'company_id.exists' => 'A empresa seleccionada não existe.',
            'store_id.exists' => 'A loja seleccionada não existe.',
        ]);

        $user = $request->user();

        /*
         * Confirmar que o utilizador pertence à empresa.
         */
        $company = $user->companies()
            ->where('companies.id', $data['company_id'])
            ->wherePivot('status', 'active')
            ->where('companies.status', 'active')
            ->first();

        if (! $company) {
            throw ValidationException::withMessages([
                'company_id' => 'Não tens acesso a esta empresa.',
            ]);
        }

        $store = null;

        if (! empty($data['store_id'])) {
            $store = $user->stores()
                ->where('stores.id', $data['store_id'])
                ->where('stores.company_id', $company->id)
                ->where('stores.status', 'active')
                ->wherePivot('status', 'active')
                ->first();
        }

        /*
        * Se a loja enviada não pertencer à nova empresa,
        * seleccionar automaticamente a loja principal.
        */
        if (! $store) {
            $store = $user->stores()
                ->where('stores.company_id', $company->id)
                ->where('stores.status', 'active')
                ->wherePivot('status', 'active')
                ->orderByDesc('stores.is_main')
                ->orderBy('stores.name')
                ->first();
        }

        $request->session()->put([
            'active_company_id' => $company->id,
            'active_store_id' => $store?->id,
        ]);
        
        return back()->with(
            'success',
            'Empresa e loja activas foram actualizadas.'
        );
    }
}