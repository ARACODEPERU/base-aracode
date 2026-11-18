<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Migración consolidada: áreas de los locales propios (local_sales), tarifas por área,
// líneas de reserva, horario por área de campo, descuento y campos de torneo.
return new class extends Migration
{
    public function up(): void
    {
        // Áreas de cada local propio: salón, piscina, campo deportivo, etc.
        Schema::create('even_local_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_id')->constrained('local_sales')->cascadeOnDelete();
            $table->string('name');
            $table->enum('area_type', ['event_hall', 'sports_field', 'pool', 'kids_play'])->default('event_hall');
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['local_id', 'is_active']);
        });

        // Cada local existente recibe un salón por defecto para no perder sus reservas.
        $now = now();

        foreach (DB::table('local_sales')->pluck('id') as $localId) {
            DB::table('even_local_areas')->insert([
                'local_id' => $localId,
                'name' => 'Salón de eventos',
                'area_type' => 'event_hall',
                'capacity' => null,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Cada tarifa existente pasa a pertenecer al salón por defecto de su local.
        Schema::table('even_local_rental_rates', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('local_id')->constrained('even_local_areas')->cascadeOnDelete();
        });

        $halls = DB::table('even_local_areas')
            ->where('area_type', 'event_hall')
            ->pluck('id', 'local_id');

        foreach ($halls as $localId => $areaId) {
            DB::table('even_local_rental_rates')
                ->where('local_id', $localId)
                ->whereNull('area_id')
                ->update(['area_id' => $areaId]);
        }

        // Líneas de reserva: una por área y rango de fechas, usadas para validar choques de horario.
        Schema::create('even_local_rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('even_local_rentals')->cascadeOnDelete();
            $table->foreignId('area_id')->constrained('even_local_areas')->cascadeOnDelete();
            $table->foreignId('rental_rate_id')->nullable()->constrained('even_local_rental_rates')->nullOnDelete();
            $table->date('event_date');
            $table->date('event_end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('total_hours', 5, 2);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('base_amount', 10, 2);
            $table->decimal('extras_total', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->boolean('is_included')->default(false);
            $table->boolean('is_tournament')->default(false);
            $table->timestamps();

            $table->index(['area_id', 'event_date']);
            $table->index('rental_id');
        });

        // Cada reserva existente pasa a tener una línea en el salón de su local, con los mismos montos.
        DB::table('even_local_rentals')->orderBy('id')->chunkById(200, function ($rentals) use ($halls, $now) {
            foreach ($rentals as $rental) {
                $areaId = $halls[$rental->local_id] ?? null;

                if ($areaId === null) {
                    continue;
                }

                DB::table('even_local_rental_items')->insert([
                    'rental_id' => $rental->id,
                    'area_id' => $areaId,
                    'rental_rate_id' => $rental->rental_rate_id,
                    'event_date' => $rental->event_date,
                    'event_end_date' => $rental->event_end_date ?? $rental->event_date,
                    'start_time' => $rental->start_time,
                    'end_time' => $rental->end_time,
                    'total_hours' => $rental->total_hours,
                    'hourly_rate' => $rental->hourly_rate,
                    'base_amount' => $rental->base_amount,
                    'extras_total' => round((float) $rental->total_price - (float) $rental->base_amount, 2),
                    'subtotal' => $rental->total_price,
                    'is_included' => false,
                    'is_tournament' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        // Descuento y torneo en la reserva. El torneo tiene un monto fijo para toda la serie.
        Schema::table('even_local_rentals', function (Blueprint $table) {
            $table->decimal('discount_amount', 10, 2)->default(0)->after('total_price');
            $table->string('discount_reason')->nullable()->after('discount_amount');
            $table->boolean('is_tournament')->default(false)->after('reservation_status');
            // Sede del torneo: nombre del complejo deportivo (even_locals) donde se juega.
            $table->string('tournament_venue')->nullable()->after('is_tournament');
        });

        // Horario operativo por área de campo y por día de la semana (0 = domingo ... 6 = sábado).
        Schema::create('even_local_area_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('even_local_areas')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();

            $table->unique(['area_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('even_local_area_schedules');

        Schema::table('even_local_rentals', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'discount_reason', 'is_tournament']);
        });

        Schema::dropIfExists('even_local_rental_items');

        Schema::table('even_local_rental_rates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });

        Schema::dropIfExists('even_local_areas');
    }
};
