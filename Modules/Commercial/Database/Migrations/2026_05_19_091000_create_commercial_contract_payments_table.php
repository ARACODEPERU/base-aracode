<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_contract_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('commercial_contracts')->cascadeOnDelete();
            $table->unsignedInteger('payment_number');
            $table->string('description')->nullable();
            $table->date('due_date');
            $table->string('payment_type', 40)->default('custom');
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 5)->default('PEN');
            $table->string('status', 30)->default('pending');
            $table->date('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'due_date']);
            $table->index(['contract_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_contract_payments');
    }
};
