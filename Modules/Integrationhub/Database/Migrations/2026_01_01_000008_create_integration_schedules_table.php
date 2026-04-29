<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained('integrations')->onDelete('cascade');
            $table->string('cron_expression', 100)->comment('Expresión cron (ej: 0 */6 * * * para cada 6 horas)');
            $table->boolean('is_active')->default(true)->comment('Si la programación está activa (1=si, 0=no)');
            $table->timestamp('last_executed_at')->nullable()->comment('Última fecha de ejecución');
            $table->timestamp('next_execution_at')->nullable()->comment('Próxima fecha de ejecución programada');
            $table->timestamps();

            $table->index('integration_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_schedules');
    }
};