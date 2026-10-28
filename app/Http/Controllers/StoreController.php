<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');

        $search = trim((string) $request->get('search'));

        $stores = Store::query()
            ->where('company_id', $company->id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view(
            'stores.index',
            compact(
                'stores',
                'search'
            )
        );
    }

    public function create()
    {
        return view('stores.create');
    }

    public function store(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique('stores')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    ),
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

            'is_main' => [
                'nullable',
                'boolean',
            ],
        ]);

        $isMain = $request->boolean('is_main');

        if ($isMain) {
            Store::query()
                ->where('company_id', $company->id)
                ->update([
                    'is_main' => false,
                ]);
        }

        $store = Store::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'code' => $data['code'] ?: null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_main' => $isMain,
            'status' => 'active',
        ]);

        /*
         * Associar automaticamente o utilizador actual
         * à nova loja.
         */
        $store->users()->syncWithoutDetaching([
            auth()->id() => [
                'status' => 'active',
            ],
        ]);

        return redirect()
            ->route('stores.index')
            ->with(
                'success',
                'Loja criada com sucesso.'
            );
    }

    public function edit(Store $store)
    {
        $company = app('activeCompany');

        abort_unless(
            $store->company_id === $company->id,
            403
        );

        return view(
            'stores.edit',
            compact('store')
        );
    }

    public function update(
        Request $request,
        Store $store
    ) {
        $company = app('activeCompany');

        abort_unless(
            $store->company_id === $company->id,
            403
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique('stores')
                    ->ignore($store->id)
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    ),
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

            'is_main' => [
                'nullable',
                'boolean',
            ],

            'status' => [
                'required',
                'in:active,inactive',
            ],
        ]);

        $isMain = $request->boolean('is_main');

        if ($isMain) {
            Store::query()
                ->where('company_id', $company->id)
                ->where('id', '!=', $store->id)
                ->update([
                    'is_main' => false,
                ]);
        }

        $store->update([
            'name' => $data['name'],
            'code' => $data['code'] ?: null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'is_main' => $isMain,
            'status' => $data['status'],
        ]);

        return redirect()
            ->route('stores.index')
            ->with(
                'success',
                'Loja actualizada com sucesso.'
            );
    }
}