<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Descripción con formato del plan.
 *
 * `description` se conserva como texto plano derivado para los listados,
 * comprobantes y webhooks que ya lo usan; `description_html` guarda el HTML
 * acotado que se pinta en la página pública de planes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('aca_subscription_types', 'description_html')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->text('description_html')->nullable()->after('description')
                    ->comment('Descripción con formato (HTML acotado) para la página pública de planes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('aca_subscription_types', 'description_html')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->dropColumn('description_html');
            });
        }
    }
};
