<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_errors', function (Blueprint $table) {
            $table->string('source', 150)->nullable()->after('message')->comment('Origen del error: controlador, endpoint, etc.');
        });
    }

    public function down(): void
    {
        Schema::table('integration_errors', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
