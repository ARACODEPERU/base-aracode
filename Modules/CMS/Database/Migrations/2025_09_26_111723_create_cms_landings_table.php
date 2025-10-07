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
        Schema::create('cms_landings', function (Blueprint $table) {
            $table->id();
            $table->string('menu_id', 2)->nullable();
            $table->string('title', 300)->comment('titulo de la landing');
            $table->string('slug', 500)->comment('ruta amigable');
            $table->string('description_short')->comment('descripcion corta, para la etiqueta meta o otros usos');
            $table->binary('description_long')->nullable()->comment('descripcion amplia usada para todo el conenido');
            $table->string('main_image')->nullable()->comment('imagen principal');
            $table->json('data_related')->nullable()->comment('datos relacionados que se mostraran');
            $table->json('data_related2')->nullable()->comment('datos relacionados que se mostraran, porseacaso se nesesite a futuro');
            $table->text('additional_field1')->nullable()->comment('campo adicional');
            $table->text('additional_field2')->nullable()->comment('campo adicional');
            $table->text('additional_field3')->nullable()->comment('campo adicional');
            $table->date('date_start')->nullable()->comment('si es por campaña fecha de inicio');
            $table->date('date_end')->nullable()->comment('si es por campaña fecha de fin de publicacion');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_landings');
    }
};
