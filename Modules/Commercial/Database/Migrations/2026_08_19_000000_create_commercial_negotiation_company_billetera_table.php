<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_negotiation_company_billetera', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('negotiation_id');
            $table->unsignedBigInteger('company_billetera_id');
            $table->timestamps();

            // Nombres cortos para evitar el limite de 64 chars en MySQL.
            $table->index('negotiation_id', 'neg_idx');
            $table->index('company_billetera_id', 'billetera_idx');
            $table->unique(['negotiation_id', 'company_billetera_id'], 'neg_bill_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_negotiation_company_billetera');
    }
};
