<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda la variante visual de Aracodito elegida por el administrador.
     * El valor por defecto es el estilo clásico para no cambiar a los usuarios
     * que ya tenían el asistente configurado.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('aracodito_style', 20)->default('classic');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('aracodito_style');
        });
    }
};
