<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();

            $table
                ->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table
                ->foreignId('supplier_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('code', 100);
            $table->string('name', 200)->nullable();

            $table
                ->decimal('initial_quantity', 15, 3)
                ->default(0);

            $table
                ->decimal('quantity', 15, 3)
                ->default(0);

            $table
                ->decimal('unit_cost', 15, 2)
                ->default(0);

            $table->date('received_at')->nullable();
            $table->date('expiry_date')->nullable();

            $table
                ->string('status', 30)
                ->default('active');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'store_id',
                    'product_id',
                    'code',
                ],
                'stock_batches_store_product_code_unique'
            );

            $table->index(
                [
                    'company_id',
                    'store_id',
                    'product_id',
                    'status',
                ],
                'stock_batches_lookup_index'
            );

            $table->index(
                [
                    'product_id',
                    'expiry_date',
                ],
                'stock_batches_expiry_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
