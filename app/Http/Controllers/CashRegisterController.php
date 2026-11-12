<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterController extends Controller
{
    public function index()
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless($store, 403);

        $currentRegister = CashRegister::query()
            ->with([
                'user',
                'movements.user',
            ])
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->where('user_id', auth()->id())
            ->where('status', 'open')
            ->latest('id')
            ->first();

        $currentSummary = null;

        if ($currentRegister) {

            /*
            |--------------------------------------------------------------------------
            | Vendas em dinheiro
            |--------------------------------------------------------------------------
            */

            $cashSales = Payment::query()
            ->where(
                'cash_register_id',
                $currentRegister->id
            )
            ->where(
                'method',
                'cash'
            )
            ->sum('amount');
            
                $salesCount = $currentRegister
                ->sales()
                ->where('status', 'completed')
                ->count();


            /*
            |--------------------------------------------------------------------------
            | Entradas manuais
            |--------------------------------------------------------------------------
            */

            $manualIn = CashMovement::query()
                ->where(
                    'cash_register_id',
                    $currentRegister->id
                )
                ->where('type', 'in')
                ->sum('amount');


            /*
            |--------------------------------------------------------------------------
            | Saídas manuais
            |--------------------------------------------------------------------------
            */

            $manualOut = CashMovement::query()
                ->where(
                    'cash_register_id',
                    $currentRegister->id
                )
                ->where('type', 'out')
                ->sum('amount');


            /*
            |--------------------------------------------------------------------------
            | Saldo esperado neste momento
            |--------------------------------------------------------------------------
            */

            $expectedBalance =
                (float) $currentRegister->opening_amount
                + (float) $cashSales
                + (float) $manualIn
                - (float) $manualOut;


            $currentSummary = [
                'opening_amount' =>
                    (float) $currentRegister->opening_amount,

                'cash_sales' =>
                    (float) $cashSales,

                'manual_in' =>
                    (float) $manualIn,

                'manual_out' =>
                    (float) $manualOut,

                'expected_balance' =>
                    $expectedBalance,

                'sales_count' => $salesCount,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Histórico
        |--------------------------------------------------------------------------
        */

        $registers = CashRegister::query()
            ->with('user')
            ->where('company_id', $company->id)
            ->where('store_id', $store->id)
            ->latest('opened_at')
            ->paginate(20);


        return view(
            'cash-registers.index',
            compact(
                'currentRegister',
                'currentSummary',
                'registers'
            )
        );
    }

    public function open(Request $request, AuditService $auditService)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless($store, 403);

        $data = $request->validate([
            'opening_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'opening_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use ($company, $store, $data, $auditService) {
            // Serializa aberturas do mesmo operador para impedir dois
            // caixas abertos por submissões simultâneas.
            User::query()
                ->whereKey(auth()->id())
                ->lockForUpdate()
                ->firstOrFail();

            $alreadyOpen = CashRegister::query()
                ->where('company_id', $company->id)
                ->where('store_id', $store->id)
                ->where('user_id', auth()->id())
                ->where('status', 'open')
                ->exists();

            if ($alreadyOpen) {
                throw ValidationException::withMessages([
                    'opening_amount' => 'Já tens um caixa aberto nesta loja.',
                ]);
            }

            $cashRegister = CashRegister::create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'user_id' => auth()->id(),
                'status' => 'open',
                'opening_amount' => $data['opening_amount'],
                'opening_notes' => $data['opening_notes'] ?? null,
                'opened_at' => now(),
            ]);

            $auditService->record('cash_register.opened', $cashRegister, [
                'opening_amount' => (float) $cashRegister->opening_amount,
            ]);
        });

        return back()->with(
            'success',
            'Caixa aberto com sucesso.'
        );
    }

    public function movement(
        Request $request,
        CashRegister $cashRegister,
        AuditService $auditService
    ) {
        $this->ensureAccess($cashRegister);

        $data = $request->validate([
            'type' => [
                'required',
                'in:in,out',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'reason' => [
                'required',
                'string',
                'max:150',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use ($cashRegister, $data, $auditService) {
            $lockedRegister = CashRegister::query()
                ->whereKey($cashRegister->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRegister->status !== 'open') {
                abort(422, 'Este caixa já está fechado.');
            }

            $movement = CashMovement::create([
                'cash_register_id' => $lockedRegister->id,
                'user_id' => auth()->id(),
                'type' => $data['type'],
                'amount' => $data['amount'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);

            $auditService->record('cash_register.movement_created', $lockedRegister, [
                'movement_id' => $movement->id,
                'type' => $movement->type,
                'amount' => (float) $movement->amount,
                'reason' => $movement->reason,
            ]);
        });

        return back()->with(
            'success',
            'Movimento de caixa registado.'
        );
    }

    public function close(
        Request $request,
        CashRegister $cashRegister,
        AuditService $auditService
    ) {
        $this->ensureAccess($cashRegister);

        $data = $request->validate([
            'closing_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'closing_notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        DB::transaction(function () use (
            $cashRegister,
            $data,
            $auditService
        ) {
            $lockedRegister = CashRegister::query()
                ->whereKey($cashRegister->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRegister->status !== 'open') {
                abort(422, 'Este caixa já está fechado.');
            }

            $cashSales = Payment::query()
            ->where(
                'cash_register_id',
                $lockedRegister->id
            )
            ->where(
                'method',
                'cash'
            )
            ->sum('amount');

            $manualIn = CashMovement::query()
                ->where(
                    'cash_register_id',
                    $lockedRegister->id
                )
                ->where('type', 'in')
                ->sum('amount');

            $manualOut = CashMovement::query()
                ->where(
                    'cash_register_id',
                    $lockedRegister->id
                )
                ->where('type', 'out')
                ->sum('amount');

            $expected =
                (float) $lockedRegister->opening_amount
                + (float) $cashSales
                + (float) $manualIn
                - (float) $manualOut;

            $closing = (float) $data['closing_amount'];

            if (
                abs($closing - $expected) >= 0.01
                && blank($data['closing_notes'] ?? null)
            ) {
                throw ValidationException::withMessages([
                    'closing_notes' =>
                        'Justifique a diferença entre o valor contado e o saldo esperado.',
                ]);
            }

            $lockedRegister->update([
                'status' => 'closed',
                'closing_amount' => $closing,
                'expected_amount' => $expected,
                'difference' => $closing - $expected,
                'closing_notes' =>
                    $data['closing_notes'] ?? null,
                'closed_at' => now(),
            ]);

            $auditService->record('cash_register.closed', $lockedRegister, [
                'expected_amount' => $expected,
                'closing_amount' => $closing,
                'difference' => $closing - $expected,
                'justification' => $data['closing_notes'] ?? null,
            ]);
        });

        return back()->with(
            'success',
            'Caixa fechado com sucesso.'
        );
    }

    private function ensureAccess(
        CashRegister $cashRegister
    ): void {
        $company = app('activeCompany');
        $store = app('activeStore');

        abort_unless(
            $store
            && $cashRegister->company_id === $company->id
            && $cashRegister->store_id === $store->id
            && $cashRegister->user_id === auth()->id(),
            403
        );
    }
}
