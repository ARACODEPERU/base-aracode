<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_negotiations', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->string('currency', 5)->default('PEN');
            $table->string('payment_type', 20)->default('single');
            $table->decimal('initial_amount', 12, 2)->nullable();
            $table->json('schedule')->nullable();
            $table->integer('single_payment_days')->nullable();
            $table->string('contact_channel', 40)->nullable();
            $table->string('contact_detail')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_link')->nullable();
            $table->string('status', 20)->default('pendiente');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->json('client_data')->nullable();
            $table->string('voucher_path')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('people')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_negotiations');
    }
};
