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
        Schema::table('integration_auth', function (Blueprint $table) {
            // Ubicación donde se enviará el campo de autenticación (header, body, query)
            $table->enum('auth_location', ['header', 'body', 'query'])->default('header')->after('field_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_auth', function (Blueprint $table) {
            $table->dropColumn('auth_location');
        });
    }
};
