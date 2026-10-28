<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless($store, 403);

        /*
        |--------------------------------------------------------------------------
        | Período
        |--------------------------------------------------------------------------
        */

        $period = $request->get(
            'period',
            'today'
        );

        $dateFrom = null;
        $dateTo = null;

        switch ($period) {

            case '7d':

                $dateFrom = now()
                    ->subDays(6)
                    ->startOfDay();

                $dateTo = now()
                    ->endOfDay();

                break;


            case '30d':

                $dateFrom = now()
                    ->subDays(29)
                    ->startOfDay();

                $dateTo = now()
                    ->endOfDay();

                break;


            case 'custom':

                $request->validate([
                    'date_from' => [
                        'required',
                        'date',
                    ],

                    'date_to' => [
                        'required',
                        'date',
                        'after_or_equal:date_from',
                    ],
                ]);

                $dateFrom = Carbon::parse(
                    $request->date_from
                )->startOfDay();

                $dateTo = Carbon::parse(
                    $request->date_to
                )->endOfDay();

                break;


            case 'today':
            default:

                $period = 'today';

                $dateFrom = now()
                    ->startOfDay();

                $dateTo = now()
                    ->endOfDay();

                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Query base de vendas do período
        |--------------------------------------------------------------------------
        */

        $salesQuery = Sale::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'status',
                'completed'
            )
            ->whereNotNull(
                'completed_at'
            )
            ->whereBetween(
                'completed_at',
                [
                    $dateFrom,
                    $dateTo,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Métricas comerciais
        |--------------------------------------------------------------------------
        */

        $salesCount = (
            clone $salesQuery
        )->count();

        $revenue = (float) (
            clone $salesQuery
        )->sum(
            'total'
        );

        $averageTicket =
            $salesCount > 0
                ? $revenue / $salesCount
                : 0;

        $discountsTotal = (float) (
            clone $salesQuery
        )->sum(
            'discount_total'
        );

        $taxTotal = (float) (
            clone $salesQuery
        )->sum(
            'tax_total'
        );

        /*
        |--------------------------------------------------------------------------
        | Produtos vendidos no período
        |--------------------------------------------------------------------------
        */

        $productsSold = (float) SaleItem::query()
            ->join(
                'sales',
                'sales.id',
                '=',
                'sale_items.sale_id'
            )
            ->where(
                'sales.company_id',
                $company->id
            )
            ->where(
                'sales.store_id',
                $store->id
            )
            ->where(
                'sales.status',
                'completed'
            )
            ->whereBetween(
                'sales.completed_at',
                [
                    $dateFrom,
                    $dateTo,
                ]
            )
            ->sum(
                'sale_items.quantity'
            );

        /*
        |--------------------------------------------------------------------------
        | Pagamentos efectivamente recebidos no período
        |--------------------------------------------------------------------------
        |
        | Aqui usamos payments.paid_at.
        |
        | Isto permite que uma prestação recebida hoje,
        | referente a uma venda antiga, apareça no relatório de hoje.
        |--------------------------------------------------------------------------
        */

        $paymentsQuery = Payment::query()
            ->join(
                'sales',
                'sales.id',
                '=',
                'payments.sale_id'
            )
            ->where(
                'sales.company_id',
                $company->id
            )
            ->where(
                'sales.store_id',
                $store->id
            )
            ->where(
                'sales.status',
                'completed'
            )
            ->whereBetween(
                DB::raw(
                    'COALESCE(
                        payments.paid_at,
                        payments.created_at
                    )'
                ),
                [
                    $dateFrom,
                    $dateTo,
                ]
            );

        $paymentsReceived = (float) (
            clone $paymentsQuery
        )->sum(
            'payments.amount'
        );

        $paymentsCount = (
            clone $paymentsQuery
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Métodos de pagamento
        |--------------------------------------------------------------------------
        */

        $paymentsByMethod = (
            clone $paymentsQuery
        )
            ->select(
                'payments.method',
                DB::raw(
                    'COUNT(payments.id) as transactions'
                ),
                DB::raw(
                    'SUM(payments.amount) as total'
                )
            )
            ->groupBy(
                'payments.method'
            )
            ->orderByDesc(
                'total'
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | CONTAS A RECEBER - POSIÇÃO ACTUAL
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | Esta secção NÃO respeita o filtro do período.
        |
        | Uma dívida criada há semanas ou meses continua a aparecer
        | enquanto possuir saldo pendente.
        |--------------------------------------------------------------------------
        */

        $receivablesQuery = Sale::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'status',
                'completed'
            )
            ->where(
                'amount_due',
                '>',
                0
            );

        /*
        |--------------------------------------------------------------------------
        | Total actualmente por receber
        |--------------------------------------------------------------------------
        */

        $receivablesTotal = (float) (
            clone $receivablesQuery
        )->sum(
            'amount_due'
        );

        /*
        |--------------------------------------------------------------------------
        | Número de contas abertas
        |--------------------------------------------------------------------------
        */

        $receivablesCount = (
            clone $receivablesQuery
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Total vencido
        |--------------------------------------------------------------------------
        */

        $overdueTotal = (float) (
            clone $receivablesQuery
        )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->sum(
                'amount_due'
            );

        /*
        |--------------------------------------------------------------------------
        | Número de contas vencidas
        |--------------------------------------------------------------------------
        */

        $overdueCount = (
            clone $receivablesQuery
        )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Contas que vencem hoje
        |--------------------------------------------------------------------------
        */

        $dueTodayTotal = (float) (
            clone $receivablesQuery
        )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                today()
            )
            ->sum(
                'amount_due'
            );

        $dueTodayCount = (
            clone $receivablesQuery
        )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                today()
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Contas a vencer futuramente
        |--------------------------------------------------------------------------
        */

        $futureDueTotal = (float) (
            clone $receivablesQuery
        )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '>',
                today()
            )
            ->sum(
                'amount_due'
            );

        $futureDueCount = (
            clone $receivablesQuery
        )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '>',
                today()
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Contas sem data de vencimento
        |--------------------------------------------------------------------------
        */

        $withoutDueDateTotal = (float) (
            clone $receivablesQuery
        )
            ->whereNull(
                'due_date'
            )
            ->sum(
                'amount_due'
            );

        $withoutDueDateCount = (
            clone $receivablesQuery
        )
            ->whereNull(
                'due_date'
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Top clientes devedores - posição actual
        |--------------------------------------------------------------------------
        */

        $topDebtors = Sale::query()
            ->select(
                'customer_id',
                DB::raw(
                    'COUNT(id) as debts_count'
                ),
                DB::raw(
                    'SUM(amount_due) as total_due'
                ),
                DB::raw(
                    'MIN(due_date) as next_due_date'
                )
            )
            ->with(
                'customer'
            )
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'status',
                'completed'
            )
            ->whereNotNull(
                'customer_id'
            )
            ->where(
                'amount_due',
                '>',
                0
            )
            ->groupBy(
                'customer_id'
            )
            ->orderByDesc(
                'total_due'
            )
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Próximas contas a vencer
        |--------------------------------------------------------------------------
        */

        $upcomingReceivables = Sale::query()
            ->with([
                'customer',
            ])
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'status',
                'completed'
            )
            ->where(
                'amount_due',
                '>',
                0
            )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '>=',
                today()
            )
            ->orderBy(
                'due_date'
            )
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dívidas vencidas mais antigas
        |--------------------------------------------------------------------------
        */

        $overdueReceivables = Sale::query()
            ->with([
                'customer',
            ])
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'status',
                'completed'
            )
            ->where(
                'amount_due',
                '>',
                0
            )
            ->whereNotNull(
                'due_date'
            )
            ->whereDate(
                'due_date',
                '<',
                today()
            )
            ->orderBy(
                'due_date'
            )
            ->limit(8)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Vendas por dia
        |--------------------------------------------------------------------------
        */

        $dailySalesRaw = Sale::query()
            ->selectRaw(
                'DATE(completed_at) as sale_date'
            )
            ->selectRaw(
                'COUNT(*) as sales_count'
            )
            ->selectRaw(
                'SUM(total) as total'
            )
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'status',
                'completed'
            )
            ->whereBetween(
                'completed_at',
                [
                    $dateFrom,
                    $dateTo,
                ]
            )
            ->groupBy(
                DB::raw(
                    'DATE(completed_at)'
                )
            )
            ->orderBy(
                'sale_date'
            )
            ->get()
            ->keyBy(
                'sale_date'
            );

        /*
        |--------------------------------------------------------------------------
        | Montar período completo
        |--------------------------------------------------------------------------
        */

        $dailySales = collect();

        $cursor = $dateFrom
            ->copy()
            ->startOfDay();

        while (
            $cursor->lte(
                $dateTo
                    ->copy()
                    ->startOfDay()
            )
        ) {
            $date =
                $cursor->format(
                    'Y-m-d'
                );

            $row =
                $dailySalesRaw->get(
                    $date
                );

            $dailySales->push([
                'date' =>
                    $date,

                'label' =>
                    $cursor->format(
                        'd/m'
                    ),

                'sales_count' =>
                    $row
                        ? (int) $row->sales_count
                        : 0,

                'total' =>
                    $row
                        ? (float) $row->total
                        : 0,
            ]);

            $cursor->addDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Produtos mais vendidos
        |--------------------------------------------------------------------------
        */

        $topProducts = SaleItem::query()
            ->select(
                'sale_items.product_id',
                DB::raw(
                    'SUM(sale_items.quantity) as total_quantity'
                ),
                DB::raw(
                    'SUM(sale_items.total) as total_revenue'
                )
            )
            ->join(
                'sales',
                'sales.id',
                '=',
                'sale_items.sale_id'
            )
            ->with(
                'product.unit'
            )
            ->where(
                'sales.company_id',
                $company->id
            )
            ->where(
                'sales.store_id',
                $store->id
            )
            ->where(
                'sales.status',
                'completed'
            )
            ->whereBetween(
                'sales.completed_at',
                [
                    $dateFrom,
                    $dateTo,
                ]
            )
            ->groupBy(
                'sale_items.product_id'
            )
            ->orderByDesc(
                'total_quantity'
            )
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Texto do período
        |--------------------------------------------------------------------------
        */

        $periodLabel = match (
            $period
        ) {
            'today' =>
                'Hoje',

            '7d' =>
                'Últimos 7 dias',

            '30d' =>
                'Últimos 30 dias',

            'custom' =>
                $dateFrom->format(
                    'd/m/Y'
                )
                . ' a '
                . $dateTo->format(
                    'd/m/Y'
                ),

            default =>
                'Hoje',
        };

        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'reports.index',
            compact(
                'period',
                'periodLabel',
                'dateFrom',
                'dateTo',

                'salesCount',
                'revenue',
                'averageTicket',
                'productsSold',
                'discountsTotal',
                'taxTotal',

                'paymentsReceived',
                'paymentsCount',
                'paymentsByMethod',

                'receivablesTotal',
                'receivablesCount',

                'overdueTotal',
                'overdueCount',

                'dueTodayTotal',
                'dueTodayCount',

                'futureDueTotal',
                'futureDueCount',

                'withoutDueDateTotal',
                'withoutDueDateCount',

                'topDebtors',
                'upcomingReceivables',
                'overdueReceivables',

                'dailySales',
                'topProducts'
            )
        );
    }
}