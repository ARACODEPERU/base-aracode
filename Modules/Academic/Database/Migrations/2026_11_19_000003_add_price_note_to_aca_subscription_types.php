<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Texto libre encima del precio (por ejemplo «Antes S/ 1800»).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('aca_subscription_types', 'price_note')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->text('price_note')->nullable()
                    ->after(Schema::hasColumn('aca_subscription_types', 'prices') ? 'prices' : 'description_html')
                    ->comment('Texto con formato que se muestra encima del precio en la página de planes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('aca_subscription_types', 'price_note')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->dropColumn('price_note');
            });
        }
    }
};
