<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {

            $table->enum('payment_status', [
                'paid',
                'partial',
                'unpaid',
            ])
                ->default('paid')
                ->after('status');

            $table->decimal('amount_due', 15, 2)
                ->default(0)
                ->after('paid_total');

            $table->date('due_date')
                ->nullable()
                ->after('amount_due');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'amount_due',
                'due_date',
            ]);
        });
    }
};