<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->boolean('is_required')->default(false)->after('default_value');
        });
    }

    public function down(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->dropColumn('is_required');
        });
    }
};
