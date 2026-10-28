<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');

        $search = trim(
            (string) $request->get('search')
        );

        $users = $company->users()
            ->with('stores')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhere('users.phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('users.name')
            ->paginate(20)
            ->withQueryString();

        return view(
            'users.index',
            compact(
                'users',
                'search'
            )
        );
    }

    public function create()
    {
        $company = app('activeCompany');

        $stores = Store::query()
            ->where('company_id', $company->id)
            ->where('status', 'active')
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        return view(
            'users.create',
            compact('stores')
        );
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

            'email' => [
                'required',
                'email',
                'max:150',
                'unique:users,email',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in([
                    'admin',
                    'manager',
                    'cashier',
                ]),
            ],

            'stores' => [
                'nullable',
                'array',
            ],

            'stores.*' => [
                'integer',
            ],
        ]);

        DB::transaction(function () use (
            $data,
            $company
        ) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make(
                    $data['password']
                ),
                'status' => 'active',
            ]);

            $company->users()->attach(
                $user->id,
                [
                    'is_owner' => false,
                    'role' => $data['role'],
                    'status' => 'active',
                ]
            );

            $storeIds = Store::query()
                ->where(
                    'company_id',
                    $company->id
                )
                ->whereIn(
                    'id',
                    $data['stores'] ?? []
                )
                ->pluck('id');

            foreach ($storeIds as $storeId) {
                $user->stores()->attach(
                    $storeId,
                    [
                        'status' => 'active',
                    ]
                );
            }
        });

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Utilizador criado com sucesso.'
            );
    }

    public function edit(User $user)
    {
        $company = app('activeCompany');

        $pivot = $company->users()
            ->where(
                'users.id',
                $user->id
            )
            ->firstOrFail()
            ->pivot;

        $stores = Store::query()
            ->where(
                'company_id',
                $company->id
            )
            ->orderByDesc('is_main')
            ->orderBy('name')
            ->get();

        $selectedStores = $user->stores()
            ->where(
                'stores.company_id',
                $company->id
            )
            ->pluck('stores.id')
            ->all();

        return view(
            'users.edit',
            compact(
                'user',
                'pivot',
                'stores',
                'selectedStores'
            )
        );
    }

    public function update(
        Request $request,
        User $user
    ) {
        $company = app('activeCompany');

        $companyUser = $company->users()
            ->where(
                'users.id',
                $user->id
            )
            ->firstOrFail();

        $pivot = $companyUser->pivot;

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')
                    ->ignore($user->id),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],

            'role' => [
                'required',
                Rule::in([
                    'owner',
                    'admin',
                    'manager',
                    'cashier',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'active',
                    'inactive',
                ]),
            ],

            'stores' => [
                'nullable',
                'array',
            ],

            'stores.*' => [
                'integer',
            ],
        ]);

        /*
         * O proprietário não pode deixar
         * de ser owner por este formulário.
         */
        if ($pivot->is_owner) {
            $data['role'] = 'owner';
            $data['status'] = 'active';
        }

        DB::transaction(function () use (
            $data,
            $company,
            $user,
            $pivot
        ) {
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make(
                    $data['password']
                );
            }

            $user->update($userData);

            $company->users()->updateExistingPivot(
                $user->id,
                [
                    'role' => $data['role'],
                    'status' => $data['status'],
                    'is_owner' => $pivot->is_owner,
                ]
            );

            $storeIds = Store::query()
                ->where(
                    'company_id',
                    $company->id
                )
                ->whereIn(
                    'id',
                    $data['stores'] ?? []
                )
                ->pluck('id');

            /*
             * Remover apenas associações de lojas
             * desta empresa.
             */
            $companyStoreIds = Store::query()
                ->where(
                    'company_id',
                    $company->id
                )
                ->pluck('id');

            $user->stores()
                ->detach($companyStoreIds);

            foreach ($storeIds as $storeId) {
                $user->stores()->attach(
                    $storeId,
                    [
                        'status' => 'active',
                    ]
                );
            }
        });

        return redirect()
            ->route('users.index')
            ->with(
                'success',
                'Utilizador actualizado com sucesso.'
            );
    }
}