<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');

        $search = trim((string) $request->get('search'));

        $customers = Customer::query()
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
            'customers.index',
            compact('customers', 'search')
        );
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'nuit' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        Customer::create([
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
            ->route('customers.index')
            ->with('success', 'Cliente criado com sucesso.');
    }

    public function edit(Customer $customer)
    {
        $company = app('activeCompany');

        abort_unless(
            $customer->company_id === $company->id,
            403
        );

        return view(
            'customers.edit',
            compact('customer')
        );
    }

    public function update(
        Request $request,
        Customer $customer
    ) {
        $company = app('activeCompany');

        abort_unless(
            $customer->company_id === $company->id,
            403
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'nuit' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);

        $customer->update([
            'name' => $data['name'],
            'nuit' => $data['nuit'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente actualizado com sucesso.');
    }
}