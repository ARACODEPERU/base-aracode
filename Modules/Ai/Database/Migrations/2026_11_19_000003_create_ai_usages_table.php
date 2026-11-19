<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro auditable de cada consulta de IA.
 *
 * Guarda quién la hizo, con qué proveedor y modelo, cuántos tokens consumió y
 * el costo estimado. El texto de la consulta solo se guarda cuando el
 * parámetro IA-00009 lo permite; por defecto no se almacena.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_usages')) {
            return;
        }

        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider', 20);
            $table->string('model', 100);
            $table->string('screen', 120)->nullable();
            $table->string('status', 20)->default('ok');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('total_tokens')->default(0);
            $table->decimal('cost_usd', 12, 6)->default(0);
            $table->text('prompt_excerpt')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['provider', 'model']);

            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
