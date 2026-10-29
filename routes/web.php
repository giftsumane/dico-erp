<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BusinessContextController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\BusinessFeatureController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProductAttributeValueController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\ReturnBrowseController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventOperationController;

/*
|--------------------------------------------------------------------------
| Página inicial
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});


/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [LoginController::class, 'create']
    )->name('login');

    Route::post(
        '/login',
        [LoginController::class, 'store']
    )->name('login.store');

});


/*
|--------------------------------------------------------------------------
| Área autenticada
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'business.context',
])->group(function () {


    /*
    |--------------------------------------------------------------------------
    | ROTAS GERAIS
    |--------------------------------------------------------------------------
    |
    | Todos os utilizadores autenticados com contexto válido
    | podem aceder.
    |
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');


    Route::post(
        '/context',
        [BusinessContextController::class, 'update']
    )->name('business.context.update');


    Route::post(
        '/logout',
        [LoginController::class, 'destroy']
    )->name('logout');


    /*
    |--------------------------------------------------------------------------
    | OPERAÇÃO
    |--------------------------------------------------------------------------
    |
    | Owner
    | Admin
    | Manager
    | Cashier
    |
    */

    Route::middleware(
        'role:owner,admin,manager,cashier'
    )->group(function () {


        /*
        |--------------------------------------------------------------------------
        | POS
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:pos'
        )->group(function () {

            Route::get(
                '/pos',
                [PosController::class, 'index']
            )->name('pos.index');


            Route::post(
                '/pos/complete',
                [PosController::class, 'complete']
            )->name('pos.complete');


            Route::get(
                '/pos/sales/{sale}/receipt',
                [PosController::class, 'receipt']
            )->name('pos.receipt');

        });


        /*
        |--------------------------------------------------------------------------
        | Caixa
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:pos'
        )->group(function () {

            Route::get(
                '/cash-registers',
                [CashRegisterController::class, 'index']
            )->name('cash-registers.index');


            Route::post(
                '/cash-registers/open',
                [CashRegisterController::class, 'open']
            )->name('cash-registers.open');


            Route::post(
                '/cash-registers/{cashRegister}/movements',
                [CashRegisterController::class, 'movement']
            )->name('cash-registers.movements.store');


            Route::post(
                '/cash-registers/{cashRegister}/close',
                [CashRegisterController::class, 'close']
            )->name('cash-registers.close');

        });


        /*
        |--------------------------------------------------------------------------
        | Vendas
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:sales'
        )->group(function () {

            Route::get(
                '/sales',
                [SaleController::class, 'index']
            )->name('sales.index');


            Route::get(
                '/sales/{sale}',
                [SaleController::class, 'show']
            )->name('sales.show');

            Route::get('/returns', [ReturnBrowseController::class, 'index'])->name('returns.index');
            Route::get('/returns/{saleReturn}', [ReturnBrowseController::class, 'show'])->name('returns.show');


            /*
            |--------------------------------------------------------------------------
            | Devoluções
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/sales/{sale}/returns',
                [SaleReturnController::class, 'store']
            )->name('sales.returns.store');

        });


        /*
        |--------------------------------------------------------------------------
        | Clientes
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/customers',
            [CustomerController::class, 'index']
        )->name('customers.index');


        Route::get(
            '/customers/create',
            [CustomerController::class, 'create']
        )->name('customers.create');


        Route::post(
            '/customers',
            [CustomerController::class, 'store']
        )->name('customers.store');


        Route::get(
            '/customers/{customer}/edit',
            [CustomerController::class, 'edit']
        )->name('customers.edit');


        Route::put(
            '/customers/{customer}',
            [CustomerController::class, 'update']
        )->name('customers.update');


        /*
        |--------------------------------------------------------------------------
        | Contas a Receber
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:receivables'
        )->group(function () {

            Route::get(
                '/receivables',
                [ReceivableController::class, 'index']
            )->name('receivables.index');


            Route::get(
                '/receivables/{sale}',
                [ReceivableController::class, 'show']
            )->name('receivables.show');


            Route::post(
                '/receivables/{sale}/payments',
                [ReceivableController::class, 'payment']
            )->name('receivables.payments.store');

        });

    });


    /*
    |--------------------------------------------------------------------------
    | GESTÃO
    |--------------------------------------------------------------------------
    |
    | Owner
    | Admin
    | Manager
    |
    */

    Route::middleware(
        'role:owner,admin,manager'
    )->group(function () {


        /*
        |--------------------------------------------------------------------------
        | Produtos
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products',
            [ProductController::class, 'index']
        )->name('products.index');


        Route::get(
            '/products/create',
            [ProductController::class, 'create']
        )->name('products.create');


        Route::post(
            '/products',
            [ProductController::class, 'store']
        )->name('products.store');


        Route::get(
            '/products/{product}/edit',
            [ProductController::class, 'edit']
        )->name('products.edit');


        Route::put(
            '/products/{product}',
            [ProductController::class, 'update']
        )->name('products.update');

        Route::middleware(
            'feature:variants'
        )->group(function () {

            Route::post(
                '/products/{product}/generate-variants',
                [ProductController::class, 'generateVariants']
            )->name('products.generate-variants');


            Route::put(
                '/products/{product}/variants/{variant}',
                [ProductController::class, 'updateVariant']
            )->name('products.variants.update');

        });


        /*
        |--------------------------------------------------------------------------
        | Categorias
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/categories',
            [CategoryController::class, 'index']
        )->name('categories.index');


        Route::get(
            '/categories/create',
            [CategoryController::class, 'create']
        )->name('categories.create');


        Route::post(
            '/categories',
            [CategoryController::class, 'store']
        )->name('categories.store');


        /*
        |--------------------------------------------------------------------------
        | Marcas
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:brands'
        )->group(function () {

            Route::get(
                '/brands',
                [BrandController::class, 'index']
            )->name('brands.index');


            Route::get(
                '/brands/create',
                [BrandController::class, 'create']
            )->name('brands.create');


            Route::post(
                '/brands',
                [BrandController::class, 'store']
            )->name('brands.store');

        });


        /*
        |--------------------------------------------------------------------------
        | Unidades
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/units',
            [UnitController::class, 'index']
        )->name('units.index');


        Route::get(
            '/units/create',
            [UnitController::class, 'create']
        )->name('units.create');


        Route::post(
            '/units',
            [UnitController::class, 'store']
        )->name('units.store');


        /*
        |--------------------------------------------------------------------------
        | Atributos e Valores de Variantes
        |--------------------------------------------------------------------------
        |
        | Só disponíveis quando a empresa utiliza produtos
        | com variantes.
        |
        | Exemplos:
        |
        | Cor
        | ├── Preto
        | └── Branco
        |
        | Tamanho
        | ├── S
        | ├── M
        | └── L
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:variants'
        )->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Atributos
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/attributes',
                [ProductAttributeController::class, 'index']
            )->name('attributes.index');


            Route::get(
                '/attributes/create',
                [ProductAttributeController::class, 'create']
            )->name('attributes.create');


            Route::post(
                '/attributes',
                [ProductAttributeController::class, 'store']
            )->name('attributes.store');


            Route::get(
                '/attributes/{attribute}/edit',
                [ProductAttributeController::class, 'edit']
            )->name('attributes.edit');


            Route::put(
                '/attributes/{attribute}',
                [ProductAttributeController::class, 'update']
            )->name('attributes.update');


            /*
            |--------------------------------------------------------------------------
            | Valores dos Atributos
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/attributes/{attribute}/values',
                [
                    ProductAttributeValueController::class,
                    'store'
                ]
            )->name('attributes.values.store');


            Route::put(
                '/attributes/{attribute}/values/{value}',
                [
                    ProductAttributeValueController::class,
                    'update'
                ]
            )->name('attributes.values.update');


            Route::delete(
                '/attributes/{attribute}/values/{value}',
                [
                    ProductAttributeValueController::class,
                    'destroy'
                ]
            )->name('attributes.values.destroy');

        });


        /*
        |--------------------------------------------------------------------------
        | Stock
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:inventory'
        )->group(function () {

            Route::get(
                '/stocks',
                [StockController::class, 'index']
            )->name('stocks.index');


            Route::get(
                '/stocks/entry',
                [StockController::class, 'create']
            )->name('stocks.create');


            Route::post(
                '/stocks/entry',
                [StockController::class, 'store']
            )->name('stocks.store');


            Route::get(
                '/stocks/movements',
                [StockController::class, 'movements']
            )->name('stocks.movements');


            /*
            |--------------------------------------------------------------------------
            | Stock por lotes
            |--------------------------------------------------------------------------
            */

            Route::middleware(
                'feature:stock_batches'
            )->group(function () {

                Route::get(
                    '/stocks/{stock}/batches',
                    [StockController::class, 'batches']
                )->name('stocks.batches.index');


                Route::get(
                    '/stocks/{stock}/batches/{batch}/edit',
                    [StockController::class, 'editBatch']
                )->name('stocks.batches.edit');


                Route::put(
                    '/stocks/{stock}/batches/{batch}',
                    [StockController::class, 'updateBatch']
                )->name('stocks.batches.update');

            });


            /*
            |--------------------------------------------------------------------------
            | Ajuste de stock simples
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/stock/{stock}/edit',
                [StockController::class, 'edit']
            )->name('stocks.edit');


            Route::put(
                '/stock/{stock}',
                [StockController::class, 'update']
            )->name('stocks.update');

        });


        /*
        |--------------------------------------------------------------------------
        | Fornecedores
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:purchases'
        )->group(function () {

            Route::get(
                '/suppliers',
                [SupplierController::class, 'index']
            )->name('suppliers.index');


            Route::get(
                '/suppliers/create',
                [SupplierController::class, 'create']
            )->name('suppliers.create');


            Route::post(
                '/suppliers',
                [SupplierController::class, 'store']
            )->name('suppliers.store');


            Route::get(
                '/suppliers/{supplier}/edit',
                [SupplierController::class, 'edit']
            )->name('suppliers.edit');


            Route::put(
                '/suppliers/{supplier}',
                [SupplierController::class, 'update']
            )->name('suppliers.update');

        });


        /*
        |--------------------------------------------------------------------------
        | Compras
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:purchases'
        )->group(function () {

            Route::get(
                '/purchases',
                [PurchaseController::class, 'index']
            )->name('purchases.index');


            Route::get(
                '/purchases/create',
                [PurchaseController::class, 'create']
            )->name('purchases.create');


            Route::post(
                '/purchases',
                [PurchaseController::class, 'store']
            )->name('purchases.store');


            Route::get(
                '/purchases/{purchase}',
                [PurchaseController::class, 'show']
            )->name('purchases.show');


            Route::post(
                '/purchases/{purchase}/items',
                [PurchaseController::class, 'addItem']
            )->name('purchases.items.store');


            Route::delete(
                '/purchases/{purchase}/items/{item}',
                [PurchaseController::class, 'removeItem']
            )->name('purchases.items.destroy');


            Route::post(
                '/purchases/{purchase}/confirm',
                [PurchaseController::class, 'confirm']
            )->name('purchases.confirm');

        });


        /*
        |--------------------------------------------------------------------------
        | Relatórios
        |--------------------------------------------------------------------------
        */

        Route::middleware(
            'feature:sales'
        )->group(function () {

            Route::get(
                '/reports',
                [ReportController::class, 'index']
            )->name('reports.index');

        });

        /*
        |--------------------------------------------------------------------------
        | Eventos, bares e balcões
        |--------------------------------------------------------------------------
        */

        Route::middleware('feature:event_bars')->group(function () {
            Route::get('/events', [EventController::class, 'index'])
                ->name('events.index');
            Route::get('/events/create', [EventController::class, 'create'])
                ->name('events.create');
            Route::post('/events', [EventController::class, 'store'])
                ->name('events.store');
            Route::get('/events/{event}', [EventController::class, 'show'])
                ->name('events.show');
            Route::post('/events/{event}/bars', [EventOperationController::class, 'storeBar'])
                ->name('events.bars.store');
            Route::post('/events/{event}/bars/{bar}/counters', [EventOperationController::class, 'storeCounter'])
                ->name('events.counters.store');
            Route::post('/events/{event}/assignments', [EventOperationController::class, 'storeAssignment'])
                ->name('events.assignments.store');
        });

    });


    /*
    |--------------------------------------------------------------------------
    | ADMINISTRAÇÃO
    |--------------------------------------------------------------------------
    |
    | Owner
    | Admin
    |
    */

    Route::middleware(
        'role:owner,admin'
    )->group(function () {


        /*
        |--------------------------------------------------------------------------
        | Lojas
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/stores',
            [StoreController::class, 'index']
        )->name('stores.index');


        Route::get(
            '/stores/create',
            [StoreController::class, 'create']
        )->name('stores.create');


        Route::post(
            '/stores',
            [StoreController::class, 'store']
        )->name('stores.store');


        Route::get(
            '/stores/{store}/edit',
            [StoreController::class, 'edit']
        )->name('stores.edit');


        Route::put(
            '/stores/{store}',
            [StoreController::class, 'update']
        )->name('stores.update');


        /*
        |--------------------------------------------------------------------------
        | Utilizadores
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/users',
            [UserController::class, 'index']
        )->name('users.index');


        Route::get(
            '/users/create',
            [UserController::class, 'create']
        )->name('users.create');


        Route::post(
            '/users',
            [UserController::class, 'store']
        )->name('users.store');


        Route::get(
            '/users/{user}/edit',
            [UserController::class, 'edit']
        )->name('users.edit');


        Route::put(
            '/users/{user}',
            [UserController::class, 'update']
        )->name('users.update');


        /*
        |--------------------------------------------------------------------------
        | Configurações da Empresa
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/settings/company',
            [CompanySettingController::class, 'edit']
        )->name('settings.company.edit');


        Route::put(
            '/settings/company',
            [CompanySettingController::class, 'update']
        )->name('settings.company.update');


        /*
        |--------------------------------------------------------------------------
        | Perfil e Funcionalidades
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/settings/features',
            [BusinessFeatureController::class, 'edit']
        )->name('settings.features.edit');


        Route::put(
            '/settings/features',
            [BusinessFeatureController::class, 'update']
        )->name('settings.features.update');


        Route::post(
            '/settings/features/apply-profile',
            [BusinessFeatureController::class, 'applyProfile']
        )->name('settings.features.apply-profile');

    });

});
