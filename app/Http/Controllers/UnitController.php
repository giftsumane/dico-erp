<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $company = app('activeCompany');

        $units = Unit::where('company_id', $company->id)
            ->orderBy('name')
            ->paginate(20);

        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'symbol' => [
                'required',
                'string',
                'max:20',
            ],
        ]);

        Unit::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'symbol' => $data['symbol'],
            'active' => true,
        ]);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidade criada com sucesso.');
    }
}