<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('res_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('res_recipes')->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained('res_supplies')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->timestamps();

            $table->unique(['recipe_id', 'supply_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('res_recipe_items');
    }
};
