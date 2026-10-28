<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $companies = $user->companies()
            ->wherePivot('status', 'active')
            ->where('companies.status', 'active')
            ->orderBy('companies.name')
            ->get();

        if ($companies->isEmpty()) {
            abort(
                403,
                'A tua conta não está associada a nenhuma empresa activa.'
            );
        }

        $activeCompanyId = $request->session()
            ->get('active_company_id');

        $activeCompany = $companies->firstWhere(
            'id',
            $activeCompanyId
        );

        /*
         * Seleccionar automaticamente a primeira empresa
         * quando ainda não existe uma empresa activa válida.
         */
        if (! $activeCompany) {
            $activeCompany = $companies->first();

            $request->session()->put(
                'active_company_id',
                $activeCompany->id
            );

            $request->session()->forget('active_store_id');
        }

        $stores = $user->stores()
            ->where('stores.company_id', $activeCompany->id)
            ->where('stores.status', 'active')
            ->wherePivot('status', 'active')
            ->orderByDesc('stores.is_main')
            ->orderBy('stores.name')
            ->get();

        $activeStoreId = $request->session()
            ->get('active_store_id');

        $activeStore = $stores->firstWhere(
            'id',
            $activeStoreId
        );

        /*
         * Seleccionar automaticamente a loja principal
         * ou a primeira loja disponível.
         */
        if (! $activeStore && $stores->isNotEmpty()) {
            $activeStore = $stores->first();

            $request->session()->put(
                'active_store_id',
                $activeStore->id
            );
        }

        if ($stores->isEmpty()) {
            $request->session()->forget('active_store_id');

            $activeStore = null;
        }

        /*
         * Disponibilizar nas views.
         */
        view()->share([
            'availableCompanies' => $companies,
            'availableStores' => $stores,
            'activeCompany' => $activeCompany,
            'activeStore' => $activeStore,
        ]);

        /*
         * Disponibilizar nos controllers através de:
         *
         * app('activeCompany')
         * app('activeStore')
         */
        app()->instance(
            'activeCompany',
            $activeCompany
        );

        $activeCompany->loadMissing(
            'features'
        );

        app()->instance(
            'activeStore',
            $activeStore
        );

        $companyUser = $activeCompany->users()
        ->where('users.id', $user->id)
        ->wherePivot('status', 'active')
        ->first();

        $activeRole = $companyUser?->pivot?->role;

        app()->instance(
            'activeRole',
            $activeRole
        );

        view()->share(
            'activeRole',
            $activeRole
        );

        return $next($request);
    }
}