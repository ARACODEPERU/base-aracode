<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alertas de presupuesto registradas por el comando ai:presupuesto.
 *
 * Se guarda una sola alerta por mes y nivel (aviso o excedido), para que el
 * comando pueda correr cada hora sin duplicar el registro.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_budget_alerts')) {
            return;
        }

        Schema::create('ai_budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7);
            $table->string('level', 20);
            $table->decimal('spend_usd', 12, 6)->default(0);
            $table->decimal('budget_usd', 12, 6)->default(0);
            $table->unsignedTinyInteger('threshold_percent')->default(0);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['period', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_budget_alerts');
    }
};
