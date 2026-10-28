<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'sale_return_items',
            function (Blueprint $table) {
                $table->id();

                $table
                    ->foreignId('sale_return_id')
                    ->constrained('sale_returns')
                    ->cascadeOnDelete();

                $table
                    ->foreignId('sale_item_id')
                    ->constrained('sale_items')
                    ->restrictOnDelete();

                $table
                    ->foreignId('product_id')
                    ->constrained('products')
                    ->restrictOnDelete();

                $table->decimal(
                    'quantity',
                    15,
                    3
                );

                /*
                |--------------------------------------------------------------------------
                | Valores históricos
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'unit_price',
                    15,
                    2
                );

                $table->decimal(
                    'total',
                    15,
                    2
                );

                $table->timestamps();

                $table->index(
                    'sale_item_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'sale_return_items'
        );
    }
};