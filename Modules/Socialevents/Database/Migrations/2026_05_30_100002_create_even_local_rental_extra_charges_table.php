<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('even_local_rental_extra_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('even_local_rentals')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->enum('phase', ['booking', 'during_event'])->default('booking');
            $table->enum('reason', ['planned', 'damage', 'additional_service', 'other'])->default('planned');
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rental_id', 'phase']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('even_local_rental_extra_charges');
    }
};
