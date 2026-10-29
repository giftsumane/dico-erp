<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const MAX_VARIANT_COMBINATIONS = 500;

    /*
    |--------------------------------------------------------------------------
    | Lista
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $company = app('activeCompany');

        $search = trim(
            (string) $request->get('search')
        );

        $products = Product::query()
            ->with([
                'category',
                'brand',
                'unit',
                'parent',
            ])
            ->where(
                'company_id',
                $company->id
            )
            ->when(
                $search,
                function ($query) use ($search) {
                    $query->where(
                        function ($subQuery) use ($search) {
                            $subQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'barcode',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view(
            'products.index',
            compact(
                'products',
                'search'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Criar
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $company = app('activeCompany');

        $categories = Category::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where('active', true)
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Atributos para produtos com variantes
        |--------------------------------------------------------------------------
        */

        $attributes = collect();

        if ($company->hasFeature('variants')) {
            $attributes = $company
                ->productAttributes()
                ->with([
                    'values' => function ($query) {
                        $query
                            ->where('active', true)
                            ->orderBy('position')
                            ->orderBy('value');
                    },
                ])
                ->where('active', true)
                ->orderBy('position')
                ->orderBy('name')
                ->get();
        }

        return view(
            'products.create',
            compact(
                'categories',
                'brands',
                'units',
                'attributes'
            )
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
            /*
            |--------------------------------------------------------------------------
            | Arquitectura do produto
            |--------------------------------------------------------------------------
            */

            'product_type' => [
                'required',
                Rule::in([
                    Product::TYPE_SIMPLE,
                    Product::TYPE_VARIABLE,
                    Product::TYPE_SERVICE,
                ]),
            ],

            'inventory_mode' => [
                'nullable',
                Rule::in([
                    Product::INVENTORY_NONE,
                    Product::INVENTORY_SIMPLE,
                    Product::INVENTORY_BATCH,
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Atributos das variantes
            |--------------------------------------------------------------------------
            */

            'attribute_ids' => [
                'nullable',
                'array',
            ],

            'attribute_ids.*' => [
                'integer',
                'distinct',
            ],

            'variant_option_ids' => [
                'nullable',
                'array',
            ],

            'variant_option_ids.*' => [
                'integer',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Dados principais
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'max:200',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique('products')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    ),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique('products')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    ),
            ],

            'category_id' => [
                'nullable',
                'integer',
            ],

            'brand_id' => [
                'nullable',
                'integer',
            ],

            'unit_id' => [
                'nullable',
                'integer',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Valores
            |--------------------------------------------------------------------------
            */

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Compatibilidade com stock actual
            |--------------------------------------------------------------------------
            */

            'track_stock' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Segurança da feature de variantes
        |--------------------------------------------------------------------------
        */

        if (
            $data['product_type'] === Product::TYPE_VARIABLE
            && ! $company->hasFeature('variants')
        ) {
            abort(
                403,
                'A funcionalidade de variantes não está activa para esta empresa.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validar relações dentro da empresa
        |--------------------------------------------------------------------------
        */

        $categoryId = $this->validateCategory(
            $data['category_id'] ?? null,
            $company->id
        );

        $brandId = $this->validateBrand(
            $data['brand_id'] ?? null,
            $company->id
        );

        $unitId = $this->validateUnit(
            $data['unit_id'] ?? null,
            $company->id
        );


        /*
        |--------------------------------------------------------------------------
        | Validar atributos seleccionados
        |--------------------------------------------------------------------------
        */

        $attributeIds = $this->validateAttributeIds(
            $data['attribute_ids'] ?? [],
            $company->id,
            $data['product_type']
        );

        $variantOptionIds = $this->validateVariantOptionIds(
            $data['variant_option_ids'] ?? [],
            $attributeIds,
            $company->id,
            $data['product_type']
        );


        /*
        |--------------------------------------------------------------------------
        | Determinar arquitectura
        |--------------------------------------------------------------------------
        |
        | simple + track_stock
        |     → inventory_mode = simple
        |
        | simple + sem track_stock
        |     → inventory_mode = none
        |
        | variable
        |     → inventory_mode = none
        |     → track_stock = false
        |
        | service
        |     → inventory_mode = none
        |     → track_stock = false
        |
        */

        $inventoryMode = $this->validateInventoryMode(
            $data['inventory_mode'] ?? Product::INVENTORY_NONE,
            $data['product_type'],
            $company
        );

        $architecture = $this->resolveArchitecture(
            $data['product_type'],
            $inventoryMode
        );


        /*
        |--------------------------------------------------------------------------
        | Criar produto
        |--------------------------------------------------------------------------
        */

        $product = DB::transaction(
            function () use (
                $company,
                $architecture,
                $categoryId,
                $brandId,
                $unitId,
                $data,
                $attributeIds,
                $variantOptionIds
            ) {
                $product = Product::create([
                    'company_id' => $company->id,

                    'parent_id' => null,

                    'product_type' =>
                        $architecture['product_type'],

                    'inventory_mode' =>
                        $architecture['inventory_mode'],

                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,

                    'name' => $data['name'],

                    'sku' =>
                        ! empty($data['sku'])
                            ? $data['sku']
                            : null,

                    'barcode' =>
                        ! empty($data['barcode'])
                            ? $data['barcode']
                            : null,

                    'description' =>
                        $data['description'] ?? null,

                    'purchase_price' =>
                        $data['purchase_price'] ?? 0,

                    'sale_price' =>
                        $data['sale_price'],

                    'tax_rate' =>
                        $data['tax_rate'] ?? 0,

                    'minimum_stock' =>
                        $architecture['track_stock']
                            ? ($data['minimum_stock'] ?? 0)
                            : 0,

                    'track_stock' =>
                        $architecture['track_stock'],

                    'active' => true,
                ]);

                $this->syncProductAttributes(
                    $product,
                    $attributeIds
                );

                $this->syncVariantOptions(
                    $product,
                    $variantOptionIds
                );

                return $product;
            }
        );


        return redirect()
            ->route('products.index')
            ->with(
                'success',
                $product->isVariable()
                    ? 'Produto com variantes criado com sucesso.'
                    : 'Produto criado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Editar
    |--------------------------------------------------------------------------
    */

    public function edit(Product $product)
    {
        $company = app('activeCompany');

        abort_unless(
            (int) $product->company_id === (int) $company->id,
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Variantes-filhas serão geridas pelo produto principal
        |--------------------------------------------------------------------------
        */

        abort_if(
            $product->isVariant(),
            403,
            'As variantes são geridas através do produto principal.'
        );

        $categories = Category::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $brands = Brand::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $units = Unit::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where('active', true)
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Atributos
        |--------------------------------------------------------------------------
        |
        | Incluímos os atributos já associados ao produto mesmo que tenham sido
        | desactivados posteriormente. Assim não perdemos configuração histórica.
        |--------------------------------------------------------------------------
        */

        $product->load([
            'attributes',
            'variantOptions',
            'variants.attributeValues.attribute',
        ]);

        $selectedAttributeIds = $product
            ->attributes
            ->pluck('id')
            ->all();

        $selectedVariantOptionIds = $product
            ->variantOptions
            ->pluck('id')
            ->all();

        $attributes = collect();

        if ($company->hasFeature('variants')) {
            $attributes = $company
                ->productAttributes()
                ->with([
                    'values' => function ($query) use ($selectedVariantOptionIds) {
                        $query
                            ->where(function ($subQuery) use ($selectedVariantOptionIds) {
                                $subQuery->where('active', true);

                                if (! empty($selectedVariantOptionIds)) {
                                    $subQuery->orWhereIn(
                                        'id',
                                        $selectedVariantOptionIds
                                    );
                                }
                            })
                            ->orderBy('position')
                            ->orderBy('value');
                    },
                ])
                ->where(
                    function ($query) use ($selectedAttributeIds) {
                        $query
                            ->where('active', true);

                        if (! empty($selectedAttributeIds)) {
                            $query->orWhereIn(
                                'id',
                                $selectedAttributeIds
                            );
                        }
                    }
                )
                ->orderBy('position')
                ->orderBy('name')
                ->get();
        }

        return view(
            'products.edit',
            compact(
                'product',
                'categories',
                'brands',
                'units',
                'attributes'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Product $product
    ) {
        $company = app('activeCompany');

        abort_unless(
            (int) $product->company_id === (int) $company->id,
            403
        );


        /*
        |--------------------------------------------------------------------------
        | Não permitimos editar directamente variantes-filhas
        |--------------------------------------------------------------------------
        */

        abort_if(
            $product->isVariant(),
            403,
            'As variantes são geridas através do produto principal.'
        );


        $data = $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Arquitectura
            |--------------------------------------------------------------------------
            */

            'product_type' => [
                'required',
                Rule::in([
                    Product::TYPE_SIMPLE,
                    Product::TYPE_VARIABLE,
                    Product::TYPE_SERVICE,
                ]),
            ],

            'inventory_mode' => [
                'nullable',
                Rule::in([
                    Product::INVENTORY_NONE,
                    Product::INVENTORY_SIMPLE,
                    Product::INVENTORY_BATCH,
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | Atributos das variantes
            |--------------------------------------------------------------------------
            */

            'attribute_ids' => [
                'nullable',
                'array',
            ],

            'attribute_ids.*' => [
                'integer',
                'distinct',
            ],

            'variant_option_ids' => [
                'nullable',
                'array',
            ],

            'variant_option_ids.*' => [
                'integer',
                'distinct',
            ],

            /*
            |--------------------------------------------------------------------------
            | Produto
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'max:200',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique('products')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    )
                    ->ignore($product->id),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique('products')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    )
                    ->ignore($product->id),
            ],

            'category_id' => [
                'nullable',
                'integer',
            ],

            'brand_id' => [
                'nullable',
                'integer',
            ],

            'unit_id' => [
                'nullable',
                'integer',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Valores
            |--------------------------------------------------------------------------
            */

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Stock
            |--------------------------------------------------------------------------
            */

            'track_stock' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Segurança da feature de variantes
        |--------------------------------------------------------------------------
        */

        if (
            $data['product_type'] === Product::TYPE_VARIABLE
            && ! $company->hasFeature('variants')
        ) {
            abort(
                403,
                'A funcionalidade de variantes não está activa para esta empresa.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Não converter produto variável com variantes existentes
        |--------------------------------------------------------------------------
        |
        | Esta protecção será especialmente importante a partir da Fase 2.5.
        |--------------------------------------------------------------------------
        */

        if (
            $product->isVariable()
            && $data['product_type'] !== Product::TYPE_VARIABLE
            && $product->variants()->exists()
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'product_type' =>
                        'Este produto já possui variantes e não pode ser convertido directamente para outro tipo.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validar relações
        |--------------------------------------------------------------------------
        */

        $categoryId = $this->validateCategory(
            $data['category_id'] ?? null,
            $company->id
        );

        $brandId = $this->validateBrand(
            $data['brand_id'] ?? null,
            $company->id
        );

        $unitId = $this->validateUnit(
            $data['unit_id'] ?? null,
            $company->id
        );


        /*
        |--------------------------------------------------------------------------
        | Validar atributos seleccionados
        |--------------------------------------------------------------------------
        */

        $attributeIds = $this->validateAttributeIds(
            $data['attribute_ids'] ?? [],
            $company->id,
            $data['product_type'],
            $product
        );

        $variantOptionIds = $this->validateVariantOptionIds(
            $data['variant_option_ids'] ?? [],
            $attributeIds,
            $company->id,
            $data['product_type'],
            $product
        );

        $this->validateExistingVariantConfiguration(
            $product,
            $attributeIds,
            $variantOptionIds
        );


        /*
        |--------------------------------------------------------------------------
        | Resolver arquitectura
        |--------------------------------------------------------------------------
        */

        $inventoryMode = $this->validateInventoryMode(
            $data['inventory_mode'] ?? Product::INVENTORY_NONE,
            $data['product_type'],
            $company,
            $product
        );

        $this->validateInventoryModeTransition(
            $product,
            $inventoryMode
        );

        $architecture = $this->resolveArchitecture(
            $data['product_type'],
            $inventoryMode
        );


        /*
        |--------------------------------------------------------------------------
        | Actualizar
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $product,
                $architecture,
                $categoryId,
                $brandId,
                $unitId,
                $data,
                $request,
                $attributeIds,
                $variantOptionIds
            ) {
                $product->update([
                    'product_type' =>
                        $architecture['product_type'],

                    'inventory_mode' =>
                        $architecture['inventory_mode'],

                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,

                    'name' => $data['name'],

                    'sku' =>
                        ! empty($data['sku'])
                            ? $data['sku']
                            : null,

                    'barcode' =>
                        ! empty($data['barcode'])
                            ? $data['barcode']
                            : null,

                    'description' =>
                        $data['description'] ?? null,

                    'purchase_price' =>
                        $data['purchase_price'] ?? 0,

                    'sale_price' =>
                        $data['sale_price'],

                    'tax_rate' =>
                        $data['tax_rate'] ?? 0,

                    'minimum_stock' =>
                        $architecture['track_stock']
                            ? ($data['minimum_stock'] ?? 0)
                            : 0,

                    'track_stock' =>
                        $architecture['track_stock'],

                    'active' =>
                        $request->boolean('active'),
                ]);

                $this->syncProductAttributes(
                    $product,
                    $attributeIds
                );

                $this->syncVariantOptions(
                    $product,
                    $variantOptionIds
                );
            }
        );


        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Produto actualizado com sucesso.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolver arquitectura do produto
    |--------------------------------------------------------------------------
    |
    | Centralizamos aqui a compatibilidade entre:
    |
    | product_type
    | inventory_mode
    | track_stock
    |--------------------------------------------------------------------------
    */

    private function resolveArchitecture(
        string $productType,
        string $inventoryMode
    ): array {
        if ($productType === Product::TYPE_SERVICE) {
            return [
                'product_type' => Product::TYPE_SERVICE,
                'inventory_mode' => Product::INVENTORY_NONE,
                'track_stock' => false,
            ];
        }

        if ($productType === Product::TYPE_VARIABLE) {
            return [
                'product_type' => Product::TYPE_VARIABLE,
                'inventory_mode' => Product::INVENTORY_NONE,
                'track_stock' => false,
            ];
        }

        return [
            'product_type' => Product::TYPE_SIMPLE,
            'inventory_mode' => $inventoryMode,
            'track_stock' =>
                $inventoryMode !== Product::INVENTORY_NONE,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validar modo de inventário
    |--------------------------------------------------------------------------
    */

    private function validateInventoryMode(
        string $inventoryMode,
        string $productType,
        $company,
        ?Product $product = null
    ): string {
        if (
            $productType === Product::TYPE_SERVICE
            || $productType === Product::TYPE_VARIABLE
        ) {
            return Product::INVENTORY_NONE;
        }

        if (! $company->hasFeature('inventory')) {
            return Product::INVENTORY_NONE;
        }

        $allowedModes = [
            Product::INVENTORY_NONE,
            Product::INVENTORY_SIMPLE,
        ];

        $canUseBatches =
            $company->hasFeature('stock_batches');

        /*
        |--------------------------------------------------------------------------
        | Preservação histórica
        |--------------------------------------------------------------------------
        |
        | Se a feature for desactivada depois de um produto já estar em batch,
        | continuamos a aceitar o modo actual durante a edição para não converter
        | silenciosamente a arquitectura do produto.
        |--------------------------------------------------------------------------
        */

        if (
            $canUseBatches
            || (
                $product
                && $product->inventory_mode
                    === Product::INVENTORY_BATCH
            )
        ) {
            $allowedModes[] =
                Product::INVENTORY_BATCH;
        }

        if (! in_array(
            $inventoryMode,
            $allowedModes,
            true
        )) {
            throw ValidationException::withMessages([
                'inventory_mode' =>
                    'O modo de inventário seleccionado não está disponível para esta empresa.',
            ]);
        }

        if (
            $inventoryMode === Product::INVENTORY_BATCH
            && ! $canUseBatches
            && ! (
                $product
                && $product->inventory_mode
                    === Product::INVENTORY_BATCH
            )
        ) {
            throw ValidationException::withMessages([
                'inventory_mode' =>
                    'A funcionalidade de stock por lotes não está activa para esta empresa.',
            ]);
        }

        return $inventoryMode;
    }


/*
|--------------------------------------------------------------------------
| Proteger mudanças de arquitectura de stock
|--------------------------------------------------------------------------
|
| Um produto só pode mudar de modo de inventário quando não existe
| qualquer quantidade registada.
|
| A validação considera:
|
| - stock agregado em TODAS as lojas;
| - stock existente em lotes;
| - produtos simples;
| - variantes.
|
| Exemplos bloqueados:
|
| simple + stock > 0  → batch
| batch  + stock > 0  → simple
| simple + stock > 0  → none
| batch  + stock > 0  → none
|
*/

    private function validateInventoryModeTransition(
        Product $product,
        string $newInventoryMode
    ): void {

        $currentMode =
            $product->inventory_mode
            ?: Product::INVENTORY_NONE;


        /*
        |--------------------------------------------------------------------------
        | Nada mudou
        |--------------------------------------------------------------------------
        */

        if ($currentMode === $newInventoryMode) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Produtos-pai e serviços
        |--------------------------------------------------------------------------
        |
        | Produtos variable e service não possuem stock próprio.
        |
        */

        if (
            $product->isVariable()
            || $product->isService()
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Stock agregado em todas as lojas
        |--------------------------------------------------------------------------
        |
        | Não verificamos apenas a loja activa.
        |
        | Se o produto tiver:
        |
        | Maputo = 0
        | Matola = 5
        |
        | a alteração também deve ser bloqueada.
        |
        */

        $stockTotal = (float) DB::table('stocks')
            ->where(
                'product_id',
                $product->id
            )
            ->sum('quantity');

        
        $hasAnyStockBalance = DB::table('stocks')
            ->where(
                'product_id',
                $product->id
            )
            ->whereRaw('ABS(quantity) >= 0.0005')
            ->exists();


        /*
        |--------------------------------------------------------------------------
        | Stock existente em lotes
        |--------------------------------------------------------------------------
        */

        $batchTotal = (float) DB::table('stock_batches')
            ->where(
                'product_id',
                $product->id
            )
            ->sum('quantity');


        /*
        |--------------------------------------------------------------------------
        | Bloquear se existir stock
        |--------------------------------------------------------------------------
        */

        if (
            $hasAnyStockBalance
            || abs($batchTotal) >= 0.0005
        ){

            $currentLabel = match ($currentMode) {

                Product::INVENTORY_SIMPLE =>
                    'stock simples',

                Product::INVENTORY_BATCH =>
                    'stock por lotes',

                Product::INVENTORY_NONE =>
                    'sem controlo de stock',

                default =>
                    $currentMode,
            };


            $newLabel = match ($newInventoryMode) {

                Product::INVENTORY_SIMPLE =>
                    'stock simples',

                Product::INVENTORY_BATCH =>
                    'stock por lotes',

                Product::INVENTORY_NONE =>
                    'sem controlo de stock',

                default =>
                    $newInventoryMode,
            };


            $formattedStock = rtrim(
                rtrim(
                    number_format(
                        $stockTotal,
                        3,
                        ',',
                        '.'
                    ),
                    '0'
                ),
                ','
            );


            throw ValidationException::withMessages([
                'inventory_mode' =>
                    "Não é possível alterar o controlo de stock "
                    . "de {$currentLabel} para {$newLabel} porque "
                    . "este produto possui {$formattedStock} unidade(s) "
                    . "registada(s). Ajuste o stock para zero antes "
                    . "de alterar o modo de inventário.",
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Protecção histórica adicional
        |--------------------------------------------------------------------------
        |
        | Mesmo que o saldo actual seja zero, verificamos se existe algum lote
        | que, por inconsistência de dados, ainda tenha saldo diferente de zero.
        |
        */

        $hasBatchBalance = DB::table('stock_batches')
            ->where(
                'product_id',
                $product->id
            )
            ->where(
                'quantity',
                '!=',
                0
            )
            ->exists();


        if ($hasBatchBalance) {

            throw ValidationException::withMessages([
                'inventory_mode' =>
                    'Este produto ainda possui quantidade em lotes. '
                    . 'Ajuste todos os lotes para zero antes de alterar '
                    . 'o modo de inventário.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validar atributos de produto variável
    |--------------------------------------------------------------------------
    */

    private function validateAttributeIds(
        array $attributeIds,
        int $companyId,
        string $productType,
        ?Product $product = null
    ): array {
        if ($productType !== Product::TYPE_VARIABLE) {
            return [];
        }

        $attributeIds = collect($attributeIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($attributeIds->isEmpty()) {
            throw ValidationException::withMessages([
                'attribute_ids' =>
                    'Seleccione pelo menos um atributo para o produto com variantes.',
            ]);
        }

        $historicalAttributeIds = collect();

        if ($product) {
            $historicalAttributeIds = $product
                ->attributes()
                ->pluck('product_attributes.id')
                ->map(fn ($id) => (int) $id);
        }

        $validIds = ProductAttribute::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $attributeIds->all())
            ->where(function ($query) use ($historicalAttributeIds) {
                $query->where('active', true);

                if ($historicalAttributeIds->isNotEmpty()) {
                    $query->orWhereIn(
                        'id',
                        $historicalAttributeIds->all()
                    );
                }
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($validIds->count() !== $attributeIds->count()) {
            throw ValidationException::withMessages([
                'attribute_ids' =>
                    'Foi seleccionado um atributo inválido ou indisponível.',
            ]);
        }

        return $attributeIds->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Sincronizar atributos do produto
    |--------------------------------------------------------------------------
    */

    private function syncProductAttributes(
        Product $product,
        array $attributeIds
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Apenas produtos-pai do tipo variable utilizam esta relação
        |--------------------------------------------------------------------------
        */

        if (! $product->isVariable()) {
            $product
                ->attributes()
                ->detach();

            return;
        }

        $syncData = [];

        foreach (
            $attributeIds as $position => $attributeId
        ) {
            $syncData[$attributeId] = [
                'position' => $position,
            ];
        }

        $product
            ->attributes()
            ->sync($syncData);
    }



    /*
    |--------------------------------------------------------------------------
    | Validar valores permitidos para geração de variantes
    |--------------------------------------------------------------------------
    */

    private function validateVariantOptionIds(
        array $variantOptionIds,
        array $attributeIds,
        int $companyId,
        string $productType,
        ?Product $product = null
    ): array {
        if ($productType !== Product::TYPE_VARIABLE) {
            return [];
        }

        $variantOptionIds = collect($variantOptionIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($variantOptionIds->isEmpty()) {
            throw ValidationException::withMessages([
                'variant_option_ids' =>
                    'Seleccione pelo menos um valor para cada atributo do produto com variantes.',
            ]);
        }

        $historicalValueIds = collect();

        if ($product) {
            $historicalValueIds = $product
                ->variantOptions()
                ->pluck('product_attribute_values.id')
                ->map(fn ($id) => (int) $id);
        }

        $values = ProductAttributeValue::query()
            ->whereIn('id', $variantOptionIds->all())
            ->whereHas(
                'attribute',
                function ($query) use ($companyId, $attributeIds) {
                    $query
                        ->where('company_id', $companyId)
                        ->whereIn('id', $attributeIds);
                }
            )
            ->where(function ($query) use ($historicalValueIds) {
                $query->where('active', true);

                if ($historicalValueIds->isNotEmpty()) {
                    $query->orWhereIn(
                        'id',
                        $historicalValueIds->all()
                    );
                }
            })
            ->get();

        if ($values->count() !== $variantOptionIds->count()) {
            throw ValidationException::withMessages([
                'variant_option_ids' =>
                    'Foi seleccionado um valor de atributo inválido ou indisponível.',
            ]);
        }

        $attributeIdsWithValues = $values
            ->pluck('product_attribute_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $missingAttributeIds = collect($attributeIds)
            ->map(fn ($id) => (int) $id)
            ->diff($attributeIdsWithValues);

        if ($missingAttributeIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variant_option_ids' =>
                    'Seleccione pelo menos um valor para cada atributo seleccionado.',
            ]);
        }

        return $variantOptionIds->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Sincronizar valores permitidos do produto variável
    |--------------------------------------------------------------------------
    */

    private function syncVariantOptions(
        Product $product,
        array $variantOptionIds
    ): void {
        if (! $product->isVariable()) {
            $product->variantOptions()->detach();

            return;
        }

        $product->variantOptions()->sync($variantOptionIds);
    }


    private function validateExistingVariantConfiguration(
        Product $product,
        array $attributeIds,
        array $variantOptionIds
    ): void {
        if (
            ! $product->isVariable()
            || ! $product->variants()->exists()
        ) {
            return;
        }

        $currentAttributeIds = $product
            ->attributes()
            ->pluck('product_attributes.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $submittedAttributeIds = collect($attributeIds)
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($currentAttributeIds->all() !== $submittedAttributeIds->all()) {
            throw ValidationException::withMessages([
                'attribute_ids' =>
                    'Este produto já possui variantes. Não é possível alterar o conjunto de atributos depois da geração das variantes.',
            ]);
        }

        $variantIds = $product
            ->variants()
            ->pluck('id');

        $usedValueIds = DB::table('product_variant_values')
            ->whereIn('product_id', $variantIds)
            ->pluck('product_attribute_value_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $submittedValueIds = collect($variantOptionIds)
            ->map(fn ($id) => (int) $id)
            ->unique();

        if ($usedValueIds->diff($submittedValueIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variant_option_ids' =>
                    'Não é possível remover um valor que já está utilizado por uma variante existente. Pode manter o valor, adicionar novas opções ou desactivar a variante individualmente.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validar categoria
    |--------------------------------------------------------------------------
    */

    private function validateCategory(
        ?int $categoryId,
        int $companyId
    ): ?int {
        if (! $categoryId) {
            return null;
        }

        $exists = Category::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'id',
                $categoryId
            )
            ->exists();

        abort_unless(
            $exists,
            403
        );

        return $categoryId;
    }


    /*
    |--------------------------------------------------------------------------
    | Validar marca
    |--------------------------------------------------------------------------
    */

    private function validateBrand(
        ?int $brandId,
        int $companyId
    ): ?int {
        if (! $brandId) {
            return null;
        }

        $exists = Brand::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'id',
                $brandId
            )
            ->exists();

        abort_unless(
            $exists,
            403
        );

        return $brandId;
    }


    /*
    |--------------------------------------------------------------------------
    | Validar unidade
    |--------------------------------------------------------------------------
    */

    private function validateUnit(
        ?int $unitId,
        int $companyId
    ): ?int {
        if (! $unitId) {
            return null;
        }

        $exists = Unit::query()
            ->where(
                'company_id',
                $companyId
            )
            ->where(
                'id',
                $unitId
            )
            ->exists();

        abort_unless(
            $exists,
            403
        );

        return $unitId;
    }

    /*
    |--------------------------------------------------------------------------
    | Gerar variantes
    |--------------------------------------------------------------------------
    */

    public function generateVariants(Product $product)
    {
        $company = app('activeCompany');

        abort_unless(
            (int) $product->company_id === (int) $company->id,
            403
        );

        abort_unless(
            $company->hasFeature('variants'),
            403,
            'A funcionalidade de variantes não está activa.'
        );

        abort_unless(
            $product->isVariable(),
            422,
            'Apenas produtos com variantes podem gerar variantes.'
        );

        $product->load([
            'attributes',
            'variantOptions.attribute',
            'variants.attributeValues',
        ]);

        if ($product->attributes->isEmpty()) {
            return back()->withErrors([
                'variants' =>
                    'Configure pelo menos um atributo antes de gerar variantes.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Organizar os valores por atributo
        |--------------------------------------------------------------------------
        */

        $optionsByAttribute = [];

        foreach ($product->attributes as $attribute) {
            $values = $product
                ->variantOptions
                ->where(
                    'product_attribute_id',
                    $attribute->id
                )
                ->values();

            if ($values->isEmpty()) {
                return back()->withErrors([
                    'variants' =>
                        "O atributo {$attribute->name} não possui valores seleccionados.",
                ]);
            }

            $optionsByAttribute[] = $values;
        }

        $combinationCount = 1;

        foreach ($optionsByAttribute as $values) {
            $combinationCount *= $values->count();

            if (
                $combinationCount
                > self::MAX_VARIANT_COMBINATIONS
            ) {
                return back()->withErrors([
                    'variants' =>
                        'A configuração gera '
                        . $combinationCount
                        . ' combinações. O limite por geração é '
                        . self::MAX_VARIANT_COMBINATIONS
                        . '. Reduza os valores seleccionados antes de continuar.',
                ]);
            }
        }

        $combinations = $this->cartesianProduct(
            $optionsByAttribute
        );

        if (empty($combinations)) {
            return back()->withErrors([
                'variants' =>
                    'Não foi possível gerar combinações para este produto.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Indexar variantes existentes
        |--------------------------------------------------------------------------
        |
        | A chave é composta pelos IDs dos valores, ordenados.
        | Assim evitamos queries repetidas e não duplicamos combinações.
        |--------------------------------------------------------------------------
        */

        $existingCombinationKeys = $product
            ->variants
            ->mapWithKeys(
                function (Product $variant) {
                    $key = $variant
                        ->attributeValues
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->implode('-');

                    return [
                        $key => $variant->id,
                    ];
                }
            )
            ->all();

        $created = 0;
        $skipped = 0;

        $inventoryEnabled =
            $company->hasFeature('inventory');

        DB::transaction(
            function () use (
                $product,
                $company,
                $combinations,
                $inventoryEnabled,
                &$existingCombinationKeys,
                &$created,
                &$skipped
            ) {
                foreach ($combinations as $combination) {
                    $valueIds = collect($combination)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->sort()
                        ->values()
                        ->all();

                    $combinationKey = implode(
                        '-',
                        $valueIds
                    );

                    if (
                        isset(
                            $existingCombinationKeys[
                                $combinationKey
                            ]
                        )
                    ) {
                        $skipped++;

                        continue;
                    }

                    $suffix = collect($combination)
                        ->pluck('value')
                        ->implode(' / ');

                    $variant = Product::create([
                        'company_id' =>
                            $company->id,

                        'parent_id' =>
                            $product->id,

                        'product_type' =>
                            Product::TYPE_VARIANT,

                        'inventory_mode' =>
                            $inventoryEnabled
                                ? Product::INVENTORY_SIMPLE
                                : Product::INVENTORY_NONE,

                        'category_id' =>
                            $product->category_id,

                        'brand_id' =>
                            $product->brand_id,

                        'unit_id' =>
                            $product->unit_id,

                        'name' =>
                            $product->name
                            .' - '
                            .$suffix,

                        /*
                        |--------------------------------------------------------------------------
                        | SKU e barcode
                        |--------------------------------------------------------------------------
                        |
                        | Nesta fase ficam vazios. Na Fase 2.6 cada variante
                        | poderá receber SKU e barcode próprios.
                        |--------------------------------------------------------------------------
                        */

                        'sku' => null,
                        'barcode' => null,

                        'description' =>
                            $product->description,

                        'purchase_price' =>
                            $product->purchase_price,

                        'sale_price' =>
                            $product->sale_price,

                        'tax_rate' =>
                            $product->tax_rate,

                        'minimum_stock' => 0,

                        'track_stock' =>
                            $inventoryEnabled,

                        'active' =>
                            $product->active,
                    ]);

                    $variant
                        ->attributeValues()
                        ->sync($valueIds);

                    $existingCombinationKeys[
                        $combinationKey
                    ] = $variant->id;

                    $created++;
                }
            }
        );

        if ($created === 0) {
            return back()->with(
                'success',
                'Nenhuma variante nova foi criada. Todas as combinações já existem.'
            );
        }

        $message =
            "{$created} variante(s) criada(s) com sucesso.";

        if ($skipped > 0) {
            $message .=
                " {$skipped} combinação(ões) já existente(s) foi(ram) ignorada(s).";
        }

        return back()->with(
            'success',
            $message
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar variante individual
    |--------------------------------------------------------------------------
    */

    public function updateVariant(
        Request $request,
        Product $product,
        Product $variant
    ) {
        $company = app('activeCompany');

        abort_unless(
            (int) $product->company_id === (int) $company->id,
            403
        );

        abort_unless(
            (int) $variant->company_id === (int) $company->id,
            403
        );

        abort_unless(
            $product->isVariable(),
            422,
            'O produto principal não é um produto com variantes.'
        );

        abort_unless(
            $variant->isVariant()
            && (int) $variant->parent_id === (int) $product->id,
            404
        );

        abort_unless(
            $company->hasFeature('variants'),
            403,
            'A funcionalidade de variantes não está activa.'
        );

        $data = $request->validate([
            'sku' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique('products')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    )
                    ->ignore($variant->id),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:100',

                Rule::unique('products')
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'company_id',
                                $company->id
                            )
                    )
                    ->ignore($variant->id),
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'sale_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'inventory_mode' => [
                'nullable',
                Rule::in([
                    Product::INVENTORY_NONE,
                    Product::INVENTORY_SIMPLE,
                    Product::INVENTORY_BATCH,
                ]),
            ],

            'active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $inventoryMode = $this->validateInventoryMode(
            $data['inventory_mode'] ?? Product::INVENTORY_NONE,
            Product::TYPE_SIMPLE,
            $company,
            $variant
        );

        $this->validateInventoryModeTransition(
            $variant,
            $inventoryMode
        );

        $trackStock =
            $inventoryMode !== Product::INVENTORY_NONE;

        $variant->update([
            'sku' =>
                ! empty($data['sku'])
                    ? trim($data['sku'])
                    : null,

            'barcode' =>
                ! empty($data['barcode'])
                    ? trim($data['barcode'])
                    : null,

            'purchase_price' =>
                $data['purchase_price'] ?? 0,

            'sale_price' =>
                $data['sale_price'],

            'minimum_stock' =>
                $trackStock
                    ? ($data['minimum_stock'] ?? 0)
                    : 0,

            'track_stock' =>
                $trackStock,

            'inventory_mode' =>
                $inventoryMode,

            'active' =>
                $request->boolean('active'),
        ]);

        return back()->with(
            'success',
            "Variante {$variant->name} actualizada com sucesso."
        );
    }


    private function cartesianProduct(
        array $sets
    ): array {
        $result = [[]];
    
        foreach ($sets as $set) {
    
            $append = [];
    
            foreach ($result as $product) {
    
                foreach ($set as $item) {
    
                    $append[] = array_merge(
                        $product,
                        [$item]
                    );
                }
            }
    
            $result = $append;
        }
    
        return $result;
    }
}
