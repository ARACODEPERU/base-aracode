<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->boolean('has_subitems')->default(false)->comment('Indica si el campo tiene subitems (1=si, 0=no)');
            $table->json('subitems')->nullable()->comment('Subitems en formato JSON');
        });
    }

    public function down(): void
    {
        Schema::table('integration_field_maps', function (Blueprint $table) {
            $table->dropColumn(['has_subitems', 'subitems']);
        });
    }
};