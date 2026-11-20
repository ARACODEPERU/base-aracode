<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Texto libre debajo del precio (por ejemplo «Precio válido hasta el 24 de
 * Noviembre» o «Incluye IGV»).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('aca_subscription_types', 'price_note_below')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->text('price_note_below')->nullable()
                    ->after(Schema::hasColumn('aca_subscription_types', 'price_note') ? 'price_note' : 'prices')
                    ->comment('Texto con formato que se muestra debajo del precio en la página de planes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('aca_subscription_types', 'price_note_below')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->dropColumn('price_note_below');
            });
        }
    }
};
