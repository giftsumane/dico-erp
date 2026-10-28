<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'company_features',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId(
                    'company_id'
                )
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string(
                    'feature',
                    100
                );

                $table->boolean(
                    'enabled'
                )->default(true);

                $table->timestamps();

                $table->unique([
                    'company_id',
                    'feature',
                ]);

                $table->index([
                    'feature',
                    'enabled',
                ]);
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'company_features'
        );
    }
};