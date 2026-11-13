<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name', 120);
            $table->string('feature_key', 60)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 80)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 15, 2)->nullable();
            $table->decimal('price_yearly', 15, 2)->nullable();
            $table->unsignedSmallInteger('trial_days')->default(14);
            $table->unsignedSmallInteger('max_users')->nullable();
            $table->unsignedSmallInteger('max_stores')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_module_id')->constrained()->cascadeOnDelete();
            $table->unique(['subscription_plan_id', 'service_module_id']);
        });

        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('trial');
            $table->string('billing_cycle', 20)->default('monthly');
            $table->decimal('agreed_price', 15, 2)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('company_subscription_module', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_module_id')->constrained()->cascadeOnDelete();
            $table->unique(['company_subscription_id', 'service_module_id'], 'subscription_module_unique');
        });

        $now = now();
        $modules = [
            ['key' => 'invoicing', 'name' => 'Faturação', 'feature_key' => 'sales', 'description' => 'Clientes, cotações, facturas, recibos e notas.', 'sort_order' => 10],
            ['key' => 'receivables', 'name' => 'Contas a receber', 'feature_key' => 'receivables', 'description' => 'Crédito, vencimentos e cobranças.', 'sort_order' => 20],
            ['key' => 'pos', 'name' => 'Ponto de Venda', 'feature_key' => 'pos', 'description' => 'POS, caixa e vendas rápidas.', 'sort_order' => 30],
            ['key' => 'inventory', 'name' => 'Stock', 'feature_key' => 'inventory', 'description' => 'Quantidades, movimentos e valorização.', 'sort_order' => 40],
            ['key' => 'purchases', 'name' => 'Compras', 'feature_key' => 'purchases', 'description' => 'Fornecedores, compras e entradas.', 'sort_order' => 50],
            ['key' => 'advanced_products', 'name' => 'Produtos avançados', 'feature_key' => 'variants', 'description' => 'Marcas, variantes, lotes e validades.', 'sort_order' => 60],
            ['key' => 'event_bars', 'name' => 'Eventos e bares', 'feature_key' => 'event_bars', 'description' => 'Eventos, armazém, bares e balcões.', 'sort_order' => 70],
            ['key' => 'accounting', 'name' => 'Contabilidade', 'feature_key' => null, 'description' => 'Plano de contas, lançamentos e demonstrações (futura fase).', 'sort_order' => 80],
        ];
        foreach ($modules as $module) {
            DB::table('service_modules')->insert($module + ['active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }

        $plans = [
            ['name' => 'Faturação', 'slug' => 'faturacao', 'description' => 'Documentos comerciais para prestadores de serviços e pequenas empresas.', 'trial_days' => 14, 'max_users' => 3, 'max_stores' => 1, 'sort_order' => 10, 'modules' => ['invoicing']],
            ['name' => 'Comercial', 'slug' => 'comercial', 'description' => 'Faturação, contas a receber e acompanhamento comercial.', 'trial_days' => 14, 'max_users' => 5, 'max_stores' => 1, 'sort_order' => 20, 'modules' => ['invoicing', 'receivables']],
            ['name' => 'Loja', 'slug' => 'loja', 'description' => 'Operação completa de loja com POS, caixa e stock.', 'trial_days' => 14, 'max_users' => 10, 'max_stores' => 2, 'sort_order' => 30, 'modules' => ['invoicing', 'receivables', 'pos', 'inventory']],
            ['name' => 'Gestão', 'slug' => 'gestao', 'description' => 'Loja, compras, fornecedores e produtos avançados.', 'trial_days' => 14, 'max_users' => 20, 'max_stores' => 5, 'sort_order' => 40, 'modules' => ['invoicing', 'receivables', 'pos', 'inventory', 'purchases', 'advanced_products']],
            ['name' => 'Eventos', 'slug' => 'eventos', 'description' => 'Gestão comercial e operação de bares em eventos.', 'trial_days' => 14, 'max_users' => 30, 'max_stores' => 10, 'sort_order' => 50, 'modules' => ['invoicing', 'receivables', 'pos', 'inventory', 'purchases', 'event_bars']],
        ];
        foreach ($plans as $plan) {
            $moduleKeys = $plan['modules'];
            unset($plan['modules']);
            $planId = DB::table('subscription_plans')->insertGetId($plan + [
                'price_monthly' => null, 'price_yearly' => null, 'is_public' => true,
                'active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $moduleIds = DB::table('service_modules')->whereIn('key', $moduleKeys)->pluck('id');
            foreach ($moduleIds as $moduleId) {
                DB::table('plan_module')->insert(['subscription_plan_id' => $planId, 'service_module_id' => $moduleId]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_subscription_module');
        Schema::dropIfExists('company_subscriptions');
        Schema::dropIfExists('plan_module');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('service_modules');
    }
};
