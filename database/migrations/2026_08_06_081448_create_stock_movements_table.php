<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('type', [
                'entry',
                'exit',
                'adjustment_in',
                'adjustment_out',
                'transfer_in',
                'transfer_out',
                'sale',
                'purchase',
                'return_in',
                'return_out',
            ]);

            $table->decimal('quantity', 15, 3);

            $table->decimal('quantity_before', 15, 3)
                ->default(0);

            $table->decimal('quantity_after', 15, 3)
                ->default(0);

            $table->string('reference_type')->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('reference')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('occurred_at')
                ->useCurrent();

            $table->timestamps();

            $table->index([
                'company_id',
                'store_id',
                'product_id',
            ]);

            $table->index([
                'reference_type',
                'reference_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};