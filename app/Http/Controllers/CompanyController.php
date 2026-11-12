<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Store;
use App\Support\BusinessProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = $request->user()->companies()
            ->withCount('stores')
            ->wherePivot('status', 'active')
            ->orderBy('companies.name')
            ->get();

        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        $profiles = BusinessProfiles::labels();

        return view('companies.create', compact('profiles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'trade_name' => ['nullable', 'string', 'max:200'],
            'nuit' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'business_type' => [
                'required',
                Rule::in(array_keys(BusinessProfiles::labels())),
            ],
            'store_name' => ['required', 'string', 'max:150'],
            'store_code' => ['nullable', 'string', 'max:50'],
        ]);

        [$company, $store] = DB::transaction(function () use ($data, $request) {
            $company = Company::create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'trade_name' => $data['trade_name'] ?? null,
                'nuit' => $data['nuit'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'currency' => 'MZN',
                'timezone' => 'Africa/Maputo',
                'status' => 'active',
            ]);

            $company->users()->attach($request->user()->id, [
                'is_owner' => true,
                'role' => 'owner',
                'status' => 'active',
            ]);

            $company->applyBusinessProfile($data['business_type']);

            $store = Store::create([
                'company_id' => $company->id,
                'name' => $data['store_name'],
                'code' => $data['store_code'] ?? null,
                'is_main' => true,
                'status' => 'active',
            ]);
            $store->users()->attach($request->user()->id, ['status' => 'active']);

            AuditLog::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'user_id' => $request->user()->id,
                'action' => 'company.created',
                'subject_type' => Company::class,
                'subject_id' => $company->id,
                'metadata' => [
                    'name' => $company->name,
                    'business_type' => $data['business_type'],
                    'main_store_id' => $store->id,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            return [$company, $store];
        });

        $request->session()->put([
            'active_company_id' => $company->id,
            'active_store_id' => $store->id,
        ]);

        return redirect()->route('dashboard')->with(
            'success',
            'Empresa criada e seleccionada com sucesso.'
        );
    }
}
