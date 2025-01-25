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
        Schema::create('aca_student_subscriptions', function (Blueprint $table) {
            // Claves foráneas
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subscription_id');

            // Definir la clave primaria compuesta
            $table->primary(['student_id', 'subscription_id']);

            // Campos adicionales
            $table->date('date_start')->nullable();  // Fecha de inicio de la suscripción
            $table->date('date_end')->nullable();    // Fecha de finalización de la suscripción
            $table->boolean('status')->default(true); // Estado de la suscripción (activa o inactiva)

            // Nuevo: Campos sugeridos
            $table->text('notes')->nullable();                  // Notas adicionales
            $table->integer('renewals')->default(0);            // Número de renovaciones
            $table->unsignedBigInteger('registration_user_id')->nullable();
            $table->unsignedBigInteger('onli_sale_id')->nullable();
            $table->timestamps();                               // Fechas de creación y actualización
            $table->foreign('student_id', 'student_id_sfk')->references('id')->on('aca_students')->onDelete('cascade');
            $table->foreign('subscription_id', 'subscription_id_sfk')->references('id')->on('aca_subscription_types')->onDelete('cascade');
            $table->foreign('registration_user_id', 'registration_user_id_ufk')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aca_student_subscriptions');
    }
};
