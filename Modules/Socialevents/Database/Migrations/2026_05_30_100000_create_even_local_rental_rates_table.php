<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('even_local_rental_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('local_sales')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('hourly_rate', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['local_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('even_local_rental_rates');
    }
};
