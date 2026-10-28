<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');

        $search = trim((string) $request->get('search'));

        $suppliers = Supplier::query()
            ->where('company_id', $company->id)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('nuit', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view(
            'suppliers.index',
            compact(
                'suppliers',
                'search'
            )
        );
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:200',
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

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        Supplier::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'nuit' => $data['nuit'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'active' => true,
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                'Fornecedor criado com sucesso.'
            );
    }

    public function edit(Supplier $supplier)
    {
        $company = app('activeCompany');

        abort_unless(
            $supplier->company_id === $company->id,
            403
        );

        return view(
            'suppliers.edit',
            compact('supplier')
        );
    }

    public function update(
        Request $request,
        Supplier $supplier
    ) {
        $company = app('activeCompany');

        abort_unless(
            $supplier->company_id === $company->id,
            403
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:200',
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

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $supplier->update([
            'name' => $data['name'],
            'nuit' => $data['nuit'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                'Fornecedor actualizado com sucesso.'
            );
    }
}