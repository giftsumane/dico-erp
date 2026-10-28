<?php

namespace App\Http\Controllers;

use App\Models\SaleReturn;
use Illuminate\Http\Request;

class ReturnBrowseController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:completed,cancelled'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = SaleReturn::query()
            ->with(['sale.customer', 'user', 'store'])
            ->where('company_id', $company->id);

        if ($store) {
            $query->where('store_id', $store->id);
        }

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('number', 'like', "%{$term}%")
                    ->orWhereHas('sale', function ($saleQuery) use ($term) {
                        $saleQuery->where('number', 'like', "%{$term}%")
                            ->orWhereHas('customer', function ($customerQuery) use ($term) {
                                $customerQuery->where('name', 'like', "%{$term}%");
                            });
                    });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('returned_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('returned_at', '<=', $filters['to']);
        }

        $returns = $query->orderByDesc('returned_at')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('returns.index', compact('returns'));
    }

    public function show(SaleReturn $saleReturn)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless((int) $saleReturn->company_id === (int) $company->id, 404);
        if ($store) {
            abort_unless((int) $saleReturn->store_id === (int) $store->id, 404);
        }

        $saleReturn->load([
            'sale.customer', 'user', 'store', 'cashRegister',
            'items.product.unit', 'items.stockBatches',
        ]);

        return view('returns.show', compact('saleReturn'));
    }
}
