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
        Schema::create('sale_payment_schedule_destinations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('method_id')->nullable();
            $table->date('date_payment')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('amount',12,2)->default(0);
            $table->string('proof',200)->nullable()->comment('para subir una imagen o archivo del pago');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->enum('status', ['pending','paid','failed','observed'])->default('pending');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('sale_documents')->nullOnDelete();
            $table->foreign('schedule_id')->references('id')->on('sale_payment_schedules')->nullOnDelete();
            $table->foreign('method_id')->references('id')->on('payment_methods')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payment_schedule_destinations');
    }
};
