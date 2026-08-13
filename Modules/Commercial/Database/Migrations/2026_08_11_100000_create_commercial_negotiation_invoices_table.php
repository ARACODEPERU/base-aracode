<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_negotiation_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('negotiation_id');
            $table->string('invoice_type', 20)->default('boleta');
            $table->string('ruc', 11)->nullable();
            $table->string('razon_social')->nullable();
            $table->string('direccion')->nullable();
            $table->string('estado', 40)->nullable();
            $table->string('condicion', 40)->nullable();
            $table->string('ubigeo', 10)->nullable();
            $table->string('distrito')->nullable();
            $table->string('provincia')->nullable();
            $table->string('departamento')->nullable();
            $table->timestamps();

            $table->foreign('negotiation_id')->references('id')->on('commercial_negotiations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_negotiation_invoices');
    }
};
