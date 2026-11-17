<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pais declarado por el usuario en el registro del blog.
 *
 * La migracion es idempotente: si la tabla no existe o la columna ya fue
 * creada, no hace nada y puede ejecutarse otra vez sin lanzar errores.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            // El registro del blog guarda el telefono en users.telefono. La
            // columna la crea el modulo Socialevents; se comprueba aqui para
            // que esta migracion siga siendo segura si ese modulo no corrio.
            if (! Schema::hasColumn('users', 'telefono')) {
                $table->string('telefono', 20)->nullable();
            }

            if (! Schema::hasColumn('users', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable()->after('person_id')
                    ->comment('id del pais declarado por el usuario');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'country_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('country_id');
        });
    }
};
