<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_edition_match_sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('event_edition_matches')->onDelete('cascade');
            $table->foreignId('player_id')->constrained('people')->onDelete('cascade');
            $table->enum('type', ['yellow', 'red', 'double_yellow']);
            $table->string('minute', 5)->nullable();
            $table->decimal('amount_fine', 8, 2)->default(0)->comment('monto de la multa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_edition_match_sanctions');
    }
};
