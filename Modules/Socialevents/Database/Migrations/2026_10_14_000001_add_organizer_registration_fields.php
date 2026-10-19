<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'apellido_paterno')) {
                $table->string('apellido_paterno', 120)->nullable()->after('name');
            }
            if (! Schema::hasColumn('users', 'apellido_materno')) {
                $table->string('apellido_materno', 120)->nullable()->after('apellido_paterno');
            }
            if (! Schema::hasColumn('users', 'telefono')) {
                $table->string('telefono', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'tipo_identidad')) {
                $table->string('tipo_identidad', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'numero_identidad')) {
                // Único en la tabla: el DNI (o documento) identifica a una sola cuenta.
                $table->string('numero_identidad', 20)->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'sexo')) {
                $table->string('sexo', 1)->nullable();
            }
            if (! Schema::hasColumn('users', 'direccion')) {
                $table->string('direccion', 255)->nullable();
            }
        });

        Schema::table('socialevent_organizations', function (Blueprint $table) {
            if (! Schema::hasColumn('socialevent_organizations', 'tipo_organizacion')) {
                // empresa | persona_natural
                $table->string('tipo_organizacion', 20)->nullable()->after('name');
            }
            if (! Schema::hasColumn('socialevent_organizations', 'ruc')) {
                $table->string('ruc', 11)->nullable()->after('tipo_organizacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('socialevent_organizations', function (Blueprint $table) {
            foreach (['ruc', 'tipo_organizacion'] as $column) {
                if (Schema::hasColumn('socialevent_organizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'numero_identidad')) {
                $table->dropUnique(['numero_identidad']);
            }
            foreach (['direccion', 'sexo', 'numero_identidad', 'tipo_identidad', 'telefono', 'apellido_materno', 'apellido_paterno'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
