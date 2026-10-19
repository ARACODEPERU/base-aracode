<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socialevent_match_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('socialevent_organizations')->cascadeOnDelete();
            $table->foreignId('edition_id')->constrained('event_editions')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('format', 50);
            $table->unsignedSmallInteger('teams_count');
            $table->unsignedSmallInteger('matches_count');
            $table->decimal('amount', 10, 2)->default(0);
            // La primera fecha de cada edición es gratis; las siguientes se cobran.
            $table->boolean('is_free')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'edition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socialevent_match_batches');
    }
};
