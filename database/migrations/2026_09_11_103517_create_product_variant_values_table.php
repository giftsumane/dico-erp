<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_variant_values',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'product_id'
                )
                    ->constrained(
                        'products'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'product_attribute_value_id'
                )
                    ->constrained(
                        'product_attribute_values'
                    )
                    ->cascadeOnDelete();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | O mesmo valor não pode ser ligado duas vezes à mesma variante
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'product_id',
                        'product_attribute_value_id',
                    ],
                    'product_variant_value_unique'
                );


                $table->index(
                    'product_id'
                );

                $table->index(
                    'product_attribute_value_id',
                    'variant_value_attribute_value_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_variant_values'
        );
    }
};