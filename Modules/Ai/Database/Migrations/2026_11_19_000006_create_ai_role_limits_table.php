<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Límites de IA por rol. Un 0 en cualquiera de los dos valores significa que
 * ese rol no agrega un límite propio y se queda con el global.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_role_limits')) {
            return;
        }

        Schema::create('ai_role_limits', function (Blueprint $table) {
            $table->id();
            $table->string('role_name', 100)->unique();
            $table->unsignedInteger('daily_limit')->default(0);
            $table->decimal('monthly_budget_usd', 12, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_role_limits');
    }
};
