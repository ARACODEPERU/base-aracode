<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->string('field_value', 255)->nullable()->change();
            $table->string('source_table', 100)->nullable()->change();
            $table->string('source_field', 100)->nullable()->change();
            $table->string('default_value', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->string('field_value', 255)->nullable(false)->change();
            $table->string('source_table', 100)->nullable(false)->change();
            $table->string('source_field', 100)->nullable(false)->change();
            $table->string('default_value', 255)->nullable(false)->change();
        });
    }
};