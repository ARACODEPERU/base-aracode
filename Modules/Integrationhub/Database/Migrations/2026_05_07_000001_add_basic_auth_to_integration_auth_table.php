<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY COLUMN es exclusivo de MySQL (sqlite no altera tipos de columna).
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'mysql') {
            return;
        }

        // Agregar 'basic_auth' al enum de field_type
        DB::statement("ALTER TABLE integration_auth MODIFY COLUMN field_type ENUM('text', 'password', 'token', 'api_key', 'oauth', 'basic_auth') NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'mysql') {
            return;
        }

        // Revertir: quitar 'basic_auth'
        DB::statement("ALTER TABLE integration_auth MODIFY COLUMN field_type ENUM('text', 'password', 'token', 'api_key', 'oauth') NOT NULL DEFAULT 'text'");
    }
};
