<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heal_patient_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('heal_patients')->cascadeOnDelete();
            $table->foreignId('attention_id')->nullable()->constrained('heal_attentions')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('heal_doctors')->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained('heal_procedures')->nullOnDelete();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->unsignedBigInteger('sale_document_id')->nullable();
            $table->string('name_snapshot', 160);
            $table->text('description_snapshot')->nullable();
            $table->decimal('default_price', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('currency_type_id', 3)->default('PEN');
            $table->string('status', 20)->default('pending');
            $table->timestamp('charged_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['attention_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heal_patient_charges');
    }
};
