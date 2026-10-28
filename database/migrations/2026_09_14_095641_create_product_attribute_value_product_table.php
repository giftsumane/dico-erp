<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_attribute_value_product',
            function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger(
                    'product_attribute_value_id'
                );

                $table->timestamps();

                $table->foreign(
                    'product_id',
                    'pavp_product_fk'
                )
                    ->references('id')
                    ->on('products')
                    ->cascadeOnDelete();

                $table->foreign(
                    'product_attribute_value_id',
                    'pavp_value_fk'
                )
                    ->references('id')
                    ->on('product_attribute_values')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'product_id',
                        'product_attribute_value_id',
                    ],
                    'pavp_product_value_unique'
                );

                $table->index(
                    'product_id',
                    'pavp_product_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_attribute_value_product'
        );
    }
};