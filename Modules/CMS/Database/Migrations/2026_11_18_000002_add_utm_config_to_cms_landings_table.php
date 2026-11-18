<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cms_landings', 'utm_config')) {
            Schema::table('cms_landings', function (Blueprint $table) {
                // Configuracion de enlaces UTM del landing: campana, URL base de
                // publicacion y term/content/activo por canal.
                $table->json('utm_config')->nullable()->after('data_related2');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cms_landings', 'utm_config')) {
            Schema::table('cms_landings', function (Blueprint $table) {
                $table->dropColumn('utm_config');
            });
        }
    }
};
