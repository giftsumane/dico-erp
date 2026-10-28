<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        $company = app('activeCompany');

        $brands = Brand::where('company_id', $company->id)
            ->orderBy('name')
            ->paginate(20);

        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        return view('brands.create');
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

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        Brand::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => true,
        ]);

        return redirect()
            ->route('brands.index')
            ->with('success', 'Marca criada com sucesso.');
    }
}