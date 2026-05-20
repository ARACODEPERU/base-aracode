<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('responsible_person_id')->nullable();
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('contract_type', 40);
            $table->string('title');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 5)->default('PEN');
            $table->longText('body')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('people')->cascadeOnDelete();
            $table->foreign('responsible_person_id')->references('id')->on('people')->nullOnDelete();
            $table->foreign('service_id')->references('id')->on('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_contracts');
    }
};
