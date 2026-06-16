<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('onli_carrito_abandonado', function (Blueprint $table) {
            $table->id();
            $table->string('client_id', 100)->nullable()->index();
            $table->string('phone_country', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->json('cart_items')->nullable();
            $table->decimal('cart_total', 12, 2)->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->integer('notification_count')->default(0);
            $table->timestamps();

            $table->index('client_id');
            $table->index('phone');
            $table->index('email');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('onli_carrito_abandonado');
    }
};
