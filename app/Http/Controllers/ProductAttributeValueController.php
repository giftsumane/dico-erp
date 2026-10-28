<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductAttributeValueController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Criar valor
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        ProductAttribute $attribute
    ) {
        $company = app('activeCompany');

        $this->ensureAttributeAccess(
            $attribute,
            $company->id
        );

        $data = $request->validate([
            'value' => [
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

        $value = trim($data['value']);

        $slug = Str::slug($value);

        $exists = ProductAttributeValue::query()
            ->where(
                'product_attribute_id',
                $attribute->id
            )
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'value' =>
                        'Este valor já existe neste atributo.',
                ]);
        }

        $attribute->values()->create([
            'value' => $value,

            'slug' => $slug,

            'position' =>
                $data['position'] ?? 0,

            'active' => true,
        ]);

        return redirect()
            ->route('attributes.index')
            ->with(
                'success',
                'Valor adicionado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar valor
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        ProductAttribute $attribute,
        ProductAttributeValue $value
    ) {
        $company = app('activeCompany');

        $this->ensureAttributeAccess(
            $attribute,
            $company->id
        );

        $this->ensureValueAccess(
            $attribute,
            $value
        );

        $data = $request->validate([
            'value' => [
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

        $name = trim($data['value']);

        $slug = Str::slug($name);

        $exists = ProductAttributeValue::query()
            ->where(
                'product_attribute_id',
                $attribute->id
            )
            ->where('slug', $slug)
            ->where('id', '!=', $value->id)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'value' =>
                        'Este valor já existe neste atributo.',
                ]);
        }

        $value->update([
            'value' => $name,

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
                'Valor actualizado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Remover valor
    |--------------------------------------------------------------------------
    */

    public function destroy(
        ProductAttribute $attribute,
        ProductAttributeValue $value
    ) {
        $company = app('activeCompany');

        $this->ensureAttributeAccess(
            $attribute,
            $company->id
        );

        $this->ensureValueAccess(
            $attribute,
            $value
        );

        /*
        |--------------------------------------------------------------------------
        | Não eliminar se já estiver associado a variantes
        |--------------------------------------------------------------------------
        */

        if ($value->products()->exists()) {
            return back()
                ->withErrors([
                    'value' =>
                        'Este valor já está associado a produtos e não pode ser eliminado.',
                ]);
        }

        $value->delete();

        return redirect()
            ->route('attributes.index')
            ->with(
                'success',
                'Valor eliminado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Segurança
    |--------------------------------------------------------------------------
    */

    private function ensureAttributeAccess(
        ProductAttribute $attribute,
        int $companyId
    ): void {
        abort_unless(
            $attribute->company_id === $companyId,
            403
        );
    }


    private function ensureValueAccess(
        ProductAttribute $attribute,
        ProductAttributeValue $value
    ): void {
        abort_unless(
            $value->product_attribute_id ===
                $attribute->id,
            403
        );
    }
}