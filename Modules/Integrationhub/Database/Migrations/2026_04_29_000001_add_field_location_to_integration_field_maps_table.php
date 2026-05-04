<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->enum('field_location', ['query', 'path', 'body', 'header'])->default('query')->after('field_type');
        });
    }

    public function down(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->dropColumn('field_location');
        });
    }
};