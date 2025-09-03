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
        Schema::create('cigle_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sede_id')->nullable()->comment('sede donde pertenese');
            $table->unsignedBigInteger('person_id')->comment('se relaciona a la tabla persona');
            $table->string('unique_code', 11)->comment('codigo del miembro unico dentro de la institucion');
            $table->unsignedBigInteger('type_id')->nullable()->comment('id del tipo de miembro');
            $table->boolean('main')->default(false)->comment('segun el tipo si es un principal en caso otros tengan el mismo tipo siempre debe aver uno principal');
            $table->date('date_statement')->nullable()->comment('fecha de afirmacion');
            $table->timestamps();
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('set null');
            $table->foreign('type_id')->references('id')->on('cigle_member_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cigle_members');
    }
};
