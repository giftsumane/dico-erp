<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Produto pai
            |--------------------------------------------------------------------------
            |
            | Será usado futuramente por produtos com variantes.
            |
            | Exemplo:
            |
            | Camisa Classic
            | ├── Preto / S
            | ├── Preto / M
            | └── Branco / M
            |
            */

            $table->foreignId('parent_id')
                ->nullable()
                ->after('company_id')
                ->constrained('products')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Tipo de produto
            |--------------------------------------------------------------------------
            |
            | simple     = produto simples
            | variable   = produto pai com variantes
            | variant    = variante vendável
            | service    = serviço
            | composite  = produto composto / receita
            |
            */

            $table->string(
                'product_type',
                30
            )
                ->default('simple')
                ->after('parent_id');


            /*
            |--------------------------------------------------------------------------
            | Modelo de inventário
            |--------------------------------------------------------------------------
            |
            | none   = sem controlo de stock
            | simple = stock convencional
            | batch  = stock por lotes
            | serial = controlo individual / número de série
            |
            */

            $table->string(
                'inventory_mode',
                30
            )
                ->default('simple')
                ->after('product_type');

            $table->index([
                'company_id',
                'product_type',
            ]);

            $table->index([
                'company_id',
                'inventory_mode',
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Migrar produtos existentes
        |--------------------------------------------------------------------------
        |
        | Mantemos compatibilidade com track_stock.
        |--------------------------------------------------------------------------
        */

        DB::table('products')
            ->update([
                'product_type' => 'simple',
            ]);


        DB::table('products')
            ->where('track_stock', true)
            ->update([
                'inventory_mode' => 'simple',
            ]);


        DB::table('products')
            ->where(function ($query) {
                $query
                    ->where('track_stock', false)
                    ->orWhereNull('track_stock');
            })
            ->update([
                'inventory_mode' => 'none',
            ]);
    }


    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropIndex([
                'company_id',
                'product_type',
            ]);

            $table->dropIndex([
                'company_id',
                'inventory_mode',
            ]);

            $table->dropForeign([
                'parent_id',
            ]);

            $table->dropColumn([
                'parent_id',
                'product_type',
                'inventory_mode',
            ]);
        });
    }
};