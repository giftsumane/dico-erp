<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $activeCompany = app('activeCompany');
        $activeStore = app('activeStore');
        $activeRole = app('activeRole');

        /*
        |--------------------------------------------------------------------------
        | Métricas administrativas
        |--------------------------------------------------------------------------
        */

        $companiesCount = $user->companies()
            ->wherePivot(
                'status',
                'active'
            )
            ->count();

        $storesCount = $activeCompany
            ->stores()
            ->where(
                'status',
                'active'
            )
            ->count();

        $usersCount = User::query()
            ->whereHas(
                'companies',
                function ($query) use ($activeCompany) {
                    $query
                        ->where(
                            'companies.id',
                            $activeCompany->id
                        )
                        ->where(
                            'company_user.status',
                            'active'
                        );
                }
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Valores padrão
        |--------------------------------------------------------------------------
        */

        $salesToday = 0;
        $revenueToday = 0;
        $paymentsReceivedToday = 0;
        $paymentsReceivedTodayCount = 0;
        $averageTicket = 0;

        $lowStockCount = 0;

        $receivablesTotal = 0;
        $receivablesCount = 0;

        $overdueTotal = 0;
        $overdueCount = 0;

        $dueTodayTotal = 0;
        $dueTodayCount = 0;

        /*
        |--------------------------------------------------------------------------
        | Métricas da loja
        |--------------------------------------------------------------------------
        */

        if ($activeStore) {

            /*
            |--------------------------------------------------------------------------
            | Vendas de hoje
            |--------------------------------------------------------------------------
            */

            $todaySalesQuery = Sale::query()
                ->where(
                    'company_id',
                    $activeCompany->id
                )
                ->where(
                    'store_id',
                    $activeStore->id
                )
                ->where(
                    'status',
                    'completed'
                )
                ->whereDate(
                    'completed_at',
                    today()
                );

            $salesToday = (
                clone $todaySalesQuery
            )->count();

            /*
             * Valor comercial vendido hoje.
             */
            $revenueToday = (float) (
                clone $todaySalesQuery
            )->sum(
                'total'
            );

            $averageTicket =
                $salesToday > 0
                    ? $revenueToday / $salesToday
                    : 0;

            /*
            |--------------------------------------------------------------------------
            | Valor realmente recebido hoje
            |--------------------------------------------------------------------------
            |
            | Inclui:
            |
            | - vendas pagas hoje;
            | - depósitos;
            | - prestações recebidas hoje de vendas antigas.
            |--------------------------------------------------------------------------
            */

            $todayPaymentsQuery = Payment::query()
                ->join(
                    'sales',
                    'sales.id',
                    '=',
                    'payments.sale_id'
                )
                ->where(
                    'sales.company_id',
                    $activeCompany->id
                )
                ->where(
                    'sales.store_id',
                    $activeStore->id
                )
                ->where(
                    'sales.status',
                    'completed'
                )
                ->whereDate(
                    \DB::raw(
                        'COALESCE(
                            payments.paid_at,
                            payments.created_at
                        )'
                    ),
                    today()
                );

            $paymentsReceivedToday = (float) (
                clone $todayPaymentsQuery
            )->sum(
                'payments.amount'
            );

            $paymentsReceivedTodayCount = (
                clone $todayPaymentsQuery
            )->count();

            /*
            |--------------------------------------------------------------------------
            | Contas a receber - posição actual
            |--------------------------------------------------------------------------
            */

            $receivablesQuery = Sale::query()
                ->where(
                    'company_id',
                    $activeCompany->id
                )
                ->where(
                    'store_id',
                    $activeStore->id
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

            $receivablesTotal = (float) (
                clone $receivablesQuery
            )->sum(
                'amount_due'
            );

            $receivablesCount = (
                clone $receivablesQuery
            )->count();

            /*
            |--------------------------------------------------------------------------
            | Vencidas
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
            | Vencem hoje
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
        }

        /*
        |--------------------------------------------------------------------------
        | Stock baixo
        |--------------------------------------------------------------------------
        */

        if (
            $activeStore
            && in_array(
                $activeRole,
                [
                    'owner',
                    'admin',
                    'manager',
                ],
                true
            )
        ) {
            $lowStockCount = Stock::query()
                ->where(
                    'stocks.company_id',
                    $activeCompany->id
                )
                ->where(
                    'stocks.store_id',
                    $activeStore->id
                )
                ->join(
                    'products',
                    'products.id',
                    '=',
                    'stocks.product_id'
                )
                ->where(
                    'products.track_stock',
                    true
                )
                ->where(
                    'products.active',
                    true
                )
                ->whereColumn(
                    'stocks.quantity',
                    '<=',
                    'products.minimum_stock'
                )
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Caixa actual do utilizador
        |--------------------------------------------------------------------------
        */

        $currentRegister = null;

        $cashSales = 0;
        $cashSalesCount = 0;
        $cashExpectedBalance = 0;
        $manualIn = 0;
        $manualOut = 0;

        if ($activeStore) {

            $currentRegister = CashRegister::query()
                ->where(
                    'company_id',
                    $activeCompany->id
                )
                ->where(
                    'store_id',
                    $activeStore->id
                )
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'status',
                    'open'
                )
                ->latest('id')
                ->first();

            if ($currentRegister) {

                /*
                |--------------------------------------------------------------------------
                | Cash realmente recebido neste caixa
                |--------------------------------------------------------------------------
                |
                | Usamos payments.cash_register_id directamente.
                |--------------------------------------------------------------------------
                */

                $cashSales = (float) Payment::query()
                    ->where(
                        'cash_register_id',
                        $currentRegister->id
                    )
                    ->where(
                        'method',
                        'cash'
                    )
                    ->sum(
                        'amount'
                    );

                /*
                |--------------------------------------------------------------------------
                | Vendas originalmente feitas neste caixa
                |--------------------------------------------------------------------------
                */

                $cashSalesCount = Sale::query()
                    ->where(
                        'cash_register_id',
                        $currentRegister->id
                    )
                    ->where(
                        'status',
                        'completed'
                    )
                    ->count();

                /*
                |--------------------------------------------------------------------------
                | Movimentos manuais
                |--------------------------------------------------------------------------
                */

                $manualIn = (float) $currentRegister
                    ->movements()
                    ->where(
                        'type',
                        'in'
                    )
                    ->sum(
                        'amount'
                    );

                $manualOut = (float) $currentRegister
                    ->movements()
                    ->where(
                        'type',
                        'out'
                    )
                    ->sum(
                        'amount'
                    );

                /*
                |--------------------------------------------------------------------------
                | Saldo esperado
                |--------------------------------------------------------------------------
                */

                $cashExpectedBalance =
                    (float) $currentRegister->opening_amount
                    + $cashSales
                    + $manualIn
                    - $manualOut;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Últimas vendas
        |--------------------------------------------------------------------------
        */

        $recentSales = collect();

        if ($activeStore) {

            $recentSales = Sale::query()
                ->with([
                    'customer',
                    'payments',
                    'user',
                ])
                ->where(
                    'company_id',
                    $activeCompany->id
                )
                ->where(
                    'store_id',
                    $activeStore->id
                )
                ->where(
                    'status',
                    'completed'
                )
                ->latest(
                    'completed_at'
                )
                ->limit(6)
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Dívidas vencidas recentes
        |--------------------------------------------------------------------------
        */

        $overdueSales = collect();

        if (
            $activeStore
            && in_array(
                $activeRole,
                [
                    'owner',
                    'admin',
                    'manager',
                    'cashier',
                ],
                true
            )
        ) {
            $overdueSales = Sale::query()
                ->with([
                    'customer',
                ])
                ->where(
                    'company_id',
                    $activeCompany->id
                )
                ->where(
                    'store_id',
                    $activeStore->id
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
                ->limit(5)
                ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Métricas finais
        |--------------------------------------------------------------------------
        */

        $metrics = [
            /*
             * Administrativas
             */
            'companies' =>
                $companiesCount,

            'stores' =>
                $storesCount,

            'users' =>
                $usersCount,

            /*
             * Comercial
             */
            'sales_today' =>
                $salesToday,

            'revenue_today' =>
                $revenueToday,

            'payments_received_today' =>
                $paymentsReceivedToday,

            'payments_received_today_count' =>
                $paymentsReceivedTodayCount,

            'average_ticket' =>
                $averageTicket,

            /*
             * Stock
             */
            'low_stock' =>
                $lowStockCount,

            /*
             * Contas a receber
             */
            'receivables_total' =>
                $receivablesTotal,

            'receivables_count' =>
                $receivablesCount,

            'overdue_total' =>
                $overdueTotal,

            'overdue_count' =>
                $overdueCount,

            'due_today_total' =>
                $dueTodayTotal,

            'due_today_count' =>
                $dueTodayCount,

            /*
             * Caixa
             */
            'cash_sales' =>
                $cashSales,

            'cash_sales_count' =>
                $cashSalesCount,

            'cash_expected_balance' =>
                $cashExpectedBalance,

            'manual_in' =>
                $manualIn,

            'manual_out' =>
                $manualOut,
        ];

        return view(
            'dashboard.index',
            compact(
                'user',
                'metrics',
                'activeCompany',
                'activeStore',
                'activeRole',
                'currentRegister',
                'recentSales',
                'overdueSales'
            )
        );
    }
}