<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commercial_negotiation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('negotiation_id');
            $table->string('item_type', 20);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('title');
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();

            $table->foreign('negotiation_id')->references('id')->on('commercial_negotiations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commercial_negotiation_items');
    }
};
