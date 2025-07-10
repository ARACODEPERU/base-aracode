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
        Schema::create('complaints_books', function (Blueprint $table) {
            $table->id();
            $table->string('composite_code',50);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('names');
            $table->string('document_type_id');
            $table->string('dni', 15);
            $table->string('email');
            $table->string('telefono', 15);
            $table->string('type_service', 15);
            $table->string('description_service');
            $table->string('currency')->nullable();
            $table->decimal('amount', 12,2)->nullable();
            $table->string('type_claim', 15);
            $table->text('claim');
            $table->text('called');
            $table->boolean('accepts')->default(false);
            $table->char('status', 2)->default('RE')->comment('RE=Registrado,ER=En Revisión,EP=En Proceso,AS=Asignado,AT=Atendido,CE=Cerrado,ES=Escalado,RA=Reabierto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints_books');
    }
};
