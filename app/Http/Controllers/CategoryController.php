<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $company = app('activeCompany');

        $categories = Category::where(
            'company_id',
            $company->id
        )
            ->orderBy('name')
            ->paginate(20);

        return view(
            'categories.index',
            compact('categories')
        );
    }

    public function create()
    {
        return view('categories.create');
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

        Category::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'active' => true,
        ]);

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'Categoria criada com sucesso.'
            );
    }
}