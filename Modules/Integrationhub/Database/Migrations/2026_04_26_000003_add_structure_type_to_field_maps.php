<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->enum('structure_type', ['object', 'array'])->default('object')->comment('Tipo de estructura: object=objeto simple, array=array de objetos');
            $table->text('array_query')->nullable()->comment('Consulta SQL para obtener datos del array');
            $table->string('default_type', 20)->nullable()->comment('Tipo de valor por defecto: static, variable, none');
            $table->string('default_table', 100)->nullable()->comment('Tabla para valor por defecto variable');
            $table->string('default_field', 100)->nullable()->comment('Campo para valor por defecto variable');
        });
    }

    public function down(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->dropColumn(['structure_type', 'array_query', 'default_type', 'default_table', 'default_field']);
        });
    }
};