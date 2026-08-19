<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_billeteras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('billetera_id');
            $table->string('account_name')->comment('Nombre del titular');
            $table->string('account_number')->comment('Numero o cuenta de la billetera');
            $table->string('qr_image')->nullable()->comment('Imagen del codigo QR');
            $table->unsignedBigInteger('bank_account_id')->nullable()->comment('Cuenta bancaria asociada (opcional)');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->foreign('billetera_id')->references('id')->on('billeteras_digitales')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_billeteras');
    }
};
