<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductAttributeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Lista
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $company = app('activeCompany');

        $attributes = ProductAttribute::query()
            ->with([
                'values' => function ($query) {
                    $query
                        ->orderBy('position')
                        ->orderBy('value');
                },
            ])
            ->where('company_id', $company->id)
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return view(
            'attributes.index',
            compact('attributes')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Criar
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view(
            'attributes.create'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'position' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ]);

        $name = trim($data['name']);

        $slug = Str::slug($name);

        /*
        |--------------------------------------------------------------------------
        | Verificar duplicação dentro da empresa
        |--------------------------------------------------------------------------
        */

        $exists = ProductAttribute::query()
            ->where('company_id', $company->id)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' =>
                        'Já existe um atributo com este nome.',
                ]);
        }

        ProductAttribute::create([
            'company_id' => $company->id,

            'name' => $name,

            'slug' => $slug,

            'position' =>
                $data['position'] ?? 0,

            'active' => true,
        ]);

        return redirect()
            ->route('attributes.index')
            ->with(
                'success',
                'Atributo criado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    public function edit(
        ProductAttribute $attribute
    ) {
        $company = app('activeCompany');

        $this->ensureCompanyAccess(
            $attribute,
            $company->id
        );

        return view(
            'attributes.edit',
            compact('attribute')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        ProductAttribute $attribute
    ) {
        $company = app('activeCompany');

        $this->ensureCompanyAccess(
            $attribute,
            $company->id
        );

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'position' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $name = trim($data['name']);

        $slug = Str::slug($name);

        $exists = ProductAttribute::query()
            ->where('company_id', $company->id)
            ->where('slug', $slug)
            ->where('id', '!=', $attribute->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'name' =>
                        'Já existe um atributo com este nome.',
                ]);
        }

        $attribute->update([
            'name' => $name,

            'slug' => $slug,

            'position' =>
                $data['position'] ?? 0,

            'active' =>
                $request->boolean('active'),
        ]);

        return redirect()
            ->route('attributes.index')
            ->with(
                'success',
                'Atributo actualizado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Segurança multi-empresa
    |--------------------------------------------------------------------------
    */

    private function ensureCompanyAccess(
        ProductAttribute $attribute,
        int $companyId
    ): void {
        abort_unless(
            $attribute->company_id === $companyId,
            403
        );
    }
}