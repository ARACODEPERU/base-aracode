<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_negotiation_items', function (Blueprint $table) {
            $table->string('entity_name_product')->nullable()->after('item_type')
                ->comment('Clase de la que proviene el item (ej: Modules\\Academic\\Entities\\AcaCourse). Permite resolver la tabla correcta al generar el comprobante.');
        });
    }

    public function down(): void
    {
        Schema::table('commercial_negotiation_items', function (Blueprint $table) {
            $table->dropColumn('entity_name_product');
        });
    }
};
