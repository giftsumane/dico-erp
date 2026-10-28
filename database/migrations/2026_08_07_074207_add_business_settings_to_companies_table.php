<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {

            if (! Schema::hasColumn('companies', 'trade_name')) {
                $table->string('trade_name')->nullable();
            }

            if (! Schema::hasColumn('companies', 'email')) {
                $table->string('email')->nullable();
            }

            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable();
            }

            if (! Schema::hasColumn('companies', 'address')) {
                $table->string('address')->nullable();
            }

            if (! Schema::hasColumn('companies', 'currency')) {
                $table->string('currency', 10)
                    ->default('MZN');
            }

            if (! Schema::hasColumn('companies', 'default_tax_rate')) {
                $table->decimal(
                    'default_tax_rate',
                    5,
                    2
                )->default(0);
            }

            if (! Schema::hasColumn('companies', 'receipt_footer')) {
                $table->text('receipt_footer')
                    ->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {

            if (Schema::hasColumn('companies', 'trade_name')) {
                $table->dropColumn('trade_name');
            }

            if (Schema::hasColumn('companies', 'email')) {
                $table->dropColumn('email');
            }

            if (Schema::hasColumn('companies', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('companies', 'address')) {
                $table->dropColumn('address');
            }

            if (Schema::hasColumn('companies', 'currency')) {
                $table->dropColumn('currency');
            }

            if (Schema::hasColumn('companies', 'default_tax_rate')) {
                $table->dropColumn('default_tax_rate');
            }

            if (Schema::hasColumn('companies', 'receipt_footer')) {
                $table->dropColumn('receipt_footer');
            }
        });
    }
};