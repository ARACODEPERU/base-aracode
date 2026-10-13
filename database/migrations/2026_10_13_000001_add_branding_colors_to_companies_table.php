<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Colores de marca de la instalación (vista Configuraciones → Branding).
     *
     * Nulos = paleta de fábrica del ERP (resources/css/app.css). Al guardar un
     * color, el layout inyecta las variables `--brand-*` y todo el sistema se
     * pinta con la identidad del cliente.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('brand_primary', 9)->nullable()->after('withdrawal_account_number');
            $table->string('brand_secondary', 9)->nullable()->after('brand_primary');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['brand_primary', 'brand_secondary']);
        });
    }
};
