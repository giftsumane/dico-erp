<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 100);

            $table->string('slug', 120);

            $table->unsignedInteger('position')
                ->default(0);

            $table->boolean('active')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Um atributo não se deve repetir dentro da mesma empresa
            |--------------------------------------------------------------------------
            |
            | Exemplo:
            |
            | Empresa A → Cor
            | Empresa B → Cor
            |
            | permitido.
            |
            | Empresa A → Cor
            | Empresa A → Cor
            |
            | não permitido.
            */

            $table->unique([
                'company_id',
                'slug',
            ]);

            $table->index([
                'company_id',
                'active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'product_attributes'
        );
    }
};