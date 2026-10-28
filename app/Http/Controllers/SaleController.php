<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless($store, 403);

        $search = trim((string) $request->get('search'));
        $status = $request->get('status');
        $paymentMethod = $request->get('payment_method');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $sales = Sale::query()
            ->with([
                'customer',
                'user',
                'payments',
            ])
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)

            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        });
                });
            })

            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })

            ->when($paymentMethod, function ($query) use ($paymentMethod) {
                $query->whereHas('payments', function ($paymentQuery) use ($paymentMethod) {
                    $paymentQuery->where(
                        'method',
                        $paymentMethod
                    );
                });
            })

            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate(
                    'created_at',
                    '>=',
                    $dateFrom
                );
            })

            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate(
                    'created_at',
                    '<=',
                    $dateTo
                );
            })

            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Totais dos filtros actuais
        |--------------------------------------------------------------------------
        */

        $summaryQuery = Sale::query()
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->where('status', 'completed');

        if ($dateFrom) {
            $summaryQuery->whereDate(
                'created_at',
                '>=',
                $dateFrom
            );
        }

        if ($dateTo) {
            $summaryQuery->whereDate(
                'created_at',
                '<=',
                $dateTo
            );
        }

        if ($paymentMethod) {
            $summaryQuery->whereHas(
                'payments',
                function ($query) use ($paymentMethod) {
                    $query->where(
                        'method',
                        $paymentMethod
                    );
                }
            );
        }

        $salesCount = (clone $summaryQuery)->count();
        $salesTotal = (clone $summaryQuery)->sum('total');

        return view(
            'sales.index',
            compact(
                'sales',
                'search',
                'status',
                'paymentMethod',
                'dateFrom',
                'dateTo',
                'salesCount',
                'salesTotal'
            )
        );
    }

    public function show(Sale $sale)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless(
            $store
            && $sale->company_id === $company->id
            && $sale->store_id === $store->id,
            403
        );

        $sale->load([
            'customer',
            'user',
            'store',
            'cashRegister',
        
            'items.product.unit',
            'items.stockBatches',
        
            'items.returnItems' => function ($query) {
                $query->whereHas(
                    'saleReturn',
                    function ($returnQuery) {
                        $returnQuery->where(
                            'status',
                            \App\Models\SaleReturn::STATUS_COMPLETED
                        );
                    }
                );
            },
        
            'returns' => function ($query) {
                $query
                    ->with([
                        'user',
                        'items.product.unit',
                        'items.stockBatches',
                    ])
                    ->latest('returned_at');
            },
        
            'payments',
        ]);

        return view(
            'sales.show',
            compact('sale')
        );
    }
}