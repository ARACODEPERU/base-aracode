<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('even_local_rental_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('even_local_rentals')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignId('registered_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rental_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('even_local_rental_payments');
    }
};
