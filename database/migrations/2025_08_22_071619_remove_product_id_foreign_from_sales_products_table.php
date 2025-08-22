<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            // Verificar si la clave foránea existe antes de intentar eliminarla
            // Para las claves foráneas, es más robusto verificar la columna
            if (Schema::hasColumn('sales_products', 'product_id')) {
                // Eliminar la restricción de clave foránea
                $table->dropForeign(['product_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_products', function (Blueprint $table) {
            // Verificar si la clave foránea no existe antes de intentar crearla de nuevo
            if (!Schema::hasColumn('sales_products', 'product_id')) {
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            }
        });
    }
};
