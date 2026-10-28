<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'product_attribute_values',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'product_attribute_id'
                )
                    ->constrained(
                        'product_attributes'
                    )
                    ->cascadeOnDelete();

                $table->string(
                    'value',
                    100
                );

                $table->string(
                    'slug',
                    120
                );

                $table->unsignedInteger(
                    'position'
                )
                    ->default(0);

                $table->boolean(
                    'active'
                )
                    ->default(true);

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Evitar valor duplicado dentro do atributo
                |--------------------------------------------------------------------------
                |
                | Cor
                | ├── Preto
                | ├── Branco
                | └── Azul
                |
                */

                $table->unique([
                    'product_attribute_id',
                    'slug',
                ]);


                $table->index([
                    'product_attribute_id',
                    'active',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_attribute_values'
        );
    }
};