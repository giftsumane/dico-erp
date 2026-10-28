<?php

namespace App\Http\Controllers;

use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Nenhuma loja activa.'
            );
        }

        $search = trim(
            (string) $request->get('search')
        );

        /*
        |--------------------------------------------------------------------------
        | Produtos
        |--------------------------------------------------------------------------
        */

        $products = Product::query()
            ->with([
                'category',
                'brand',
                'unit',
                'parent',
                'attributeValues.attribute',
            ])
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'active',
                true
            )
            ->whereIn(
                'product_type',
                [
                    Product::TYPE_SIMPLE,
                    Product::TYPE_VARIANT,
                    Product::TYPE_SERVICE,
                ]
            )
            ->where(
                function ($query) {
                    $query
                        ->where(
                            'product_type',
                            '!=',
                            Product::TYPE_VARIANT
                        )
                        ->orWhereHas(
                            'parent',
                            function ($parentQuery) {
                                $parentQuery
                                    ->where('active', true)
                                    ->where(
                                        'product_type',
                                        Product::TYPE_VARIABLE
                                    );
                            }
                        );
                }
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
                                )
                                ->orWhereHas(
                                    'parent',
                                    function ($parentQuery) use ($search) {
                                        $parentQuery->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                )
                                ->orWhereHas(
                                    'attributeValues',
                                    function ($valueQuery) use ($search) {
                                        $valueQuery->where(
                                            'value',
                                            'like',
                                            "%{$search}%"
                                        );
                                    }
                                );
                        }
                    );
                }
            )
            ->orderBy('name')
            ->limit(50)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Stock
        |--------------------------------------------------------------------------
        */

        $stockMap = Stock::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'store_id',
                $store->id
            )
            ->pluck(
                'quantity',
                'product_id'
            );

        /*
        |--------------------------------------------------------------------------
        | Clientes
        |--------------------------------------------------------------------------
        */

        $customers = Customer::query()
            ->where(
                'company_id',
                $company->id
            )
            ->where(
                'active',
                true
            )
            ->orderBy('name')
            ->get();

        return view(
            'pos.index',
            compact(
                'products',
                'stockMap',
                'customers',
                'search'
            )
        );
    }


    public function complete(
        Request $request,
        StockService $stockService
    ) {
        $company = app('activeCompany');
        $store = app('activeStore');

        if (! $store) {
            abort(
                403,
                'Nenhuma loja activa.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Caixa activo
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

        if (! $cashRegister) {
            throw ValidationException::withMessages([
                'cash_register' =>
                    'É necessário abrir o caixa antes de efectuar vendas.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validação
        |--------------------------------------------------------------------------
        */

        $data = $request->validate([
            'customer_id' => [
                'nullable',
                'integer',
            ],

            /*
            |--------------------------------------------------------------------------
            | Desconto global
            |--------------------------------------------------------------------------
            */

            'discount_type' => [
                'nullable',
                'in:none,percentage,fixed',
            ],

            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Pagamento
            |--------------------------------------------------------------------------
            */

            'payment_type' => [
                'required',
                'in:full,partial,credit',
            ],

            'payment_method' => [
                'nullable',
                'required_unless:payment_type,credit',
                'in:cash,mpesa,emola,card,bank_transfer,other',
            ],

            'amount_received' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'due_date' => [
                'nullable',
                'date',
            ],

            /*
            |--------------------------------------------------------------------------
            | Produtos
            |--------------------------------------------------------------------------
            */

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Desconto individual por produto
            |--------------------------------------------------------------------------
            */

            'items.*.discount_type' => [
                'nullable',
                'in:none,percentage,fixed',
            ],

            'items.*.discount_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cliente
        |--------------------------------------------------------------------------
        */

        $customerId = null;

        if (! empty($data['customer_id'])) {
            $customerId = Customer::query()
                ->where(
                    'company_id',
                    $company->id
                )
                ->where(
                    'id',
                    $data['customer_id']
                )
                ->where(
                    'active',
                    true
                )
                ->value('id');

            if (! $customerId) {
                throw ValidationException::withMessages([
                    'customer_id' =>
                        'Cliente inválido.',
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Transacção
        |--------------------------------------------------------------------------
        */

        $sale = DB::transaction(
            function () use (
                $data,
                $company,
                $store,
                $customerId,
                $stockService,
                $cashRegister
            ) {
                /*
                |--------------------------------------------------------------------------
                | Preparar produtos
                |--------------------------------------------------------------------------
                */

                $preparedItems = [];

                /*
                 * Subtotal bruto da venda.
                 *
                 * Ainda não inclui quaisquer descontos.
                 */
                $subtotal = 0;

                /*
                 * Total de descontos individuais.
                 */
                $itemDiscountTotal = 0;

                /*
                 * Valor líquido depois dos descontos
                 * individuais e antes do desconto global.
                 */
                $subtotalAfterItemDiscounts = 0;

                foreach (
                    $data['items']
                    as $row
                ) {
                    $product = Product::query()
                        ->where(
                            'company_id',
                            $company->id
                        )
                        ->where(
                            'id',
                            $row['product_id']
                        )
                        ->where(
                            'active',
                            true
                        )
                        ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | Segurança do tipo de produto
                    |--------------------------------------------------------------------------
                    |
                    | O produto-pai do tipo variable nunca pode ser vendido
                    | directamente. A venda deve utilizar a variante concreta.
                    |--------------------------------------------------------------------------
                    */

                    if (
                        ! in_array(
                            $product->product_type,
                            [
                                Product::TYPE_SIMPLE,
                                Product::TYPE_VARIANT,
                                Product::TYPE_SERVICE,
                            ],
                            true
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'O produto '
                                . $product->name
                                . ' não pode ser vendido directamente.',
                        ]);
                    }

                    if ($product->isVariant()) {
                        $validParent = Product::query()
                            ->where(
                                'company_id',
                                $company->id
                            )
                            ->where(
                                'id',
                                $product->parent_id
                            )
                            ->where(
                                'product_type',
                                Product::TYPE_VARIABLE
                            )
                            ->where(
                                'active',
                                true
                            )
                            ->exists();

                        if (! $validParent) {
                            throw ValidationException::withMessages([
                                'items' =>
                                    'A variante '
                                    . $product->name
                                    . ' pertence a um produto principal indisponível.',
                            ]);
                        }
                    }

                    $quantity =
                        (float) $row['quantity'];

                    $unitPrice =
                        (float) $product->sale_price;

                    $taxRate =
                        (float) $product->tax_rate;

                    /*
                    |--------------------------------------------------------------------------
                    | Validar stock
                    |--------------------------------------------------------------------------
                    */

                    if ($product->track_stock) {
                        $available =
                            (float) Stock::query()
                                ->where(
                                    'company_id',
                                    $company->id
                                )
                                ->where(
                                    'store_id',
                                    $store->id
                                )
                                ->where(
                                    'product_id',
                                    $product->id
                                )
                                ->value('quantity');

                        if ($available < $quantity) {
                            throw ValidationException::withMessages([
                                'items' =>
                                    'Stock insuficiente para '
                                    . $product->name
                                    . '. Disponível: '
                                    . $available,
                            ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Subtotal bruto da linha
                    |--------------------------------------------------------------------------
                    */

                    $lineSubtotal = round(
                        $quantity * $unitPrice,
                        2
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Desconto individual
                    |--------------------------------------------------------------------------
                    */

                    $itemDiscountType =
                        $row['discount_type']
                        ?? 'none';

                    $itemDiscountValue =
                        isset(
                            $row['discount_value']
                        )
                            ? (float) $row[
                                'discount_value'
                            ]
                            : 0;

                    $itemDiscount = 0;

                    /*
                    |--------------------------------------------------------------------------
                    | Desconto percentual do item
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $itemDiscountType
                        === 'percentage'
                    ) {
                        if (
                            $itemDiscountValue > 100
                        ) {
                            throw ValidationException::withMessages([
                                'items' =>
                                    'O desconto percentual do produto '
                                    . $product->name
                                    . ' não pode ser superior a 100%.',
                            ]);
                        }

                        $itemDiscount = round(
                            $lineSubtotal
                            * (
                                $itemDiscountValue
                                / 100
                            ),
                            2
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Desconto fixo do item
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $itemDiscountType
                        === 'fixed'
                    ) {
                        if (
                            $itemDiscountValue
                            > $lineSubtotal
                        ) {
                            throw ValidationException::withMessages([
                                'items' =>
                                    'O desconto do produto '
                                    . $product->name
                                    . ' não pode ser superior ao valor da linha.',
                            ]);
                        }

                        $itemDiscount = round(
                            $itemDiscountValue,
                            2
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Subtotal líquido depois do desconto individual
                    |--------------------------------------------------------------------------
                    */

                    $subtotalAfterItemDiscount =
                        max(
                            0,
                            round(
                                $lineSubtotal
                                - $itemDiscount,
                                2
                            )
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Acumuladores
                    |--------------------------------------------------------------------------
                    */

                    $subtotal +=
                        $lineSubtotal;

                    $itemDiscountTotal +=
                        $itemDiscount;

                    $subtotalAfterItemDiscounts +=
                        $subtotalAfterItemDiscount;

                    /*
                    |--------------------------------------------------------------------------
                    | Preparar item
                    |--------------------------------------------------------------------------
                    */

                    $preparedItems[] = [
                        'product' =>
                            $product,

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            $unitPrice,

                        'tax_rate' =>
                            $taxRate,

                        /*
                         * Valor bruto.
                         */
                        'subtotal' =>
                            $lineSubtotal,

                        /*
                         * Desconto escolhido especificamente
                         * para este produto.
                         */
                        'item_discount' =>
                            $itemDiscount,

                        /*
                         * Base disponível para receber
                         * posteriormente desconto global.
                         */
                        'subtotal_after_item_discount' =>
                            $subtotalAfterItemDiscount,
                    ];
                }

                $subtotal = round(
                    $subtotal,
                    2
                );

                $itemDiscountTotal = round(
                    $itemDiscountTotal,
                    2
                );

                $subtotalAfterItemDiscounts = round(
                    $subtotalAfterItemDiscounts,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Desconto global
                |--------------------------------------------------------------------------
                |
                | O desconto global é aplicado DEPOIS dos descontos
                | individuais.
                |--------------------------------------------------------------------------
                */

                $discountType =
                    $data['discount_type']
                    ?? 'none';

                $discountValue =
                    isset(
                        $data['discount_value']
                    )
                        ? (float) $data[
                            'discount_value'
                        ]
                        : 0;

                $globalDiscountTotal = 0;

                /*
                |--------------------------------------------------------------------------
                | Desconto global percentual
                |--------------------------------------------------------------------------
                */

                if (
                    $discountType
                    === 'percentage'
                ) {
                    if (
                        $discountValue > 100
                    ) {
                        throw ValidationException::withMessages([
                            'discount_value' =>
                                'A percentagem de desconto global não pode ser superior a 100%.',
                        ]);
                    }

                    $globalDiscountTotal = round(
                        $subtotalAfterItemDiscounts
                        * (
                            $discountValue
                            / 100
                        ),
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Desconto global fixo
                |--------------------------------------------------------------------------
                */

                if (
                    $discountType
                    === 'fixed'
                ) {
                    if (
                        $discountValue
                        > $subtotalAfterItemDiscounts
                    ) {
                        throw ValidationException::withMessages([
                            'discount_value' =>
                                'O desconto global não pode ser superior ao valor restante da venda.',
                        ]);
                    }

                    $globalDiscountTotal = round(
                        $discountValue,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Total de descontos da venda
                |--------------------------------------------------------------------------
                |
                | sales.discount_total:
                |
                | descontos individuais
                | +
                | desconto global
                |--------------------------------------------------------------------------
                */

                $discountTotal = round(
                    $itemDiscountTotal
                    + $globalDiscountTotal,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Distribuir desconto global pelos produtos
                |--------------------------------------------------------------------------
                */

                $taxTotal = 0;
                $total = 0;

                $distributedGlobalDiscount = 0;

                $lastIndex =
                    count($preparedItems) - 1;

                foreach (
                    $preparedItems
                    as $index => $prepared
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Parcela do desconto global
                    |--------------------------------------------------------------------------
                    */

                    $globalLineDiscount = 0;

                    if (
                        $globalDiscountTotal > 0
                        && $subtotalAfterItemDiscounts > 0
                    ) {
                        /*
                         * O último item recebe a diferença
                         * causada pelos arredondamentos.
                         */

                        if (
                            $index === $lastIndex
                        ) {
                            $globalLineDiscount = round(
                                $globalDiscountTotal
                                - $distributedGlobalDiscount,
                                2
                            );
                        } else {
                            $proportion =
                                $prepared[
                                    'subtotal_after_item_discount'
                                ]
                                / $subtotalAfterItemDiscounts;

                            $globalLineDiscount = round(
                                $globalDiscountTotal
                                * $proportion,
                                2
                            );

                            $distributedGlobalDiscount +=
                                $globalLineDiscount;
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Desconto total deste item
                    |--------------------------------------------------------------------------
                    |
                    | desconto próprio do produto
                    | +
                    | parcela do desconto global
                    |--------------------------------------------------------------------------
                    */

                    $lineDiscount = round(
                        $prepared[
                            'item_discount'
                        ]
                        + $globalLineDiscount,
                        2
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Base tributável
                    |--------------------------------------------------------------------------
                    */

                    $taxableAmount = max(
                        0,
                        round(
                            $prepared['subtotal']
                            - $lineDiscount,
                            2
                        )
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | IVA
                    |--------------------------------------------------------------------------
                    */

                    $lineTax = round(
                        $taxableAmount
                        * (
                            $prepared['tax_rate']
                            / 100
                        ),
                        2
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Total da linha
                    |--------------------------------------------------------------------------
                    */

                    $lineTotal = round(
                        $taxableAmount
                        + $lineTax,
                        2
                    );

                    $preparedItems[$index][
                        'global_discount'
                    ] = $globalLineDiscount;

                    $preparedItems[$index][
                        'discount'
                    ] = $lineDiscount;

                    $preparedItems[$index][
                        'tax_total'
                    ] = $lineTax;

                    $preparedItems[$index][
                        'total'
                    ] = $lineTotal;

                    $taxTotal +=
                        $lineTax;

                    $total +=
                        $lineTotal;
                }

                $taxTotal = round(
                    $taxTotal,
                    2
                );

                $total = round(
                    $total,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Condição de pagamento
                |--------------------------------------------------------------------------
                */

                $paymentType =
                    $data['payment_type'];

                $paymentMethod =
                    $data['payment_method']
                    ?? null;

                $amountReceived =
                    isset(
                        $data['amount_received']
                    )
                        ? (float) $data[
                            'amount_received'
                        ]
                        : 0;

                /*
                |--------------------------------------------------------------------------
                | Pagamento total
                |--------------------------------------------------------------------------
                */

                if (
                    $paymentType === 'full'
                ) {
                    /*
                     * Método electrónico:
                     * o pagamento é exactamente o total.
                     */

                    if (
                        $paymentMethod
                        !== 'cash'
                    ) {
                        $amountReceived =
                            $total;
                    }

                    /*
                     * Cash sem valor introduzido:
                     * assumimos pagamento exacto.
                     */

                    if (
                        $paymentMethod
                        === 'cash'
                        && $amountReceived <= 0
                    ) {
                        $amountReceived =
                            $total;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Venda a crédito
                |--------------------------------------------------------------------------
                */

                if (
                    $paymentType
                    === 'credit'
                ) {
                    $amountReceived = 0;

                    $paymentMethod = null;
                }

                /*
                |--------------------------------------------------------------------------
                | Pagamento parcial
                |--------------------------------------------------------------------------
                */

                if (
                    $paymentType
                    === 'partial'
                ) {
                    if (
                        $amountReceived <= 0
                    ) {
                        throw ValidationException::withMessages([
                            'amount_received' =>
                                'Informe o valor do depósito.',
                        ]);
                    }

                    if (
                        $amountReceived >= $total
                    ) {
                        throw ValidationException::withMessages([
                            'amount_received' =>
                                'Para pagamento parcial, o valor deve ser inferior ao total da venda.',
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Troco
                |--------------------------------------------------------------------------
                */

                $change = 0;

                $paidApplied =
                    $amountReceived;

                if (
                    $paymentType === 'full'
                    && $paymentMethod === 'cash'
                ) {
                    if (
                        $amountReceived
                        < $total
                    ) {
                        throw ValidationException::withMessages([
                            'amount_received' =>
                                'O valor recebido é inferior ao total da venda.',
                        ]);
                    }

                    $change = max(
                        0,
                        $amountReceived
                        - $total
                    );

                    /*
                     * O valor que entra no Payment
                     * é o valor da venda.
                     *
                     * O excedente é troco.
                     */
                    $paidApplied =
                        $total;
                }

                /*
                |--------------------------------------------------------------------------
                | Segurança
                |--------------------------------------------------------------------------
                */

                if (
                    $paidApplied > $total
                ) {
                    $paidApplied =
                        $total;
                }

                $paidApplied = round(
                    $paidApplied,
                    2
                );

                $change = round(
                    $change,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Saldo
                |--------------------------------------------------------------------------
                */

                $amountDue = max(
                    0,
                    round(
                        $total
                        - $paidApplied,
                        2
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Estado de pagamento
                |--------------------------------------------------------------------------
                */

                if (
                    $amountDue <= 0
                ) {
                    $paymentStatus =
                        'paid';
                } elseif (
                    $paidApplied > 0
                ) {
                    $paymentStatus =
                        'partial';
                } else {
                    $paymentStatus =
                        'unpaid';
                }

                /*
                |--------------------------------------------------------------------------
                | Dívida exige cliente
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $paymentStatus,
                        [
                            'partial',
                            'unpaid',
                        ],
                        true
                    )
                    && ! $customerId
                ) {
                    throw ValidationException::withMessages([
                        'customer_id' =>
                            'Seleccione um cliente para vendas com saldo em dívida.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Vencimento
                |--------------------------------------------------------------------------
                */

                $dueDate =
                    $amountDue > 0
                        ? (
                            $data['due_date']
                            ?? null
                        )
                        : null;

                if (
                    $amountDue > 0
                    && ! $dueDate
                ) {
                    throw ValidationException::withMessages([
                        'due_date' =>
                            'Informe a data prevista para pagamento do saldo.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Criar venda
                |--------------------------------------------------------------------------
                */

                $sale = Sale::create([
                    'cash_register_id' =>
                        $cashRegister->id,

                    'company_id' =>
                        $company->id,

                    'store_id' =>
                        $store->id,

                    'customer_id' =>
                        $customerId,

                    'user_id' =>
                        auth()->id(),

                    'number' =>
                        null,

                    'status' =>
                        'draft',

                    'payment_status' =>
                        $paymentStatus,

                    /*
                     * Subtotal bruto antes de qualquer
                     * desconto.
                     */
                    'subtotal' =>
                        $subtotal,

                    /*
                     * Soma:
                     *
                     * descontos dos produtos
                     * +
                     * desconto global.
                     */
                    'discount_total' =>
                        $discountTotal,

                    'tax_total' =>
                        $taxTotal,

                    'total' =>
                        $total,

                    'paid_total' =>
                        $paidApplied,

                    'amount_due' =>
                        $amountDue,

                    'due_date' =>
                        $dueDate,

                    'change_total' =>
                        $change,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Número da venda
                |--------------------------------------------------------------------------
                */

                $sale->update([
                    'number' =>
                        'VND-'
                        . str_pad(
                            (string) $sale->id,
                            6,
                            '0',
                            STR_PAD_LEFT
                        ),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Itens
                |--------------------------------------------------------------------------
                */

                foreach (
                    $preparedItems
                    as $prepared
                ) {
                    $saleItem = SaleItem::create([
                        'sale_id' =>
                            $sale->id,

                        'product_id' =>
                            $prepared[
                                'product'
                            ]->id,

                        'quantity' =>
                            $prepared[
                                'quantity'
                            ],

                        'unit_price' =>
                            $prepared[
                                'unit_price'
                            ],

                        /*
                         * Este valor contém:
                         *
                         * desconto individual
                         * +
                         * quota do desconto global.
                         */
                        'discount' =>
                            $prepared[
                                'discount'
                            ],

                        'tax_rate' =>
                            $prepared[
                                'tax_rate'
                            ],

                        /*
                         * Subtotal bruto.
                         */
                        'subtotal' =>
                            $prepared[
                                'subtotal'
                            ],

                        'tax_total' =>
                            $prepared[
                                'tax_total'
                            ],

                        /*
                         * Total líquido + IVA.
                         */
                        'total' =>
                            $prepared[
                                'total'
                            ],
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Baixa de stock
                    |--------------------------------------------------------------------------
                    |
                    | StockService decide automaticamente:
                    |
                    | - stock simples → remove()
                    | - stock por lotes → FIFO / FEFO
                    |
                    */

                    if (
                        $prepared[
                            'product'
                        ]->track_stock
                    ) {
                        $stockService->removeForSale(
                            product:
                                $prepared[
                                    'product'
                                ],

                            store:
                                $store,

                            quantity:
                                $prepared[
                                    'quantity'
                                ],

                            saleItem:
                                $saleItem,

                            saleNumber:
                                $sale->number,

                            useExpiryDates:
                                $company->hasFeature(
                                    'expiry_dates'
                                )
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Pagamento inicial
                |--------------------------------------------------------------------------
                */

                if (
                    $paidApplied > 0
                    && $paymentMethod
                ) {
                    Payment::create([
                        'sale_id' =>
                            $sale->id,

                        'cash_register_id' =>
                            $cashRegister->id,

                        'user_id' =>
                            auth()->id(),

                        'method' =>
                            $paymentMethod,

                        'amount' =>
                            $paidApplied,

                        'reference' =>
                            $data[
                                'payment_reference'
                            ] ?? null,

                        'paid_at' =>
                            now(),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Concluir venda
                |--------------------------------------------------------------------------
                */

                $sale->update([
                    'status' =>
                        'completed',

                    'completed_at' =>
                        now(),
                ]);

                return $sale;
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Mensagem
        |--------------------------------------------------------------------------
        */

        $message = match (
            $sale->payment_status
        ) {
            'paid' =>
                'Venda concluída e paga na totalidade.',

            'partial' =>
                'Venda concluída com pagamento parcial.',

            'unpaid' =>
                'Venda concluída a crédito.',

            default =>
                'Venda concluída com sucesso.',
        };

        return redirect()
            ->route(
                'pos.receipt',
                $sale
            )
            ->with(
                'success',
                $message
            );
    }


    public function receipt(
        Sale $sale
    ) {
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

        $sale->load([
            'items.product.unit',
            'payments',
            'customer',
            'store',
            'user',
        ]);

        return view(
            'pos.receipt',
            compact(
                'sale',
                'company'
            )
        );
    }
}