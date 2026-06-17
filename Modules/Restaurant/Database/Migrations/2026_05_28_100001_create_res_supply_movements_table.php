<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('res_supply_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('res_supplies')->cascadeOnDelete();
            $table->enum('type', ['purchase', 'consumption', 'adjustment', 'void_sale']);
            $table->decimal('quantity', 12, 4);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('local_id')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('res_supply_movements');
    }
};
