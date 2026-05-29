<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('even_local_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('local_sales')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('rental_rate_id')->nullable()->constrained('even_local_rental_rates')->nullOnDelete();
            $table->enum('event_type', ['birthday', 'wedding', 'quinceanera', 'party', 'meeting', 'other']);
            $table->date('event_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_hours', 5, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('base_amount', 10, 2);
            $table->boolean('includes_tables_chairs')->default(false);
            $table->boolean('includes_food')->default(false);
            $table->enum('beer_provided_by', ['none', 'company', 'client'])->default('none');
            $table->decimal('total_price', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'partial_20', 'paid_full'])->default('pending');
            $table->enum('reservation_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['local_id', 'event_date']);
            $table->index('customer_id');
            $table->index('payment_status');
            $table->index('reservation_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('even_local_rentals');
    }
};
