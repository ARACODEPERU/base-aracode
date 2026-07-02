<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('onli_payment_problems')) {
            Schema::create('onli_payment_problems', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id');
                $table->string('phone_country', 10)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('clie_full_name', 255)->nullable();
                $table->decimal('amount', 12, 2)->nullable();
                $table->string('payment_method', 50)->nullable();
                $table->json('courses_info')->nullable();
                $table->json('payment_data')->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->timestamps();

                $table->foreign('sale_id')->references('id')->on('onli_sales')->onDelete('cascade');
                $table->index('phone');
                $table->index('email');
                $table->index('created_at');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('onli_payment_problems');
    }
};
