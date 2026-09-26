<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY es exclusivo de MySQL (sqlite no altera tipos de columna).
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE integration_field_maps MODIFY field_value TEXT NULL');
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE integration_field_maps MODIFY field_value VARCHAR(255) NULL');
    }
};
