<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless(
            $store,
            403
        );

        /*
        |--------------------------------------------------------------------------
        | Filtros
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->get('search')
        );

        $status = $request->get(
            'status',
            'open'
        );

        if (
            ! in_array(
                $status,
                [
                    'open',
                    'overdue',
                    'paid',
                ],
                true
            )
        ) {
            $status = 'open';
        }

        /*
        |--------------------------------------------------------------------------
        | Lista
        |--------------------------------------------------------------------------
        */

        $receivables = Sale::query()
            ->with([
                'customer',
                'user',
                'store',
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

            /*
            |--------------------------------------------------------------------------
            | Estado: em aberto
            |--------------------------------------------------------------------------
            */

            ->when(
                $status === 'open',
                function ($query) {
                    $query
                        ->whereIn(
                            'payment_status',
                            [
                                'partial',
                                'unpaid',
                            ]
                        )
                        ->where(
                            'amount_due',
                            '>',
                            0
                        );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Estado: vencidas
            |--------------------------------------------------------------------------
            */

            ->when(
                $status === 'overdue',
                function ($query) {
                    $query
                        ->whereIn(
                            'payment_status',
                            [
                                'partial',
                                'unpaid',
                            ]
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
                        );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Estado: liquidadas
            |--------------------------------------------------------------------------
            */

            ->when(
                $status === 'paid',
                function ($query) {
                    $query
                        ->where(
                            'payment_status',
                            'paid'
                        )
                        ->where(
                            'amount_due',
                            '<=',
                            0
                        )
                        ->whereNotNull(
                            'customer_id'
                        );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Pesquisa
            |--------------------------------------------------------------------------
            */

            ->when(
                $search,
                function ($query) use ($search) {
                    $query->where(
                        function ($subQuery) use ($search) {
                            $subQuery
                                ->where(
                                    'number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'customer',
                                    function ($customerQuery) use ($search) {
                                        $customerQuery
                                            ->where(
                                                'name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'nuit',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'phone',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'email',
                                                'like',
                                                "%{$search}%"
                                            );
                                    }
                                );
                        }
                    );
                }
            )

            /*
            |--------------------------------------------------------------------------
            | Ordenação
            |--------------------------------------------------------------------------
            */

            ->when(
                $status !== 'paid',
                function ($query) {
                    $query
                        ->orderByRaw(
                            'due_date IS NULL, due_date ASC'
                        )
                        ->orderByDesc('id');
                },
                function ($query) {
                    $query->orderByDesc(
                        'completed_at'
                    );
                }
            )
            ->paginate(25)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Base para métricas
        |--------------------------------------------------------------------------
        */

        $summaryQuery = Sale::query()
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
            );

        /*
        |--------------------------------------------------------------------------
        | Total por receber
        |--------------------------------------------------------------------------
        */

        $totalDue = (float) (
            clone $summaryQuery
        )
            ->where(
                'amount_due',
                '>',
                0
            )
            ->sum(
                'amount_due'
            );

        /*
        |--------------------------------------------------------------------------
        | Total vencido
        |--------------------------------------------------------------------------
        */

        $totalOverdue = (float) (
            clone $summaryQuery
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
            ->sum(
                'amount_due'
            );

        /*
        |--------------------------------------------------------------------------
        | Quantidade em aberto
        |--------------------------------------------------------------------------
        */

        $receivablesCount = (
            clone $summaryQuery
        )
            ->where(
                'amount_due',
                '>',
                0
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Quantidade vencida
        |--------------------------------------------------------------------------
        */

        $overdueCount = (
            clone $summaryQuery
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
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Quantidade liquidada
        |--------------------------------------------------------------------------
        */

        $paidCount = (
            clone $summaryQuery
        )
            ->where(
                'payment_status',
                'paid'
            )
            ->where(
                'amount_due',
                '<=',
                0
            )
            ->whereNotNull(
                'customer_id'
            )
            ->count();

        return view(
            'receivables.index',
            compact(
                'receivables',
                'search',
                'status',
                'totalDue',
                'totalOverdue',
                'receivablesCount',
                'overdueCount',
                'paidCount'
            )
        );
    }


    public function show(Sale $sale)
    {
        $this->ensureAccess(
            $sale
        );

        $sale->load([
            'customer',
            'items.product.unit',
            'payments.user',
            'payments.cashRegister',
            'store',
            'user',
        ]);

        return view(
            'receivables.show',
            compact(
                'sale'
            )
        );
    }


    public function payment(
        Request $request,
        Sale $sale
    ) {
        $this->ensureAccess(
            $sale
        );

        /*
        |--------------------------------------------------------------------------
        | Validar estado
        |--------------------------------------------------------------------------
        */

        if (
            (float) $sale->amount_due
            <= 0
        ) {
            throw ValidationException::withMessages([
                'amount' =>
                    'Esta venda já está liquidada.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validação
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'method' => [
                'required',
                'in:cash,mpesa,emola,card,bank_transfer,other',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $amount = round(
            (float) $data['amount'],
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Não ultrapassar saldo
        |--------------------------------------------------------------------------
        */

        if (
            $amount
            > (float) $sale->amount_due
        ) {
            throw ValidationException::withMessages([
                'amount' =>
                    'O pagamento não pode ser superior ao saldo em dívida.',
            ]);
        }

        $company =
            app('activeCompany');

        $store =
            app('activeStore');

        /*
        |--------------------------------------------------------------------------
        | Caixa actual
        |--------------------------------------------------------------------------
        |
        | Se existir um caixa aberto, associamos o pagamento.
        |
        | Para dinheiro, caixa aberto é obrigatório.
        |--------------------------------------------------------------------------
        */

        $cashRegister = CashRegister::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->where(
                'user_id',
                auth()->id()
            )
            ->where(
                'status',
                'open'
            )
            ->latest('id')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Dinheiro exige caixa
        |--------------------------------------------------------------------------
        */

        if (
            $data['method']
            === 'cash'
            && ! $cashRegister
        ) {
            throw ValidationException::withMessages([
                'method' =>
                    'É necessário ter um caixa aberto para receber dinheiro.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Transacção
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $sale,
                $data,
                $amount,
                $cashRegister
            ) {
                /*
                |--------------------------------------------------------------------------
                | Bloquear venda
                |--------------------------------------------------------------------------
                */

                $lockedSale = Sale::query()
                    ->where(
                        'id',
                        $sale->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Confirmar saldo novamente
                |--------------------------------------------------------------------------
                */

                if (
                    $amount
                    > (float) $lockedSale
                        ->amount_due
                ) {
                    throw ValidationException::withMessages([
                        'amount' =>
                            'O valor é superior ao saldo actual.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Criar pagamento
                |--------------------------------------------------------------------------
                */

                Payment::create([
                    'sale_id' =>
                        $lockedSale->id,

                    'cash_register_id' =>
                        $cashRegister?->id,

                    'user_id' =>
                        auth()->id(),

                    'method' =>
                        $data['method'],

                    'amount' =>
                        $amount,

                    'reference' =>
                        $data['reference']
                        ?? null,

                    'paid_at' =>
                        now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Novo total pago
                |--------------------------------------------------------------------------
                */

                $paidTotal = round(
                    (float) $lockedSale
                        ->paid_total
                    + $amount,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Novo saldo
                |--------------------------------------------------------------------------
                */

                $amountDue = max(
                    0,
                    round(
                        (float) $lockedSale
                            ->total
                        - $paidTotal,
                        2
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Novo estado
                |--------------------------------------------------------------------------
                */

                $paymentStatus =
                    $amountDue <= 0
                        ? 'paid'
                        : 'partial';

                /*
                |--------------------------------------------------------------------------
                | Actualizar venda
                |--------------------------------------------------------------------------
                */

                $lockedSale->update([
                    'paid_total' =>
                        $paidTotal,

                    'amount_due' =>
                        $amountDue,

                    'payment_status' =>
                        $paymentStatus,

                    /*
                     * Depois de liquidada,
                     * já não há vencimento pendente.
                     */
                    'due_date' =>
                        $amountDue <= 0
                            ? null
                            : $lockedSale->due_date,
                ]);
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Actualizar model local
        |--------------------------------------------------------------------------
        */

        $sale->refresh();

        /*
        |--------------------------------------------------------------------------
        | Mensagem
        |--------------------------------------------------------------------------
        */

        $message =
            $sale->payment_status
            === 'paid'
                ? 'Pagamento registado. A venda foi totalmente liquidada.'
                : 'Pagamento registado com sucesso.';

        return redirect()
            ->route(
                'receivables.show',
                $sale
            )
            ->with(
                'success',
                $message
            );
    }


    private function ensureAccess(
        Sale $sale
    ): void {
        $company =
            app('activeCompany');

        $store =
            app('activeStore');

        abort_unless(
            $store
            && $sale->company_id
                === $company->id
            && $sale->store_id
                === $store->id,
            403
        );
    }
}