<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estado persistente de las imágenes gestionadas desde Rendimiento.
 *
 * El inventario se puede reconstruir escaneando el disco, pero hay datos que
 * solo existen aquí: la última optimización, la copia de seguridad, la
 * cuarentena y las variantes WebP generadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('performance_images')) {
            Schema::create('performance_images', function (Blueprint $table) {
                $table->id();
                $table->string('path', 512)->unique();
                $table->string('hash', 64)->nullable()->index();
                $table->unsignedBigInteger('bytes')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->timestamp('optimized_at')->nullable();
                $table->unsignedBigInteger('compressed_bytes')->nullable();
                $table->string('backup_path', 512)->nullable();
                $table->string('quarantine_path', 512)->nullable();
                $table->timestamp('quarantined_at')->nullable();
                $table->string('webp_path', 512)->nullable();
                $table->unsignedBigInteger('webp_bytes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('performance_alerts')) {
            Schema::create('performance_alerts', function (Blueprint $table) {
                $table->id();
                $table->string('path', 512);
                $table->unsignedBigInteger('bytes')->default(0);
                $table->string('reason', 120);
                $table->timestamp('detected_at');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['path', 'reviewed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_alerts');
        Schema::dropIfExists('performance_images');
    }
};
