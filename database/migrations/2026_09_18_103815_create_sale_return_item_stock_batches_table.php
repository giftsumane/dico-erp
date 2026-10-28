<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'sale_return_item_stock_batches',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('sale_return_item_id')
                    ->constrained('sale_return_items')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('stock_batch_id')
                    ->constrained('stock_batches')
                    ->restrictOnDelete();

                $table->decimal(
                    'quantity',
                    15,
                    3
                );

                $table->timestamps();

                $table->unique(
                    [
                        'sale_return_item_id',
                        'stock_batch_id',
                    ],
                    'sale_return_item_batch_unique'
                );

                $table->index(
                    'stock_batch_id',
                    'sale_return_item_batch_stock_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'sale_return_item_stock_batches'
        );
    }
};