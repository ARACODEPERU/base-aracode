<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('res_supplies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('unit', ['unidad', 'kg', 'gramo', 'litro', 'ml', 'docena'])->default('unidad');
            $table->decimal('stock', 12, 4)->default(0);
            $table->decimal('stock_min', 12, 4)->default(1);
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('res_supplies');
    }
};
