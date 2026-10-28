<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            $table->foreignId('cash_register_id')
                ->nullable()
                ->after('sale_id')
                ->constrained('cash_registers')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->after('cash_register_id')
                ->constrained()
                ->nullOnDelete();

            $table->timestamp('paid_at')
                ->nullable()
                ->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'cash_register_id'
            );

            $table->dropConstrainedForeignId(
                'user_id'
            );

            $table->dropColumn('paid_at');
        });
    }
};