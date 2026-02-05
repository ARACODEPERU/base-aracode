<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aca_subscription_payments', function (Blueprint $table) {
            $table->id();
            // Relación con la tabla original (aunque sea PK compuesta, usamos los IDs)
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subscription_id');
            $table->unsignedBigInteger('document_id')->nullable();

            $table->date('date_start');
            $table->date('date_end');
            $table->decimal('amount', 10, 2)->default(0);
            // Índices para rapidez de consulta
            $table->index(['student_id', 'subscription_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_subscription_payments');
    }
};
