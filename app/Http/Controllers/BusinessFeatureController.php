<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\BusinessProfiles;
use App\Support\CompanyFeatures;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessFeatureController extends Controller
{
    public function edit()
    {
        $company = app('activeCompany');

        $profiles =
            BusinessProfiles::labels();

        $features = [
            /*
            |--------------------------------------------------------------------------
            | Operação principal
            |--------------------------------------------------------------------------
            */

            'pos' => [
                'label' => 'Ponto de Venda',
                'description' =>
                    'Permite realizar vendas através do POS.',
                'group' => 'core',
            ],

            'sales' => [
                'label' => 'Vendas',
                'description' =>
                    'Histórico, consulta e gestão de vendas.',
                'group' => 'core',
            ],

            'inventory' => [
                'label' => 'Stock',
                'description' =>
                    'Gestão de quantidades e movimentos de stock.',
                'group' => 'core',
            ],

            'purchases' => [
                'label' => 'Compras',
                'description' =>
                    'Compras e entradas de mercadoria de fornecedores.',
                'group' => 'core',
            ],

            'receivables' => [
                'label' => 'Contas a Receber',
                'description' =>
                    'Gestão de vendas a crédito e pagamentos pendentes.',
                'group' => 'core',
            ],

            /*
            |--------------------------------------------------------------------------
            | Produtos
            |--------------------------------------------------------------------------
            */

            'brands' => [
                'label' => 'Marcas',
                'description' =>
                    'Organizar produtos por marcas.',
                'group' => 'products',
            ],

            'variants' => [
                'label' => 'Variantes de Produto',
                'description' =>
                    'Tamanhos, cores e outras variações de um mesmo produto.',
                'group' => 'products',
            ],

            'serial_numbers' => [
                'label' => 'Números de Série',
                'description' =>
                    'Controlo individual por serial, IMEI ou identificador único.',
                'group' => 'products',
            ],

            /*
            |--------------------------------------------------------------------------
            | Stock avançado
            |--------------------------------------------------------------------------
            */

            'stock_batches' => [
                'label' => 'Stock por Lotes',
                'description' =>
                    'Separar stock por entrada, lote ou remessa.',
                'group' => 'inventory',
            ],

            'imports' => [
                'label' => 'Importações / Viagens',
                'description' =>
                    'Controlar mercadoria por viagem, importação ou remessa.',
                'group' => 'inventory',
            ],

            'expiry_dates' => [
                'label' => 'Validades',
                'description' =>
                    'Controlar datas de validade dos lotes.',
                'group' => 'inventory',
            ],

            /*
            |--------------------------------------------------------------------------
            | Restauração
            |--------------------------------------------------------------------------
            */

            'recipes' => [
                'label' => 'Receitas / Fichas Técnicas',
                'description' =>
                    'Produtos compostos por ingredientes.',
                'group' => 'food',
            ],

            'ingredients' => [
                'label' => 'Ingredientes',
                'description' =>
                    'Gestão de matérias-primas e ingredientes.',
                'group' => 'food',
            ],

            'tables' => [
                'label' => 'Mesas',
                'description' =>
                    'Gestão de mesas para restaurantes.',
                'group' => 'food',
            ],

            'kitchen' => [
                'label' => 'Cozinha',
                'description' =>
                    'Fluxo de pedidos e preparação na cozinha.',
                'group' => 'food',
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Features activas
        |--------------------------------------------------------------------------
        */

        $enabledFeatures = $company
            ->features()
            ->where('enabled', true)
            ->pluck('feature')
            ->toArray();

        return view(
            'settings.features',
            compact(
                'company',
                'profiles',
                'features',
                'enabledFeatures'
            )
        );
    }


    public function update(Request $request)
    {
        $company = app('activeCompany');

        $data = $request->validate([
            'business_type' => [
                'required',
                Rule::in(
                    array_keys(
                        BusinessProfiles::labels()
                    )
                ),
            ],

            'features' => [
                'nullable',
                'array',
            ],

            'features.*' => [
                Rule::in(
                    CompanyFeatures::all()
                ),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Perfil
        |--------------------------------------------------------------------------
        */

        $company->update([
            'business_type' =>
                $data['business_type'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Features escolhidas
        |--------------------------------------------------------------------------
        */

        $selectedFeatures =
            $data['features'] ?? [];

        foreach (
            CompanyFeatures::all()
            as $feature
        ) {
            if (
                in_array(
                    $feature,
                    $selectedFeatures,
                    true
                )
            ) {
                $company->enableFeature(
                    $feature
                );
            } else {
                $company->disableFeature(
                    $feature
                );
            }
        }

        return redirect()
            ->route(
                'settings.features.edit'
            )
            ->with(
                'success',
                'Configuração do negócio actualizada com sucesso.'
            );
    }


    public function applyProfile(
        Request $request
    ) {
        $company = app('activeCompany');

        $data = $request->validate([
            'business_type' => [
                'required',
                Rule::in(
                    array_keys(
                        BusinessProfiles::labels()
                    )
                ),
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Limpar configuração anterior
        |--------------------------------------------------------------------------
        */

        foreach (
            CompanyFeatures::all()
            as $feature
        ) {
            $company->disableFeature(
                $feature
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Aplicar perfil
        |--------------------------------------------------------------------------
        */

        $company->applyBusinessProfile(
            $data['business_type']
        );

        return redirect()
            ->route(
                'settings.features.edit'
            )
            ->with(
                'success',
                'Perfil aplicado. Pode agora personalizar as funcionalidades.'
            );
    }
}